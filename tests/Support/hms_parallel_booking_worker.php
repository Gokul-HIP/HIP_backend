<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$script, $integrationId, $barrierPath, $payload64] = $argv;
$waitUntil = microtime(true) + 30;
while (! is_file($barrierPath) && microtime(true) < $waitUntil) {
    usleep(10000);
}
if (! is_file($barrierPath)) {
    fwrite(STDERR, "Parent barrier timed out.\n");
    exit(2);
}

try {
    $integration = App\Models\HmsIntegration::query()->findOrFail((int) $integrationId);
    $payload = json_decode(base64_decode($payload64), true, flags: JSON_THROW_ON_ERROR);
    $result = app(App\Services\Hms\HmsBookingService::class)->book($integration, 'doctor', $payload);
    echo json_encode([
        'http_status' => $result['http_status'],
        'booking_id' => $result['body']['booking_id'],
        'state' => $result['body']['state'],
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    fwrite(STDERR, $e::class.': '.$e->getMessage().PHP_EOL);
    exit(1);
}
