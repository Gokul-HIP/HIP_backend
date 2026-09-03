<?php

namespace App\Modules\HospitalAutomation\Testing;

final class AutomationTestAccount
{
    public function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly ?string $userId,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $whatsapp,
        public readonly ?string $deviceToken,
    ) {}

    public function whatsappNumber(): ?string
    {
        return filled($this->whatsapp) ? $this->whatsapp : $this->phone;
    }

    /**
     * @param  list<string>  $channels  Normalized: push|email|sms|whatsapp
     */
    public function assertRecipientsForChannels(array $channels): void
    {
        foreach ($channels as $channel) {
            match ($channel) {
                'push' => $this->requireFilled($this->userId, 'AUTOMATION_TEST_USER_ID (or account user_id) is required for push'),
                'email' => $this->requireFilled($this->email, 'AUTOMATION_TEST_EMAIL is required for email'),
                'sms' => $this->requireFilled($this->phone, 'AUTOMATION_TEST_PHONE is required for sms'),
                'whatsapp' => $this->requireFilled(
                    $this->whatsappNumber(),
                    'AUTOMATION_TEST_WHATSAPP or AUTOMATION_TEST_PHONE is required for whatsapp'
                ),
                default => throw new AutomationTestException("Unsupported test channel: {$channel}"),
            };
        }
    }

    private function requireFilled(?string $value, string $message): void
    {
        if (! filled($value)) {
            throw new AutomationTestException($message);
        }
    }
}
