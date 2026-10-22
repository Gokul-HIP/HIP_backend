<?php

namespace App\Console\Commands;

use App\Models\DiseaseChatbotBooking;
use App\Models\HIPUser;
use App\Models\RazorpayPayment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListRefundRequiredChatbotBookingsCommand extends Command
{
    protected $signature = 'chatbot:refund-required
                            {--no-notify : List only; do not call NotificationService}';

    protected $description = 'List disease_chatbot_bookings with status refund_required and notify admins once (ids only).';

    public function handle(NotificationService $notifications): int
    {
        if (! Schema::hasTable('disease_chatbot_bookings')) {
            $this->warn('disease_chatbot_bookings table not found.');

            return self::SUCCESS;
        }

        $listQuery = DiseaseChatbotBooking::query()
            ->where('status', DiseaseChatbotBooking::STATUS_REFUND_REQUIRED)
            ->with('invoice')
            ->orderBy('id');

        $links = $listQuery->get();

        if ($links->isEmpty()) {
            $this->info('No refund_required chatbot booking links.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($links as $link) {
            $paymentId = RazorpayPayment::query()
                ->where('invoice_id', $link->invoice_id)
                ->orderByDesc('id')
                ->value('razorpay_payment_id');

            $amount = $link->invoice?->total_amount;
            if ($amount === null && $link->invoice_id) {
                $amount = RazorpayPayment::query()
                    ->where('invoice_id', $link->invoice_id)
                    ->orderByDesc('id')
                    ->value('amount_paid');
            }

            $rows[] = [
                'link_id' => (int) $link->id,
                'invoice_id' => $link->invoice_id !== null ? (int) $link->invoice_id : null,
                'booking_id' => (int) $link->doctor_booking_id,
                'amount' => $amount !== null ? (float) $amount : null,
                'payment_id' => $paymentId,
                'created_at' => optional($link->created_at)?->toDateTimeString(),
                'refund_notified_at' => optional($link->refund_notified_at)?->toDateTimeString(),
            ];
        }

        $this->table(
            ['link_id', 'invoice_id', 'booking_id', 'amount', 'payment_id', 'created_at', 'refund_notified_at'],
            $rows
        );

        $this->info(sprintf('Total refund_required: %d', count($rows)));

        Log::info('chatbot:refund-required listed', [
            'count' => count($rows),
            'link_ids' => array_column($rows, 'link_id'),
            'invoice_ids' => array_column($rows, 'invoice_id'),
        ]);

        if (! $this->option('no-notify')) {
            $this->notifyUnnotifiedAdmins($notifications);
        }

        return self::SUCCESS;
    }

    private function notifyUnnotifiedAdmins(NotificationService $notifications): void
    {
        $unnotified = DiseaseChatbotBooking::query()
            ->where('status', DiseaseChatbotBooking::STATUS_REFUND_REQUIRED)
            ->whereNull('refund_notified_at')
            ->orderBy('id')
            ->get();

        if ($unnotified->isEmpty()) {
            $this->info('No new refund_required links to notify.');

            return;
        }

        $admins = $this->adminRecipients();

        if ($admins->isEmpty()) {
            $this->warn('No active super-admin / super-admin-hip users to notify.');
            Log::warning('chatbot:refund-required notify skipped; no admin recipients', [
                'count' => $unnotified->count(),
            ]);

            return;
        }

        $linkIds = $unnotified->pluck('id')->map(fn ($id) => (int) $id)->all();
        $invoiceIds = $unnotified->pluck('invoice_id')->filter()->map(fn ($id) => (int) $id)->values()->all();

        $title = 'Chatbot refund required';
        $body = sprintf(
            '%d chatbot booking payment(s) need a refund (slot taken after late capture).',
            $unnotified->count()
        );
        $data = [
            'type' => 'chatbot_refund_required',
            'count' => $unnotified->count(),
            'link_ids' => $linkIds,
            'invoice_ids' => $invoiceIds,
        ];

        $sent = 0;
        foreach ($admins as $admin) {
            $notifications->notifyUser((string) $admin->id, $title, $body, $data);
            $sent++;
        }

        DiseaseChatbotBooking::query()
            ->whereIn('id', $linkIds)
            ->whereNull('refund_notified_at')
            ->update(['refund_notified_at' => now()]);

        $this->info(sprintf(
            'Notified %d admin user(s) for %d new link(s) via NotificationService::notifyUser.',
            $sent,
            count($linkIds)
        ));
        Log::info('chatbot:refund-required admins notified', [
            'admin_count' => $sent,
            'link_count' => count($linkIds),
            'link_ids' => $linkIds,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, HIPUser>
     */
    private function adminRecipients()
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('roles')) {
            return collect();
        }

        return HIPUser::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['super-admin-hip', 'super-admin'])
                    ->where('guard_name', 'filament');
            })
            ->get(['id']);
    }
}
