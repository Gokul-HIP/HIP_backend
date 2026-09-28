<?php

namespace App\Modules\Automation\Console\Commands;

use App\Modules\Automation\Services\PendingPaymentDetector;
use Illuminate\Console\Command;

class DispatchPendingPaymentsCommand extends Command
{
    protected $signature = 'hospital-automation:dispatch-pending-payments';

    protected $description = 'Dispatch PaymentPending for invoices still pending after the 30-minute reminder window.';

    public function handle(PendingPaymentDetector $detector): int
    {
        $result = $detector->detectAndDispatch();

        $this->info(sprintf(
            'Dispatched PaymentPending %d time(s); skipped %d invoice(s) without hospital scope.',
            $result['dispatched'],
            $result['skipped']
        ));

        return self::SUCCESS;
    }
}
