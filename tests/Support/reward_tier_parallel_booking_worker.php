<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$script, $userId, $barrierPath, $workerName] = $argv;
$waitUntil = microtime(true) + 30;
while (! is_file($barrierPath) && microtime(true) < $waitUntil) {
    usleep(10000);
}
if (! is_file($barrierPath)) {
    fwrite(STDERR, "Parent barrier timed out.\n");
    exit(2);
}

try {
    $result = Illuminate\Support\Facades\DB::transaction(function () use ($userId, $workerName): array {
        $tiers = app(App\Services\RewardTierService::class);
        $discount = $tiers->calculateBasePriceDiscount($userId, 500);
        // Keep the progress row locked long enough for the second process to contend on it.
        usleep(500000);

        $breakdown = app(App\Services\InvoiceFeeCalculator::class)->bookingBreakdown(500, (float) $discount['amount']);
        $booking = App\Models\DoctorBooking::query()->create([
            'member_id' => $userId,
            'status' => 'pending',
            'payment_status' => 'pending',
            'consultation_fee' => 500,
            'total_discount' => $breakdown['discount_amount'],
            'service_charges' => $breakdown['service_charges'],
            'amount_after_discount' => $breakdown['discounted_amount'],
            'total_amount' => $breakdown['invoice_total'],
            'is_online_payment' => true,
        ]);

        if ((float) $discount['amount'] > 0) {
            $tiers->reserveDiscountUsage($userId, $discount, 'doctor_booking', (int) $booking->id, false);
        }

        return [
            'worker' => $workerName,
            'booking_id' => (int) $booking->id,
            'reward_discount' => (float) $discount['amount'],
            'total_amount' => (float) $breakdown['invoice_total'],
        ];
    });

    echo json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
