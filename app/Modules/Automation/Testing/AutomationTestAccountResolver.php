<?php

namespace App\Modules\Automation\Testing;

class AutomationTestAccountResolver
{
    public function assertEnabled(): void
    {
        if (! (bool) config('automation.test.enabled')) {
            throw new AutomationTestException(
                'Automation real-notification testing is disabled. Set AUTOMATION_TEST_ENABLED=true to run.'
            );
        }
    }

    public function resolve(int $accountId): AutomationTestAccount
    {
        $this->assertEnabled();

        /** @var array<int|string, mixed>|null $accounts */
        $accounts = config('automation.test.accounts');

        if (! is_array($accounts) || ! array_key_exists($accountId, $accounts)) {
            throw new AutomationTestException(
                "Test account {$accountId} is not configured. Use --account=1 (or configure account {$accountId})."
            );
        }

        $raw = $accounts[$accountId];

        if (! is_array($raw)) {
            throw new AutomationTestException("Test account {$accountId} configuration is invalid.");
        }

        $account = new AutomationTestAccount(
            id: $accountId,
            label: (string) ($raw['label'] ?? "Test Account {$accountId}"),
            userId: $this->nullableString($raw['user_id'] ?? null),
            phone: $this->nullableString($raw['phone'] ?? null),
            email: $this->nullableString($raw['email'] ?? null),
            whatsapp: $this->nullableString($raw['whatsapp'] ?? null),
            deviceToken: $this->nullableString($raw['device_token'] ?? null),
        );

        if (
            ! filled($account->userId)
            && ! filled($account->phone)
            && ! filled($account->email)
            && ! filled($account->whatsapp)
        ) {
            throw new AutomationTestException(
                "Test account {$accountId} has no recipients configured. Set phone/email/user_id/whatsapp env vars."
            );
        }

        return $account;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
