<?php

namespace App\Modules\Automation\Support;

/**
 * Describes one wallet balance change. Hospital scope must come from the
 * record that caused the change, not from the patient's latest booking.
 */
final class RewardPointsContext
{
    public function __construct(
        public string $changeType,
        public string $source,
        public ?int $hospitalId = null,
        public ?int $invoiceId = null,
        public ?int $transactionId = null,
        public ?string $bookingSource = null,
        public int|string|null $bookingId = null,
        /**
         * Coins awarded by the originating payment, copied from the amount
         * persisted for that earning. Null for redemption, adjustment, and any
         * earning that has no persisted payment award. This is not the wallet
         * delta and is never a redeemed amount.
         */
        public ?int $coinsEarned = null,
    ) {}
}
