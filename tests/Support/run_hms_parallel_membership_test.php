<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Coins;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\HmsMembership;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Transactions;
use App\Models\UserFamilySubscription;
use App\Models\UserReward;
use App\Models\UserRewardProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (DB::connection()->getDriverName() !== 'mysql') {
    fwrite(STDERR, "Run this script with the local MySQL connection.\n");
    exit(2);
}

$hospital = Hospital::query()->find(1);
if (! $hospital) {
    fwrite(STDERR, "Hospital 1 is not available.\n");
    exit(2);
}

$suffix = Str::upper(Str::random(6));
$mobile = '6'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hms-membership-start-'.Str::uuid().'.gate';
$integration = null;
$plan = null;
$processes = [];
$pipesByName = [];

$payload = [
    'hms_membership_id' => 'HMS-M-'.$suffix,
    'type' => 'new',
    'plan_code' => 'HMS-PLAN-'.$suffix,
    'hospital_code' => '1',
    'patient' => [
        'mobile' => $mobile,
        'name' => 'Member Lock',
        'dob' => '1990-01-01',
        'gender' => 'female',
        'email' => null,
        'patient_ref' => 'P-'.$suffix,
    ],
    'covered_members' => [],
    'amount_paid' => 5000,
    'payment' => [
        'reference' => 'RCPT-'.$suffix,
        'method' => 'cash',
        'paid_at' => now()->toIso8601String(),
    ],
    'start_date' => now()->toDateString(),
];

try {
    $plan = FamilyPackage::query()->create([
        'name' => 'HMS lock '.$suffix,
        'code' => 'HMS-PLAN-'.$suffix,
        'price' => 5000,
        'duration_days' => 30,
        'max_members' => 4,
        'max_consultations' => 2,
        'max_lab_tests' => 0,
        'max_hip_coins' => 0,
        'branch_ids' => null,
        'benefits' => [],
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $integration = HmsIntegration::query()->create([
        'name' => 'HMS membership lock '.$suffix,
        'hospital_ids' => [1],
        'organization_id' => null,
        'api_key_hash' => HmsIntegration::hashKey('mem-'.$suffix),
        'signing_secret' => Str::random(40),
        'allowed_ips' => null,
        'is_enabled' => true,
        'rate_limit_per_minute' => 120,
    ]);

    $encoded = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    $workerScript = __DIR__.'/hms_parallel_membership_worker.php';
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

    $rows = HmsMembership::withTrashed()->where('integration_id', $integration->id)->count();
    $subscriptionIds = HmsMembership::query()->where('integration_id', $integration->id)->pluck('subscription_id')->unique();
    $created = count(array_filter($workerResults, fn (array $row): bool => (int) $row['http_status'] === 201));
    $replayed = count(array_filter($workerResults, fn (array $row): bool => (int) $row['http_status'] === 200));

    if ($rows !== 1 || $subscriptionIds->count() !== 1 || $created !== 1 || $replayed !== 1) {
        throw new RuntimeException('Concurrency assertion failed: '.json_encode([
            'rows' => $rows,
            'subscriptions' => $subscriptionIds,
            'workers' => $workerResults,
        ]));
    }

    echo json_encode([
        'driver' => DB::connection()->getDriverName(),
        'membership_rows' => $rows,
        'subscription_id' => $subscriptionIds->first(),
        'workers' => $workerResults,
        'assertion' => 'PASS: two simultaneous identical purchases created exactly one subscription',
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
        $links = HmsMembership::withTrashed()->where('integration_id', $integration->id)->get();
        foreach ($links as $link) {
            $subscription = UserFamilySubscription::query()->find($link->subscription_id);
            if ($subscription) {
                UserReward::query()->where('entity_id', $subscription->id)->where('entity_type', UserReward::ENTITY_TYPE_FAMILY_PACKAGE)->delete();
                Transactions::query()->where('invoice_id', $subscription->invoice_id)->delete();
                Invoice::query()->whereKey($subscription->invoice_id)->delete();
                $subscription->delete();
            }
            $link->forceDelete();
        }
        $userIds = HIPUser::query()->where('mobile_num', $mobile)->pluck('id');
        $personIds = Persons::query()->whereIn('hip_user_id', $userIds)->orWhere('mobile', $mobile)->pluck('id');
        Coins::query()->whereIn('person_id', $personIds)->delete();
        UserRewardProgress::query()->whereIn('hip_user_id', $userIds)->delete();
        Persons::query()->whereIn('id', $personIds)->delete();
        HIPUser::query()->whereIn('id', $userIds)->delete();
        HmsExternalIdMap::query()->where('integration_id', $integration->id)->delete();
        $integration->forceDelete();
    }
    if ($plan) {
        $plan->forceDelete();
    }
}
