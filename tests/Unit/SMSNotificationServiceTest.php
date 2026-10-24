<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SMSNotificationServiceTest extends TestCase
{
    public function test_unconfigured_sms_keeps_the_logged_stub_success(): void
    {
        config([
            'services.sms.base_url' => null,
            'services.sms.token' => null,
        ]);
        Http::fake();

        $result = app(SMSNotificationService::class)->send('9876543210', 'Points 5');

        $this->assertTrue($result['success']);
        $this->assertSame('SMS notification logged (provider stub)', $result['response']);
        Http::assertNothingSent();
    }

    public function test_configured_sms_posts_to_the_provider_and_reports_acceptance(): void
    {
        config([
            'services.sms.base_url' => 'https://sms.example.test',
            'services.sms.token' => 'secret-token',
            'services.sms.sender' => 'HIP',
            'services.sms.timeout' => 5,
        ]);
        Http::fake([
            'https://sms.example.test/messages' => Http::response(['id' => 'sms-1'], 200),
        ]);

        $result = app(SMSNotificationService::class)->send('9876543210', 'Points 5');

        $this->assertTrue($result['success']);
        $this->assertSame('SMS accepted', $result['response']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sms.example.test/messages'
                && $request['to'] === '9876543210'
                && $request['message'] === 'Points 5'
                && $request['sender'] === 'HIP'
                && $request->hasHeader('Authorization', 'Bearer secret-token');
        });
    }

    public function test_provider_rejection_fails_the_sms_action(): void
    {
        config([
            'services.sms.base_url' => 'https://sms.example.test',
            'services.sms.token' => 'secret-token',
        ]);
        Http::fake([
            'https://sms.example.test/messages' => Http::response(['error' => 'rejected'], 422),
        ]);

        $result = app(SMSNotificationService::class)->send('9876543210', 'Points 5');

        $this->assertFalse($result['success']);
        $this->assertSame('SMS provider rejected the message', $result['response']);
    }

    public function test_missing_mobile_does_not_call_the_provider(): void
    {
        config([
            'services.sms.base_url' => 'https://sms.example.test',
            'services.sms.token' => 'secret-token',
        ]);
        Http::fake();

        $result = app(SMSNotificationService::class)->send(null, 'Points 5');

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }
}
