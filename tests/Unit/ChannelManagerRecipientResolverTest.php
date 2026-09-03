<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use Tests\TestCase;

class ChannelManagerRecipientResolverTest extends TestCase
{
    private TestableChannelManager $channelManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channelManager = new TestableChannelManager(
            $this->createMock(PushNotificationService::class),
            $this->createMock(WhatsAppNotificationService::class),
            $this->createMock(SMSNotificationService::class),
            $this->createMock(EmailNotificationService::class),
            $this->createMock(\App\Modules\Workflow\Services\Runtime\AiVoiceCallService::class),
        );
    }

    public function test_resolves_logical_patient_email_from_context(): void
    {
        $resolved = $this->channelManager->resolve(
            'patient',
            'email',
            ['patient_email' => 'patient@example.com']
        );

        $this->assertSame('patient@example.com', $resolved);
    }

    public function test_resolves_logical_patient_sms_from_context(): void
    {
        $resolved = $this->channelManager->resolve(
            'patient',
            'sms',
            ['patient_mobile' => '9876543210']
        );

        $this->assertSame('9876543210', $resolved);
    }

    public function test_resolves_logical_doctor_email_from_model(): void
    {
        $doctor = (object) ['email' => 'doctor@example.com', 'mobile_number' => '9123456789'];

        $resolved = $this->channelManager->resolve(
            'doctor',
            'sendEmail',
            ['doctor' => $doctor]
        );

        $this->assertSame('doctor@example.com', $resolved);
    }

    public function test_resolves_logical_doctor_whatsapp_from_model(): void
    {
        $doctor = (object) ['email' => 'doctor@example.com', 'mobile_number' => '9123456789'];

        $resolved = $this->channelManager->resolve(
            'doctor',
            'whatsapp',
            ['doctor' => $doctor]
        );

        $this->assertSame('9123456789', $resolved);
    }

    public function test_uses_literal_email_without_resolution(): void
    {
        $resolved = $this->channelManager->resolve(
            'custom@example.com',
            'email',
            ['patient_email' => 'patient@example.com']
        );

        $this->assertSame('custom@example.com', $resolved);
    }

    public function test_uses_literal_phone_without_resolution(): void
    {
        $resolved = $this->channelManager->resolve(
            '+91 98765 43210',
            'sms',
            ['patient_mobile' => '1111111111']
        );

        $this->assertSame('+91 98765 43210', $resolved);
    }

    public function test_resolves_hospital_and_member_contacts(): void
    {
        $hospital = (object) [
            'admin_email' => 'admin@hospital.com',
            'admin_contact' => '9000000001',
        ];

        $member = (object) [
            'email' => 'member@example.com',
            'mobile_num' => '9000000002',
        ];

        $this->assertSame(
            'admin@hospital.com',
            $this->channelManager->resolve('hospital', 'email', ['hospital' => $hospital])
        );

        $this->assertSame(
            '9000000001',
            $this->channelManager->resolve('hospital', 'sendSMS', ['hospital' => $hospital])
        );

        $this->assertSame(
            'member@example.com',
            $this->channelManager->resolve('member', 'email', ['member' => $member])
        );

        $this->assertSame(
            '9000000002',
            $this->channelManager->resolve('member', 'whatsapp', ['member' => $member])
        );
    }

    public function test_falls_back_to_patient_contact_when_recipient_is_empty(): void
    {
        $this->assertSame(
            'fallback@example.com',
            $this->channelManager->resolve(null, 'email', ['patient_email' => 'fallback@example.com'])
        );

        $this->assertSame(
            '9999999999',
            $this->channelManager->resolve('', 'sendWhatsApp', ['patient_mobile' => '9999999999'])
        );
    }
}

class TestableChannelManager extends ChannelManager
{
    /** @param  array<string, mixed>  $context */
    public function resolve(?string $recipient, string $channel, array $context): ?string
    {
        return $this->resolveRecipient(
            $recipient,
            $this->normalizeChannelType($channel),
            $context
        );
    }
}
