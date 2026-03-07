<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Api\PaymentApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPendingPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:pending-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notifications for invoices that are pending; runs every 30 minutes.';

    protected PaymentApiService $paymentApiService;

    public function __construct(PaymentApiService $paymentApiService)
    {
        parent::__construct();
        $this->paymentApiService = $paymentApiService;
    }

    public function handle()
    {
        $threshold = Carbon::now()->subMinutes(30);

        $invoices = Invoice::where('status', 'pending')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_reminder_sent_at')
                  ->orWhere('last_reminder_sent_at', '<=', $threshold);
            })
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No pending invoices require reminders.');
            return 0;
        }

        foreach ($invoices as $invoice) {
            $sent = $this->paymentApiService->sendInvoiceNotification($invoice, true, null);
            if ($sent) {
                $this->info("Reminder sent for invoice {$invoice->id}");
            } else {
                $this->warn("Could not send reminder for invoice {$invoice->id}");
            }
        }

        return 0;
    }
}
