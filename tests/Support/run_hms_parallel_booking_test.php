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
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\RewardTierDiscountUsage;
use App\Models\Transactions;
use App\Models\UserRewardProgress;
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
$hmsBookingId = 'HMS-LOCK-'.Str::upper(Str::random(8));
$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hms-booking-start-'.Str::uuid().'.gate';
$integration = null;
$processes = [];
$pipesByName = [];

$payload = [
    'hms_booking_id' => $hmsBookingId,
    'appointment_type' => 'doctor',
    'hospital_code' => (string) $hospital->getKey(),
    'doctor_id' => (string) $doctor->getKey(),
    'appointment_date' => now()->addDays(3)->toDateString(),
    'slot_time' => '10:30',
    'original_fee' => 600,
    'payable_amount' => 600,
    'patient' => [
        'mobile' => $mobile,
        'name' => 'Lock Test',
        'dob' => '1990-05-01',
        'gender' => 'female',
        'email' => null,
        'patient_ref' => 'LOCK-'.$hmsBookingId,
        'relationship' => 'Self',
        'primary_mobile' => null,
    ],
    'notes' => null,
];

try {
    $integration = HmsIntegration::query()->create([
        'name' => 'HMS lock test '.$hmsBookingId,
        'hospital_ids' => [(int) $hospital->getKey()],
        'organization_id' => null,
        'api_key_hash' => HmsIntegration::hashKey('lock-'.$hmsBookingId),
        'signing_secret' => Str::random(40),
        'allowed_ips' => null,
        'is_enabled' => true,
        'rate_limit_per_minute' => 120,
    ]);

    $encoded = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    $workerScript = __DIR__.'/hms_parallel_booking_worker.php';
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

    $rows = HmsBooking::query()
        ->where('integration_id', $integration->id)
        ->where('hms_booking_id', $hmsBookingId)
        ->count();
    $localIds = HmsBooking::query()
        ->where('integration_id', $integration->id)
        ->where('hms_booking_id', $hmsBookingId)
        ->pluck('booking_id')
        ->unique();
    $created = array_values(array_filter($workerResults, fn (array $row): bool => (int) $row['http_status'] === 201));
    $replayed = array_values(array_filter($workerResults, fn (array $row): bool => (int) $row['http_status'] === 200));

    if ($rows !== 1 || $localIds->count() !== 1 || count($created) !== 1 || count($replayed) !== 1) {
        throw new RuntimeException('Concurrency assertion failed: '.json_encode([
            'rows' => $rows,
            'local_ids' => $localIds,
            'workers' => $workerResults,
        ]));
    }

    echo json_encode([
        'driver' => DB::connection()->getDriverName(),
        'hms_booking_rows' => $rows,
        'doctor_booking_id' => $localIds->first(),
        'workers' => $workerResults,
        'assertion' => 'PASS: two simultaneous identical requests created exactly one booking',
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
        $links = HmsBooking::withTrashed()
            ->where('integration_id', $integration->id)
            ->get();
        foreach ($links as $link) {
            RewardTierDiscountUsage::query()
                ->where('booking_type', 'doctor_booking')
                ->where('booking_id', $link->booking_id)
                ->delete();
            DoctorBookingStatus::query()->where('doctor_booking_id', $link->booking_id)->delete();
            Transactions::query()->where('invoice_id', $link->invoice_id)->delete();
            Invoice::query()->whereKey($link->invoice_id)->delete();
            DoctorBooking::query()->whereKey($link->booking_id)->delete();
            Coins::query()->where('person_id', $link->person_id)->delete();
            UserRewardProgress::query()->where('hip_user_id', $link->hip_user_id)->delete();
            Persons::query()->whereKey($link->person_id)->delete();
            HIPUser::query()->whereKey($link->hip_user_id)->delete();
            $link->forceDelete();
        }
        HmsExternalIdMap::query()->where('integration_id', $integration->id)->delete();
        $integration->forceDelete();
    }
}
