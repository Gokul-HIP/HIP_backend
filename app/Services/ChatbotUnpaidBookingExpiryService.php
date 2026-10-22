<?php

namespace App\Services;

use App\Models\DiseaseChatbotBooking;
use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Models\RazorpayPayment;
use App\Models\Transactions;
use App\Services\Api\PaymentApiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotUnpaidBookingExpiryService
{
    public function __construct(
        protected DoctorBookingStatusService $bookingStatus,
        protected PaymentApiService $paymentApi,
        protected NotificationService $notifications,
    ) {}

    public function maxLinksPerRun(): int
    {
        return max(1, (int) config('chatbot.expire_max_links_per_run', 20));
    }

    public function maxRunSeconds(): int
    {
        return max(1, (int) config('chatbot.expire_max_run_seconds', 40));
    }

    /**
     * @return array{
     *     expired: int,
     *     skipped_paid: int,
     *     skipped_in_flight: int,
     *     skipped_other: int,
     *     reconciled: int,
     *     skipped_api: int,
     *     stopped_early: bool,
     *     processed: int
     * }
     */
    public function expireDue(?Carbon $now = null): array
    {
        $now = $now ?? now();
        $startedAt = microtime(true);
        $maxLinks = $this->maxLinksPerRun();
        $maxSeconds = $this->maxRunSeconds();
        $counts = [
            'expired' => 0,
            'skipped_paid' => 0,
            'skipped_in_flight' => 0,
            'skipped_other' => 0,
            'reconciled' => 0,
            'skipped_api' => 0,
            'stopped_early' => false,
            'processed' => 0,
        ];

        DiseaseChatbotBooking::query()
            ->where('status', DiseaseChatbotBooking::STATUS_PENDING_PAYMENT)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->orderBy('id')
            ->chunkById(min(50, $maxLinks), function ($rows) use (&$counts, $startedAt, $maxLinks, $maxSeconds) {
                foreach ($rows as $link) {
                    if ($counts['processed'] >= $maxLinks) {
                        $counts['stopped_early'] = true;

                        return false;
                    }

                    if ((microtime(true) - $startedAt) >= $maxSeconds) {
                        $counts['stopped_early'] = true;
                        Log::info('Chatbot expire run stopped after time budget', [
                            'processed' => $counts['processed'],
                            'max_run_seconds' => $maxSeconds,
                        ]);

                        return false;
                    }

                    $result = $this->expireOne($link);
                    $counts[$result] = ($counts[$result] ?? 0) + 1;
                    $counts['processed']++;
                }

                if ($counts['processed'] >= $maxLinks) {
                    $counts['stopped_early'] = true;

                    return false;
                }

                return true;
            });

        return $counts;
    }

    /**
     * @return 'expired'|'skipped_paid'|'skipped_in_flight'|'skipped_other'|'reconciled'|'skipped_api'
     */
    public function expireOne(DiseaseChatbotBooking $link): string
    {
        $link->refresh();

        if ($link->status !== DiseaseChatbotBooking::STATUS_PENDING_PAYMENT) {
            return 'skipped_other';
        }

        if ($link->expires_at === null || $link->expires_at->gte(now())) {
            return 'skipped_other';
        }

        $booking = DoctorBooking::query()->find($link->doctor_booking_id);
        if (! $booking) {
            return $this->markExpiredOnly($link);
        }

        if (
            $booking->payment_status === 'paid'
            || $booking->status === DoctorBooking::STATUS_CONFIRMED
        ) {
            $link->status = DiseaseChatbotBooking::STATUS_CONFIRMED;
            $link->save();

            return 'skipped_paid';
        }

        $invoiceId = $link->invoice_id ?: $booking->invoice_id;
        $reconcile = $this->reconcileRazorpayBeforeExpire($link, $booking, $invoiceId ? (int) $invoiceId : null);

        if ($reconcile === 'reconciled') {
            return 'reconciled';
        }
        if ($reconcile === 'authorized') {
            return 'skipped_in_flight';
        }
        if ($reconcile === 'skip_api') {
            return 'skipped_api';
        }
        // 'expire' or 'no_order' → continue to expire

        return DB::transaction(function () use ($link, $booking, $invoiceId) {
            /** @var DiseaseChatbotBooking|null $locked */
            $locked = DiseaseChatbotBooking::query()
                ->whereKey($link->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== DiseaseChatbotBooking::STATUS_PENDING_PAYMENT) {
                return 'skipped_other';
            }

            if ($locked->expires_at === null || $locked->expires_at->gte(now())) {
                return 'skipped_other';
            }

            /** @var DoctorBooking|null $lockedBooking */
            $lockedBooking = DoctorBooking::query()
                ->whereKey($locked->doctor_booking_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBooking) {
                $locked->status = DiseaseChatbotBooking::STATUS_EXPIRED;
                $locked->save();

                return 'expired';
            }

            if (
                $lockedBooking->payment_status === 'paid'
                || $lockedBooking->status === DoctorBooking::STATUS_CONFIRMED
            ) {
                $locked->status = DiseaseChatbotBooking::STATUS_CONFIRMED;
                $locked->save();

                return 'skipped_paid';
            }

            $resolvedInvoiceId = $invoiceId ?: $locked->invoice_id ?: $lockedBooking->invoice_id;
            if ($resolvedInvoiceId) {
                $inFlight = RazorpayPayment::query()
                    ->where('invoice_id', $resolvedInvoiceId)
                    ->whereIn('payment_status', ['authorized', 'captured'])
                    ->exists();

                if ($inFlight) {
                    return 'skipped_in_flight';
                }
            }

            $this->bookingStatus->cancelSilently($lockedBooking->fresh(), 'chatbot_unpaid_expired');

            if ($resolvedInvoiceId) {
                /** @var Invoice|null $invoice */
                $invoice = Invoice::query()->whereKey($resolvedInvoiceId)->lockForUpdate()->first();
                if ($invoice && (string) $invoice->status === 'pending') {
                    $invoice->status = 'cancelled';
                    $invoice->save();
                }

                Transactions::query()
                    ->where('invoice_id', $resolvedInvoiceId)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);

                RazorpayPayment::query()
                    ->where('invoice_id', $resolvedInvoiceId)
                    ->where('payment_status', 'created')
                    ->update(['payment_status' => 'superseded']);
            }

            $locked->status = DiseaseChatbotBooking::STATUS_EXPIRED;
            $locked->save();

            Log::info('Chatbot unpaid booking expired', [
                'disease_chatbot_booking_id' => $locked->id,
                'doctor_booking_id' => $locked->doctor_booking_id,
                'invoice_id' => $resolvedInvoiceId,
            ]);

            return 'expired';
        });
    }

    /**
     * Server-to-server reconcile against GET /v1/orders/{id}/payments before expiring.
     *
     * @return 'reconciled'|'authorized'|'expire'|'skip_api'|'no_order'
     */
    private function reconcileRazorpayBeforeExpire(
        DiseaseChatbotBooking $link,
        DoctorBooking $booking,
        ?int $invoiceId
    ): string {
        if (! $invoiceId) {
            return 'expire';
        }

        $orderId = RazorpayPayment::query()
            ->where('invoice_id', $invoiceId)
            ->whereNotNull('razorpay_order_id')
            ->orderByDesc('id')
            ->value('razorpay_order_id');

        if (! is_string($orderId) || $orderId === '') {
            return 'no_order';
        }

        $keyId = env('RAZORPAY_KEY_ID') ?? env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_KEY_SECRET') ?? env('RAZORPAY_SECRET');

        if (! $keyId || ! $keySecret) {
            return $this->apiFailureDecision($link, $booking, $invoiceId, $orderId, 'missing_credentials');
        }

        try {
            $response = Http::withBasicAuth((string) $keyId, (string) $keySecret)
                ->acceptJson()
                ->timeout(8)
                ->connectTimeout(3)
                ->get('https://api.razorpay.com/v1/orders/'.$orderId.'/payments');
        } catch (Throwable $e) {
            return $this->apiFailureDecision($link, $booking, $invoiceId, $orderId, $e->getMessage());
        }

        if (! $response->successful()) {
            return $this->apiFailureDecision(
                $link,
                $booking,
                $invoiceId,
                $orderId,
                'http_'.$response->status()
            );
        }

        $items = $response->json('items');
        if (! is_array($items)) {
            $items = [];
        }

        $captured = null;
        $hasAuthorized = false;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? '');
            if ($status === 'captured' && $captured === null) {
                $captured = $item;
            }
            if ($status === 'authorized') {
                $hasAuthorized = true;
            }
        }

        if ($captured !== null) {
            $paymentId = (string) ($captured['id'] ?? '');
            if ($paymentId === '') {
                return 'skip_api';
            }

            try {
                $this->paymentApi->finalizeServerCapturedPayment(
                    $invoiceId,
                    $orderId,
                    $paymentId,
                    $captured
                );
            } catch (Throwable $e) {
                Log::warning('Chatbot reconcile finalize failed', [
                    'disease_chatbot_booking_id' => (int) $link->id,
                    'doctor_booking_id' => (int) $booking->id,
                    'invoice_id' => $invoiceId,
                    'razorpay_order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);

                return $this->apiFailureDecision($link, $booking, $invoiceId, $orderId, 'finalize_failed');
            }

            DiseaseChatbotBooking::query()
                ->whereKey($link->id)
                ->whereIn('status', [
                    DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
                    DiseaseChatbotBooking::STATUS_EXPIRED,
                ])
                ->update(['status' => DiseaseChatbotBooking::STATUS_CONFIRMED]);

            $this->notifyPatientPaymentConfirmed($booking, $invoiceId);

            Log::info('Chatbot unpaid booking reconciled from Razorpay capture', [
                'disease_chatbot_booking_id' => (int) $link->id,
                'doctor_booking_id' => (int) $booking->id,
                'invoice_id' => $invoiceId,
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
            ]);

            return 'reconciled';
        }

        if ($hasAuthorized) {
            return 'authorized';
        }

        return 'expire';
    }

    /**
     * @return 'skip_api'|'expire'
     */
    private function apiFailureDecision(
        DiseaseChatbotBooking $link,
        DoctorBooking $booking,
        int $invoiceId,
        string $orderId,
        string $reason
    ): string {
        $maxMinutes = (int) config('chatbot.reconcile_max_minutes', 60);
        $createdAt = $booking->created_at ?? $link->created_at;
        $pastMax = $createdAt instanceof Carbon
            && $createdAt->copy()->addMinutes($maxMinutes)->lte(now());

        if ($pastMax) {
            Log::warning('Chatbot reconcile API failed past max minutes; expiring', [
                'disease_chatbot_booking_id' => (int) $link->id,
                'doctor_booking_id' => (int) $booking->id,
                'invoice_id' => $invoiceId,
                'razorpay_order_id' => $orderId,
                'reason' => $reason,
                'reconcile_max_minutes' => $maxMinutes,
            ]);

            return 'expire';
        }

        Log::info('Chatbot reconcile API failed; skipping expire this round', [
            'disease_chatbot_booking_id' => (int) $link->id,
            'doctor_booking_id' => (int) $booking->id,
            'invoice_id' => $invoiceId,
            'razorpay_order_id' => $orderId,
            'reason' => $reason,
        ]);

        return 'skip_api';
    }

    private function notifyPatientPaymentConfirmed(DoctorBooking $booking, int $invoiceId): void
    {
        $userId = $booking->member_id;
        if (! is_string($userId) || $userId === '') {
            return;
        }

        $this->notifications->notifyUser(
            $userId,
            'Payment confirmed',
            'Your consultation booking payment was received and the appointment is confirmed.',
            [
                'type' => 'chatbot_payment_reconciled',
                'invoice_id' => $invoiceId,
                'doctor_booking_id' => (int) $booking->id,
            ]
        );
    }

    /**
     * @return 'expired'
     */
    private function markExpiredOnly(DiseaseChatbotBooking $link): string
    {
        return DB::transaction(function () use ($link) {
            $locked = DiseaseChatbotBooking::query()->whereKey($link->id)->lockForUpdate()->first();
            if ($locked && $locked->status === DiseaseChatbotBooking::STATUS_PENDING_PAYMENT) {
                $locked->status = DiseaseChatbotBooking::STATUS_EXPIRED;
                $locked->save();
            }

            return 'expired';
        });
    }
}
