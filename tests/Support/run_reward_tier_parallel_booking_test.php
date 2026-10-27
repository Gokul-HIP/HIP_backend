<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\RewardTier;
use App\Models\RewardTierConfig;
use App\Models\RewardTierDiscountUsage;
use App\Models\UserRewardProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

if (DB::connection()->getDriverName() !== 'mysql') {
    fwrite(STDERR, "Run this script with the local MySQL connection.\n");
    exit(2);
}

$user = null;
$tier = null;
$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'reward-tier-start-'.Str::uuid().'.gate';
$processes = [];
$pipesByName = [];
$testKey = Str::upper(Str::random(10));

try {
    DB::transaction(function () use (&$user, &$tier, $testKey): void {
        $user = HIPUser::query()->create([
            'email' => strtolower($testKey).'@example.invalid',
            'password' => Hash::make(Str::random(48)),
            'first_name' => 'Concurrency',
            'last_name' => 'Test',
            'is_active' => true,
        ]);
        $tier = RewardTier::query()->create([
            'name' => 'Concurrency '.$testKey,
            'min_coins' => 0,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $tier->id,
            'total_discount_percentage' => 5,
            'free_checkup_count' => 1,
            'earned_coins_per_booking' => 0,
        ]);
        UserRewardProgress::query()->create([
            'hip_user_id' => $user->id,
            'reward_tier_id' => $tier->id,
            'earned_coins_in_tier' => 0,
            'total_lifetime_coins' => 0,
        ]);
    });

    $workerScript = __DIR__.'/reward_tier_parallel_booking_worker.php';
    foreach (['A', 'B'] as $workerName) {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open([PHP_BINARY, $workerScript, $user->id, $barrier, $workerName], $descriptors, $pipes, dirname(__DIR__, 2));
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start booking worker '.$workerName);
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
        $exitCode = proc_close($processes[$workerName]);
        if ($exitCode !== 0) {
            throw new RuntimeException("Worker {$workerName} exited {$exitCode}: {$stderr}{$stdout}");
        }
        $workerResults[] = json_decode(trim($stdout), true, flags: JSON_THROW_ON_ERROR);
    }

    $discounted = array_values(array_filter($workerResults, fn (array $row): bool => $row['reward_discount'] > 0));
    $fullPrice = array_values(array_filter($workerResults, fn (array $row): bool => (float) $row['reward_discount'] === 0.0));
    $progressCount = UserRewardProgress::query()->where('hip_user_id', $user->id)->count();
    $reservationCount = RewardTierDiscountUsage::query()->where('hip_user_id', $user->id)->count();

    if (count($discounted) !== 1 || count($fullPrice) !== 1 || $progressCount !== 1 || $reservationCount !== 1) {
        throw new RuntimeException('Concurrency assertion failed: '.json_encode(compact('workerResults', 'progressCount', 'reservationCount')));
    }

    echo json_encode([
        'driver' => DB::connection()->getDriverName(),
        'workers' => $workerResults,
        'progress_rows' => $progressCount,
        'reward_usage_rows' => $reservationCount,
        'assertion' => 'PASS: exactly one discount reservation; one full-price booking; one progress row',
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
    if ($user) {
        DoctorBooking::query()->where('member_id', $user->id)->delete();
        RewardTierDiscountUsage::query()->where('hip_user_id', $user->id)->delete();
        UserRewardProgress::query()->where('hip_user_id', $user->id)->delete();
        if ($tier) {
            RewardTierConfig::query()->where('reward_tier_id', $tier->id)->delete();
            $tier->delete();
        }
        $user->delete();
    }
}
