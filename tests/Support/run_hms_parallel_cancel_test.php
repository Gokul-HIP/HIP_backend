<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Coins;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use App\Models\HIPUser;
use App\Models\HmsBooking;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\HmsPayment;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\RewardPointsTransition;
use App\Models\RewardTierDiscountUsage;
use App\Models\Transactions;
use App\Models\UserRewardProgress;
use App\Services\Hms\HmsBookingService;
use App\Services\Hms\HmsSettlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (DB::connection()->getDriverName() !== 'mysql') {
    fwrite(STDERR, "Run this script with the local MySQL connection.\n");
    exit(2);
}

$hospital = Hospital::query()->find(1) ?? Hospital::query()->orderBy('id')->first();
$doctor = $hospital ? Doctor::query()->orderBy('id')->get()->first(function (Doctor $doctor) use ($hospital): bool {
    $ids = array_map('intval', $doctor->hospital_ids ?? []);

    return $ids === [] || in_array((int) $hospital->getKey(), $ids, true);
}) : null;
if (! $hospital || ! $doctor) {
    fwrite(STDERR, "A hospital and doctor are required.\n");
    exit(2);
}

$mobile = '6'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
$hmsBookingId = 'HMS-CAN-'.Str::upper(Str::random(8));
$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hms-cancel-start-'.Str::uuid().'.gate';
$integration = null;
$processes = [];
$pipesByName = [];

$payload = [
    'hms_booking_id' => $hmsBookingId,
    'appointment_type' => 'doctor',
    'hospital_code' => (string) $hospital->getKey(),
    'doctor_id' => (string) $doctor->getKey(),
    'appointment_date' => now()->addDays(4)->toDateString(),
    'slot_time' => '15:00',
    'original_fee' => 600,
    'payable_amount' => 1,
    'patient' => [
        'mobile' => $mobile,
        'name' => 'Cancel Lock',
        'dob' => '1992-05-05',
        'gender' => 'female',
        'email' => null,
        'patient_ref' => 'CAN-'.$hmsBookingId,
        'relationship' => 'Self',
        'primary_mobile' => null,
    ],
    'notes' => null,
];

$erase = function (HmsBooking $link): void {
    HmsPayment::query()->where('hms_booking_row_id', $link->id)->delete();
    RewardTierDiscountUsage::query()->where('booking_type', 'doctor_booking')->where('booking_id', $link->booking_id)->delete();
    DoctorBookingStatus::query()->where('doctor_booking_id', $link->booking_id)->delete();
    Transactions::query()->where('invoice_id', $link->invoice_id)->delete();
    Invoice::query()->whereKey($link->invoice_id)->delete();
    DoctorBooking::query()->whereKey($link->booking_id)->delete();
    RewardPointsTransition::query()->where('hip_user_id', $link->hip_user_id)->delete();
    Coins::query()->where('person_id', $link->person_id)->delete();
    UserRewardProgress::query()->where('hip_user_id', $link->hip_user_id)->delete();
    Persons::query()->whereKey($link->person_id)->delete();
    HIPUser::query()->whereKey($link->hip_user_id)->delete();
    $link->forceDelete();
};

try {
    $integration = HmsIntegration::query()->create([
        'name' => 'HMS cancel lock '.$hmsBookingId,
        'hospital_ids' => [(int) $hospital->getKey()],
        'organization_id' => null,
        'api_key_hash' => HmsIntegration::hashKey('can-'.$hmsBookingId),
        'signing_secret' => Str::random(40),
        'allowed_ips' => null,
        'is_enabled' => true,
        'rate_limit_per_minute' => 120,
    ]);

    $bookings = app(HmsBookingService::class);
    $bookings->book($integration, 'doctor', $payload);
    $probe = HmsBooking::query()->where('integration_id', $integration->id)->where('hms_booking_id', $hmsBookingId)->firstOrFail();
    if ($probe->price_mismatch) {
        $expected = round((float) $probe->expected_amount, 2);
        $erase($probe);
        $payload['payable_amount'] = $expected;
        $bookings->book($integration, 'doctor', $payload);
    }

    $booking = HmsBooking::query()->where('integration_id', $integration->id)->where('hms_booking_id', $hmsBookingId)->firstOrFail();
    $paid = app(HmsSettlementService::class)->verify($integration, [
        'hms_booking_id' => $hmsBookingId,
        'payment' => [
            'reference' => 'RCPT-'.$hmsBookingId,
            'status' => 'success',
            'amount' => round((float) $booking->expected_amount, 2),
            'method' => 'cash',
            'paid_at' => now()->toIso8601String(),
        ],
    ]);
    if ((int) $paid['http_status'] !== 200 || ($paid['body']['state'] ?? '') !== 'confirmed') {
        throw new RuntimeException('Payment did not confirm: '.json_encode($paid));
    }

    $earned = (int) Invoice::query()->find($booking->invoice_id)?->coins_earned;
    $before = (int) Coins::query()->where('person_id', $booking->person_id)->value('coins');
    $cancelPayload = base64_encode(json_encode([
        'hms_booking_id' => $hmsBookingId,
        'reason' => 'patient request',
        'cancelled_at' => now()->toIso8601String(),
    ], JSON_THROW_ON_ERROR));
    $workerScript = __DIR__.'/hms_parallel_cancel_worker.php';
    foreach (['A', 'B'] as $workerName) {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(
            [PHP_BINARY, $workerScript, (string) $integration->id, $barrier, $cancelPayload],
            $descriptors,
            $pipes,
            dirname(__DIR__, 2)
        );
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start worker '.$workerName);
        }
        fclose($pipes[0]);
        $processes[$workerName] = $process;
        $pipesByName[$workerName] = $pipes;
    }

    touch($barrier);
    $workerResults = [];
    foreach ($pipesByName as $workerName => $pipes) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        unset($pipesByName[$workerName]);
        $exitCode = proc_close($processes[$workerName]);
        if ($exitCode !== 0) {
            throw new RuntimeException("Worker {$workerName} exited {$exitCode}: {$stderr}{$stdout}");
        }
        $workerResults[$workerName] = json_decode(trim((string) $stdout), true, flags: JSON_THROW_ON_ERROR);
    }

    $after = (int) Coins::query()->where('person_id', $booking->person_id)->value('coins');
    $reversals = RewardPointsTransition::query()
        ->where('hip_user_id', $booking->hip_user_id)
        ->where('change_type', 'adjusted')
        ->count();
    $ok = count(array_filter($workerResults, fn (array $row): bool => (int) $row['http_status'] === 200 && ($row['state'] ?? '') === 'cancelled'));
    $expectedBalance = max(0, $before - $earned);

    if ($ok !== 2 || $reversals !== 1 || $after !== $expectedBalance || $earned <= 0) {
        throw new RuntimeException('Concurrency assertion failed: '.json_encode([
            'earned' => $earned,
            'before' => $before,
            'after' => $after,
            'expected_balance' => $expectedBalance,
            'reversals' => $reversals,
            'workers' => $workerResults,
        ]));
    }

    echo json_encode([
        'driver' => DB::connection()->getDriverName(),
        'coins_earned' => $earned,
        'balance_before_cancel' => $before,
        'balance_after_cancel' => $after,
        'reversal_rows' => $reversals,
        'workers' => $workerResults,
        'assertion' => 'PASS: two simultaneous identical cancels reversed coins once',
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    if (is_file($barrier)) {
        unlink($barrier);
    }
    foreach ($pipesByName as $pipes) {
        foreach ([1, 2] as $index) {
            if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                fclose($pipes[$index]);
            }
        }
    }
    if ($integration) {
        $links = HmsBooking::withTrashed()->where('integration_id', $integration->id)->get();
        foreach ($links as $link) {
            $erase($link);
        }
        $userIds = HIPUser::query()->where('mobile_num', $mobile)->pluck('id');
        $personIds = Persons::query()->whereIn('hip_user_id', $userIds)->pluck('id');
        Coins::query()->whereIn('person_id', $personIds)->delete();
        RewardPointsTransition::query()->whereIn('hip_user_id', $userIds)->delete();
        UserRewardProgress::query()->whereIn('hip_user_id', $userIds)->delete();
        Persons::query()->whereIn('id', $personIds)->delete();
        HIPUser::query()->whereIn('id', $userIds)->delete();
        HmsExternalIdMap::query()->where('integration_id', $integration->id)->delete();
        $integration->forceDelete();
    }
}
