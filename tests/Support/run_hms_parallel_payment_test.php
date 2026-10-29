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
if (! $hospital) {
    fwrite(STDERR, "No hospital row is available.\n");
    exit(2);
}
$doctor = Doctor::query()->orderBy('id')->get()->first(function (Doctor $doctor) use ($hospital): bool {
    $ids = array_map('intval', $doctor->hospital_ids ?? []);

    return $ids === [] || in_array((int) $hospital->getKey(), $ids, true);
});
if (! $doctor) {
    fwrite(STDERR, "No doctor is available at hospital {$hospital->id}.\n");
    exit(2);
}

$mobile = '6'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
$hmsBookingId = 'HMS-PAY-'.Str::upper(Str::random(8));
$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hms-payment-start-'.Str::uuid().'.gate';
$reuseBarrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hms-payment-reuse-'.Str::uuid().'.gate';
$integration = null;
$processes = [];
$pipesByName = [];

$payload = [
    'hms_booking_id' => $hmsBookingId,
    'appointment_type' => 'doctor',
    'hospital_code' => (string) $hospital->getKey(),
    'doctor_id' => (string) $doctor->getKey(),
    'appointment_date' => now()->addDays(3)->toDateString(),
    'slot_time' => '11:00',
    'original_fee' => 600,
    'payable_amount' => 1,
    'patient' => [
        'mobile' => $mobile,
        'name' => 'Pay Lock',
        'dob' => '1991-04-02',
        'gender' => 'female',
        'email' => null,
        'patient_ref' => 'PAY-'.$hmsBookingId,
        'relationship' => 'Self',
        'primary_mobile' => null,
    ],
    'notes' => null,
];

$erase = function (HmsBooking $link, bool $erasePatient): void {
    HmsPayment::query()->where('hms_booking_row_id', $link->id)->delete();
    RewardTierDiscountUsage::query()
        ->where('booking_type', 'doctor_booking')
        ->where('booking_id', $link->booking_id)
        ->delete();
    DoctorBookingStatus::query()->where('doctor_booking_id', $link->booking_id)->delete();
    Transactions::query()->where('invoice_id', $link->invoice_id)->delete();
    Invoice::query()->whereKey($link->invoice_id)->delete();
    DoctorBooking::query()->whereKey($link->booking_id)->delete();
    if ($erasePatient) {
        Coins::query()->where('person_id', $link->person_id)->delete();
        UserRewardProgress::query()->where('hip_user_id', $link->hip_user_id)->delete();
        Persons::query()->whereKey($link->person_id)->delete();
        HIPUser::query()->whereKey($link->hip_user_id)->delete();
    }
    $link->forceDelete();
};

try {
    $integration = HmsIntegration::query()->create([
        'name' => 'HMS payment lock '.$hmsBookingId,
        'hospital_ids' => [(int) $hospital->getKey()],
        'organization_id' => null,
        'api_key_hash' => HmsIntegration::hashKey('pay-'.$hmsBookingId),
        'signing_secret' => Str::random(40),
        'allowed_ips' => null,
        'is_enabled' => true,
        'rate_limit_per_minute' => 120,
    ]);

    $bookings = app(HmsBookingService::class);
    $bookings->book($integration, 'doctor', $payload);
    $probe = HmsBooking::query()->where('integration_id', $integration->id)->where('hms_booking_id', $hmsBookingId)->firstOrFail();
    $expected = round((float) $probe->expected_amount, 2);
    if ($probe->price_mismatch) {
        $erase($probe, false);
        $payload['payable_amount'] = $expected;
        $bookings->book($integration, 'doctor', $payload);
    }

    $booking = HmsBooking::query()->where('integration_id', $integration->id)->where('hms_booking_id', $hmsBookingId)->firstOrFail();
    if ($booking->status !== HmsBooking::STATUS_PENDING) {
        throw new RuntimeException('Booking was not pending_payment: '.$booking->status.' expected '.$expected);
    }

    $payment = [
        'hms_booking_id' => $hmsBookingId,
        'payment' => [
            'reference' => 'RCPT-'.$hmsBookingId,
            'status' => 'success',
            'amount' => round((float) $booking->expected_amount, 2),
            'method' => 'cash',
            'paid_at' => now()->toIso8601String(),
        ],
    ];
    $encoded = base64_encode(json_encode($payment, JSON_THROW_ON_ERROR));
    $workerScript = __DIR__.'/hms_parallel_payment_worker.php';
    foreach (['A', 'B'] as $workerName) {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(
            [PHP_BINARY, $workerScript, (string) $integration->id, $barrier, $encoded],
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

    $payments = HmsPayment::query()->where('hms_booking_row_id', $booking->id)->get();
    $settled = $payments->where('result', HmsPayment::RESULT_SETTLED)->count();
    $confirmed = HmsBooking::query()->whereKey($booking->id)->value('status') === HmsBooking::STATUS_CONFIRMED;
    $okWorkers = count(array_filter($workerResults, fn (array $row): bool => (int) $row['http_status'] === 200 && ($row['state'] ?? '') === 'confirmed'));

    if ($payments->count() !== 1 || $settled !== 1 || ! $confirmed || $okWorkers !== 2) {
        throw new RuntimeException('Concurrency assertion failed: '.json_encode([
            'payments' => $payments->count(),
            'settled' => $settled,
            'confirmed' => $confirmed,
            'workers' => $workerResults,
        ]));
    }

    $reuseId = 'HMS-REUSE-'.Str::upper(Str::random(8));
    $reusePayload = $payload;
    $reusePayload['hms_booking_id'] = $reuseId;
    $reusePayload['slot_time'] = '15:00';
    $bookings->book($integration, 'doctor', $reusePayload);
    $reuseProbe = HmsBooking::query()->where('integration_id', $integration->id)->where('hms_booking_id', $reuseId)->firstOrFail();
    if ($reuseProbe->price_mismatch) {
        $reusePayload['payable_amount'] = round((float) $reuseProbe->expected_amount, 2);
        $erase($reuseProbe, false);
        $bookings->book($integration, 'doctor', $reusePayload);
    }
    $reuseBooking = HmsBooking::query()->where('integration_id', $integration->id)->where('hms_booking_id', $reuseId)->firstOrFail();
    if ($reuseBooking->status !== HmsBooking::STATUS_PENDING) {
        throw new RuntimeException('Reuse booking was not pending_payment: '.$reuseBooking->status);
    }
    $reuseReference = 'RCPT-REUSE-'.$reuseId;
    $rejected = app(HmsSettlementService::class)->verify($integration, [
        'hms_booking_id' => $reuseId,
        'payment' => [
            'reference' => $reuseReference,
            'status' => 'success',
            'amount' => 1,
            'method' => 'cash',
            'paid_at' => now()->toIso8601String(),
        ],
    ]);
    if (($rejected['body']['code'] ?? '') !== 'amount_mismatch') {
        throw new RuntimeException('Expected a rejected reference before the reuse race: '.json_encode($rejected));
    }

    $reusePayment = [
        'hms_booking_id' => $reuseId,
        'payment' => [
            'reference' => $reuseReference,
            'status' => 'success',
            'amount' => round((float) $reuseBooking->expected_amount, 2),
            'method' => 'upi',
            'paid_at' => now()->toIso8601String(),
        ],
    ];
    $reuseEncoded = base64_encode(json_encode($reusePayment, JSON_THROW_ON_ERROR));
    foreach (['C', 'D'] as $workerName) {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(
            [PHP_BINARY, $workerScript, (string) $integration->id, $reuseBarrier, $reuseEncoded],
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

    touch($reuseBarrier);
    $reuseResults = [];
    foreach (['C', 'D'] as $workerName) {
        $pipes = $pipesByName[$workerName];
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        unset($pipesByName[$workerName]);
        $exitCode = proc_close($processes[$workerName]);
        if ($exitCode !== 0) {
            throw new RuntimeException("Worker {$workerName} exited {$exitCode}: {$stderr}{$stdout}");
        }
        $reuseResults[$workerName] = json_decode(trim((string) $stdout), true, flags: JSON_THROW_ON_ERROR);
    }

    $reused = HmsPayment::query()->where('payment_reference', $reuseReference)->get();
    $reuseSettled = $reused->where('result', HmsPayment::RESULT_SETTLED)->count();
    $reuseConfirmed = HmsBooking::query()->whereKey($reuseBooking->id)->value('status') === HmsBooking::STATUS_CONFIRMED;
    $settledWorkers = count(array_filter($reuseResults, fn (array $row): bool => ($row['result'] ?? '') === HmsPayment::RESULT_SETTLED));
    $duplicateWorkers = count(array_filter($reuseResults, fn (array $row): bool => ($row['result'] ?? '') === HmsPayment::RESULT_DUPLICATE));
    $notes = (string) $reused->first()?->notes;
    if ($reused->count() !== 1 || $reuseSettled !== 1 || ! $reuseConfirmed || $settledWorkers !== 1 || $duplicateWorkers !== 1 || ! str_contains($notes, HmsPayment::RESULT_MISMATCH)) {
        throw new RuntimeException('Rejected-reference concurrency assertion failed: '.json_encode([
            'payments' => $reused->count(),
            'settled' => $reuseSettled,
            'confirmed' => $reuseConfirmed,
            'notes' => $notes,
            'workers' => $reuseResults,
        ]));
    }

    echo json_encode([
        'driver' => DB::connection()->getDriverName(),
        'hms_booking_id' => $hmsBookingId,
        'paid_amount' => (float) $booking->fresh()->expected_amount,
        'payment_rows' => $payments->count(),
        'workers' => $workerResults,
        'reuse_hms_booking_id' => $reuseId,
        'reuse_workers' => $reuseResults,
        'assertion' => 'PASS: two simultaneous identical verifications settled once, including a reference that was rejected first',
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    foreach ([$barrier, $reuseBarrier] as $gate) {
        if (is_file($gate)) {
            unlink($gate);
        }
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
            $erase($link, true);
        }
        $userIds = HIPUser::query()->where('mobile_num', $mobile)->pluck('id');
        Coins::query()->whereIn('person_id', Persons::query()->whereIn('hip_user_id', $userIds)->pluck('id'))->delete();
        UserRewardProgress::query()->whereIn('hip_user_id', $userIds)->delete();
        Persons::query()->whereIn('hip_user_id', $userIds)->orWhere('mobile', $mobile)->delete();
        HIPUser::query()->where('mobile_num', $mobile)->delete();
        HmsExternalIdMap::query()->where('integration_id', $integration->id)->delete();
        $integration->forceDelete();
    }
}
