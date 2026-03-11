<?php

namespace App\Services\Referral;

use App\Models\HIPUser;

class ReferralMemberResolver
{
    public function resolve(?string $memberIdInput, ?string $phoneNumberInput): array
    {
        $memberIdInput = $this->cleanText($memberIdInput);
        $phoneNumberInput = $this->cleanText($phoneNumberInput);

        $member = null;

        if ($memberIdInput) {
            $member = $this->findByMemberId($memberIdInput);
        }

        if (!$member && !$memberIdInput && $phoneNumberInput) {
            $member = $this->findByPhoneNumber($phoneNumberInput);
        }

        return [
            'member' => $member,
            'member_user_id' => $member?->id,
            'stored_member_id' => $member?->hip_id ?? $memberIdInput,
        ];
    }

    private function findByMemberId(string $memberIdInput): ?HIPUser
    {
        return HIPUser::query()
            ->where('hip_id', $memberIdInput)
            ->when(
                ctype_digit($memberIdInput),
                fn ($query) => $query->orWhereKey((int) $memberIdInput)
            )
            ->first();
    }

    private function findByPhoneNumber(string $phoneNumberInput): ?HIPUser
    {
        $digits = preg_replace('/\D+/', '', $phoneNumberInput) ?: '';
        $lastTenDigits = strlen($digits) > 10 ? substr($digits, -10) : $digits;
        $candidates = array_values(array_unique(array_filter([
            $phoneNumberInput,
            $digits,
            $lastTenDigits,
        ])));

        if ($candidates === []) {
            return null;
        }

        return HIPUser::query()
            ->whereIn('mobile_num', $candidates)
            ->first();
    }

    private function cleanText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
