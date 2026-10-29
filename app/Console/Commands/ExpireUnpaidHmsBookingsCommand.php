<?php

namespace App\Console\Commands;

use App\Services\Hms\HmsBookingExpiryService;
use Illuminate\Console\Command;

class ExpireUnpaidHmsBookingsCommand extends Command
{
    protected $signature = 'hms:bookings:expire-unpaid';

    protected $description = 'Expire unpaid HMS bookings whose hold has ended and release their reservations.';

    public function handle(HmsBookingExpiryService $expiry): int
    {
        $count = $expiry->expireDue();
        $this->info("Expired {$count} HMS booking(s).");

        return self::SUCCESS;
    }
}
