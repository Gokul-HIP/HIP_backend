<?php

namespace App\Console\Commands;

use App\Services\ChatbotUnpaidBookingExpiryService;
use Illuminate\Console\Command;

class ExpireUnpaidChatbotBookingsCommand extends Command
{
    protected $signature = 'chatbot:expire-unpaid-bookings';

    protected $description = 'Expire unpaid disease-chatbot slot holds past expires_at and release the booking.';

    public function handle(ChatbotUnpaidBookingExpiryService $expiry): int
    {
        $result = $expiry->expireDue();

        $this->info(sprintf(
            'Expired %d chatbot booking(s); processed=%d reconciled=%d skipped paid=%d in-flight=%d api=%d other=%d%s.',
            $result['expired'],
            $result['processed'] ?? 0,
            $result['reconciled'] ?? 0,
            $result['skipped_paid'],
            $result['skipped_in_flight'],
            $result['skipped_api'] ?? 0,
            $result['skipped_other'],
            ! empty($result['stopped_early']) ? ' (stopped early)' : ''
        ));

        return self::SUCCESS;
    }
}
