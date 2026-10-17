<?php

namespace App\Services;

use App\Exceptions\ChatbotFlowException;
use App\Models\DiseaseChatbotBooking;
use App\Models\DiseaseChatbotSession;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Invoice;
use App\Services\Api\BookingApiService;
use App\Services\Api\PaymentApiService;
use App\Services\Pharmacy\PharmacyOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatbotBookingService
{
    public function __construct(
        protected BookingApiService $bookingApi,
        protected PaymentApiService $paymentApi,
        protected DoctorAvailabilityService $availability,
        protected PharmacyOrderService $pharmacyOrderService,
        protected InvoiceFeeCalculator $feeCalculator,
    ) {}

    public function paymentExpiresMinutes(): int
    {
        return max(1, (int) config(
            'chatbot.payment_hold_minutes',
            config('chatbot.slot_hold_minutes', 15)
        ));
    }

    public function expiresInSeconds(?\DateTimeInterface $expiresAt): int
    {
        if ($expiresAt === null) {
            return 0;
        }

        return max(0, $expiresAt->getTimestamp() - now()->getTimestamp());
    }

    /**
     * Whether the selected slot is free for this doctor/date (cancelled bookings do not occupy).
     */
    public function slotIsFree(string $doctorId, int $hospitalId, string $date, string $slot): bool
    {
        $slotsByDate = $this->availability->slotsForDoctor($doctorId, $hospitalId);
        $available = $slotsByDate[$date] ?? [];
        $wanted = $this->availability->parseToMinutes($slot);
        if ($wanted === null) {
            return false;
        }
        foreach ($available as $open) {
            if ($this->availability->parseToMinutes($open) === $wanted) {
                return true;
            }
        }

        return false;
    }

    public function amountForOneCoin(): float
    {
        return max(0.01, (float) $this->paymentApi->amountForOneCoin());
    }

    /**
     * @return array{balance: int, max_usable: int, fee: float}
     */
    public function coinPreview(HIPUser $user, float $consultationFee): array
    {
        $balance = $this->walletBalance($user);
        $rate = $this->amountForOneCoin();
        $maxByFee = $consultationFee > 0 ? (int) floor($consultationFee / $rate) : 0;
        $maxUsable = max(0, min($balance, $maxByFee));

        return [
            'balance' => $balance,
            'max_usable' => $maxUsable,
            'fee' => $consultationFee,
        ];
    }

    public function walletBalance(HIPUser $user): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('coins')) {
            return 0;
        }

        $person = \App\Models\Persons::query()
            ->where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->first()
            ?? \App\Models\Persons::query()->where('hip_user_id', $user->id)->first();

        if (! $person) {
            return 0;
        }

        $wallet = \App\Models\Coins::query()
            ->where('person_id', $person->parent_id ?? $person->id)
            ->first();

        return (int) ($wallet?->coins ?? 0);
    }

    /**
     * @return array{
     *     consultation_fee: float,
     *     coin_discount: float,
     *     service_charge: float,
     *     taxes: float,
     *     total_payable: float,
     *     coins_applied: int
     * }
     */
    public function feeBreakdown(float $consultationFee, int $coinsUsed): array
    {
        $coinsUsed = max(0, $coinsUsed);
        $coinsValue = round($coinsUsed * $this->amountForOneCoin(), 2);
        $row = $this->feeCalculator->bookingBreakdown($consultationFee, $coinsValue);

        return [
            'consultation_fee' => (float) $row['original_amount'],
            'coin_discount' => (float) $row['discount_amount'],
            'service_charge' => (float) $row['service_charges'],
            'taxes' => (float) $row['gst_amount'],
            'total_payable' => (float) $row['invoice_total'],
            'coins_applied' => $coinsUsed,
        ];
    }

    public function existingLink(DiseaseChatbotSession $session): ?DiseaseChatbotBooking
    {
        return DiseaseChatbotBooking::query()
            ->where('session_id', $session->id)
            ->first();
    }

    /**
     * Create (or return existing) booking + payment payload for the chatbot session.
     *
     * @return array{link: DiseaseChatbotBooking, booking: DoctorBooking, payment: ?array, free: bool}
     */
    public function book(DiseaseChatbotSession $session, HIPUser $user): array
    {
        $existing = $this->existingLink($session);
        if ($existing && $this->linkIsReusable($existing)) {
            $booking = DoctorBooking::query()->find($existing->doctor_booking_id);
            if ($booking) {
                $payment = $this->paymentPayloadForBooking($booking);

                return [
                    'link' => $existing,
                    'booking' => $booking,
                    'payment' => $payment,
                    'free' => ! (bool) ($payment['requires_payment'] ?? true),
                    'idempotent' => true,
                ];
            }
        }

        $doctorId = (string) data_get($session->context, 'selected_doctor_id', '');
        $date = (string) data_get($session->context, 'selected_date', '');
        $slot = (string) data_get($session->context, 'selected_slot', '');
        $masterId = (int) data_get($session->context, 'selected_speciality.id', 0);
        $coinsUsed = max(0, (int) data_get($session->context, 'coins_used', 0));
        $personId = (string) ($session->person_id ?? '');

        if ($doctorId === '' || $date === '' || $slot === '' || $masterId <= 0) {
            throw new InvalidBookingStateException('Booking details are incomplete. Please start over.');
        }

        $this->assertPersonAuthorized($user, $personId);

        return DB::transaction(function () use ($session, $user, $doctorId, $date, $slot, $masterId, $coinsUsed, $personId) {
            $again = $this->existingLink($session->fresh());
            if ($again && $this->linkIsReusable($again)) {
                $booking = DoctorBooking::query()->findOrFail($again->doctor_booking_id);
                $payment = $this->paymentPayloadForBooking($booking);

                return [
                    'link' => $again,
                    'booking' => $booking,
                    'payment' => $payment,
                    'free' => ! (bool) ($payment['requires_payment'] ?? true),
                    'idempotent' => true,
                ];
            }

            Doctor::query()->whereKey($doctorId)->lockForUpdate()->firstOrFail();

            $slotsByDate = $this->availability->slotsForDoctor($doctorId, (int) $session->hospital_id);
            $available = $slotsByDate[$date] ?? [];
            $wanted = $this->availability->parseToMinutes($slot);
            $open = false;
            $canonical = $slot;
            foreach ($available as $openSlot) {
                if ($this->availability->parseToMinutes($openSlot) === $wanted) {
                    $open = true;
                    $canonical = $openSlot;
                    break;
                }
            }
            if (! $open) {
                throw new SlotTakenException('That time was just taken. Please pick another.');
            }

            $results = (array) data_get($session->context, 'results', []);
            $diseaseName = (string) data_get($results, '0.name', 'Consultation');
            $symptoms = array_values((array) data_get($session->context, 'symptoms', []));
            $types = (array) config('chatbot.appointment_types', ['In-Clinic']);
            $appointmentType = (string) ($types[0] ?? 'In-Clinic');

            $request = Request::create('/internal/chatbot-doctor-booking', 'POST', [
                'patient_id' => $personId,
                'doctor_id' => $doctorId,
                'branch_id' => (int) $session->hospital_id,
                'hospital_id' => (int) $session->hospital_id,
                'department_id' => $masterId,
                'appointment_type' => $appointmentType,
                'booking_date' => $date,
                'required_time_slots' => [$canonical],
                'reason_of_visit' => $diseaseName,
                'message' => implode(', ', $symptoms),
                'is_follow_up' => false,
                'is_online_payment' => true,
                'is_coins_applied' => $coinsUsed > 0,
                'coins_used' => $coinsUsed,
            ]);

            $result = $this->bookingApi->doctorBooking($request, (string) $user->id);
            /** @var DoctorBooking $booking */
            $booking = $result['booking'];
            $payment = $result['payment'] ?? null;
            $free = is_array($payment) && ($payment['requires_payment'] ?? true) === false;

            $linkAttrs = [
                'session_id' => $session->id,
                'doctor_booking_id' => (int) $booking->id,
                'invoice_id' => $booking->invoice_id ? (int) $booking->invoice_id : null,
                'status' => $free
                    ? DiseaseChatbotBooking::STATUS_CONFIRMED
                    : DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
                'expires_at' => now()->addMinutes($this->paymentExpiresMinutes()),
            ];

            // One link row per session: reuse the row after expiry / cancel / refund_required.
            if ($again) {
                $again->update($linkAttrs);
                $link = $again->fresh();
            } else {
                $link = DiseaseChatbotBooking::query()->create($linkAttrs);
            }

            $context = $session->fresh()->context ?? [];
            $context['booking_id'] = (int) $booking->id;
            $context['invoice_id'] = $booking->invoice_id ? (int) $booking->invoice_id : null;
            $context['chatbot_booking_id'] = (int) $link->id;
            $context['payment'] = $payment;
            $session->update(['context' => $context]);

            return [
                'link' => $link->fresh(),
                'booking' => $booking->fresh(),
                'payment' => $payment,
                'free' => $free,
                'idempotent' => false,
            ];
        });
    }

    protected function linkIsReusable(DiseaseChatbotBooking $link): bool
    {
        return in_array($link->status, [
            DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
            DiseaseChatbotBooking::STATUS_CONFIRMED,
        ], true);
    }

    /**
     * Re-send payment payload for an existing unpaid invoice (reuses pending Razorpay order when present).
     *
     * @return array<string, mixed>
     */
    public function refreshPaymentPayload(DoctorBooking $booking): array
    {
        if ((float) ($booking->total_amount ?? 0) <= 0) {
            return [
                'requires_payment' => false,
                'invoice_id' => $booking->invoice_id ? (int) $booking->invoice_id : null,
                'payment_status' => 'paid',
            ];
        }

        $order = $this->paymentApi->createDoctorBookingRazorpayOrder($booking->fresh());

        return [
            'requires_payment' => true,
            'invoice_id' => (int) $order['invoice_id'],
            'order_id' => (string) $order['order_id'],
            'razorpay_key' => (string) $order['razorpay_key'],
            'amount' => (int) $order['amount'],
            'transaction_id' => (string) $order['transaction_id'],
            'currency' => 'INR',
            'payment_status' => 'pending',
        ];
    }

    /**
     * @return array{paid: bool, booking: ?DoctorBooking, invoice: ?Invoice}
     */
    public function readPaymentStatus(DiseaseChatbotSession $session): array
    {
        $link = $this->existingLink($session);
        if (! $link) {
            return ['paid' => false, 'booking' => null, 'invoice' => null];
        }

        $booking = DoctorBooking::query()->find($link->doctor_booking_id);
        $invoice = $link->invoice_id
            ? Invoice::query()->find($link->invoice_id)
            : ($booking?->invoice_id ? Invoice::query()->find($booking->invoice_id) : null);

        $paid = $booking
            && ($booking->payment_status === 'paid' || $booking->status === DoctorBooking::STATUS_CONFIRMED)
            && ($invoice === null || (string) $invoice->status === 'completed' || (float) ($booking->total_amount ?? 0) <= 0);

        if ($booking && $booking->payment_status === 'paid') {
            $paid = true;
        }

        return ['paid' => $paid, 'booking' => $booking, 'invoice' => $invoice, 'link' => $link];
    }

    public function assertPersonAuthorized(HIPUser $user, string $personId): void
    {
        if ($personId === '') {
            throw new HttpException(403, 'A patient must be selected before booking.');
        }
        $family = $this->pharmacyOrderService->familyPersonIds($user);
        if (! in_array($personId, $family, true)) {
            throw new HttpException(403, 'You can only book for yourself or a family member.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function paymentPayloadForBooking(DoctorBooking $booking): ?array
    {
        if ((float) ($booking->total_amount ?? 0) <= 0 || $booking->payment_status === 'paid') {
            return [
                'requires_payment' => false,
                'invoice_id' => $booking->invoice_id ? (int) $booking->invoice_id : null,
                'payment_status' => (string) ($booking->payment_status ?? 'paid'),
            ];
        }

        return $this->refreshPaymentPayload($booking);
    }
}

class SlotTakenException extends \RuntimeException
{
}

class InvalidBookingStateException extends \RuntimeException
{
}
