<?php

namespace App\Services\Hms;

use App\Exceptions\HmsClientException;
use App\Models\DiagnosticPackage;
use App\Models\DiagnosticTestBooking;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\HmsBooking;
use App\Models\HmsIntegration;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\SecondOpinion;
use App\Models\Transactions;
use App\Services\BookingPricingPreviewService;
use App\Services\DoctorBookingStatusService;
use App\Services\MembershipEntitlementService;
use App\Services\RewardTierService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Creates pending HMS bookings without calling Razorpay or debiting coins. */
class HmsBookingService
{
    public function __construct(
        private readonly HmsHospitalResolver $hospitals,
        private readonly HmsPriceQuoteService $catalog,
        private readonly HmsPatientProvisioner $patients,
        private readonly BookingPricingPreviewService $preview,
        private readonly MembershipEntitlementService $membership,
        private readonly RewardTierService $rewards,
        private readonly DoctorBookingStatusService $doctorStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_status: int, body: array<string, mixed>}
     */
    public function book(HmsIntegration $integration, string $bookingType, array $payload): array
    {
        $hash = HmsPayloadHash::make($payload);

        try {
            $result = DB::transaction(function () use ($integration, $bookingType, $payload, $hash) {
                HmsIntegration::query()->whereKey($integration->getKey())->lockForUpdate()->first();
                $existing = $this->lockedBooking($integration, (string) $payload['hms_booking_id']);
                if ($existing) {
                    return $this->replay($existing, $hash);
                }

                return $this->create($integration, $bookingType, $payload, $hash);
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            $existing = $this->findBooking($integration, (string) $payload['hms_booking_id']);
            if (! $existing) {
                throw $exception;
            }

            $result = $this->replay($existing, $hash);
        }

        return [
            'http_status' => $result['created'] ? 201 : 200,
            'body' => $this->present($result['booking'], (bool) $result['patient_created']),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{booking: HmsBooking, created: bool, patient_created: bool}
     */
    private function create(HmsIntegration $integration, string $bookingType, array $payload, string $hash): array
    {
        $hospital = $this->hospitals->resolve($integration, (string) $payload['hospital_code']);
        $patient = $this->patients->provision(
            $integration,
            (array) $payload['patient'],
            (int) $hospital->getKey(),
            is_string(data_get($payload, 'primary_patient.name')) ? (string) data_get($payload, 'primary_patient.name') : null
        );
        $fee = round((float) $payload['original_fee'], 2);
        $hmsPayable = round((float) $payload['payable_amount'], 2);
        $personId = (string) $patient['person']->getKey();
        $hipUserId = (string) $patient['account']->getKey();

        $usageType = null;
        $usageBookingType = 'doctor_booking';
        $packageId = null;

        if ($bookingType === HmsBooking::TYPE_PACKAGE) {
            $package = $this->packageAt($integration, $hospital, (string) $payload['package_code']);
            $priced = clone $package;
            $priced->setAttribute('price', $fee);
            $priced->setAttribute('discount', null);
            $pricing = $this->preview->package($hipUserId, $priced, 'diagnostic', 0, 0, $personId);
            $meta = $this->membership->priceDiagnosticPackage(
                $hipUserId,
                $fee,
                (int) $package->getKey(),
                (int) $hospital->getKey(),
                'diagnostic',
                0,
                true,
                $personId
            );
            $local = $this->createPackageBooking($patient, $hospital, $package, $payload, $pricing['pricing']);
            $usageType = 'lab_test';
            $usageBookingType = 'diagnostic_test_booking';
            $packageId = (int) $package->getKey();
        } elseif ($bookingType === HmsBooking::TYPE_SECOND_OPINION) {
            $doctor = $this->doctorAt($integration, $hospital, (string) $payload['doctor_id']);
            $pricing = $this->preview->secondOpinion($hipUserId, $fee, 0, 0);
            $meta = $this->rewards->calculateBasePriceDiscount($hipUserId, $fee, null, true);
            $meta['membership_benefit_applied'] = false;
            $meta['subscription_id'] = null;
            $local = $this->createSecondOpinion($patient, $hospital, $doctor, $payload, $pricing['pricing']);
            $usageType = null;
            $usageBookingType = 'second_opinion';
        } else {
            $doctor = $this->doctorAt($integration, $hospital, (string) $payload['doctor_id']);
            $pricing = $this->preview->doctor($hipUserId, $fee, 0, 0, (int) $hospital->getKey(), [], true, $personId);
            $meta = $this->membership->priceDoctorConsultation($hipUserId, $fee, 0, (int) $hospital->getKey(), true, $personId);
            $local = $this->createDoctorBooking($patient, $hospital, $doctor, $payload, $pricing['pricing']);
            $usageType = 'consultation';
            $usageBookingType = 'doctor_booking';
        }

        $block = $pricing['pricing'];
        $expected = round((float) $block['total_payable'], 2);
        $mismatch = abs($expected - $hmsPayable) > 0.01;
        $confirmNow = ! $mismatch && $expected <= 0.0;
        $holdExpires = now()->addMinutes(max(1, (int) config('hms.pending_hold_minutes', 30)));

        $invoice = $this->invoiceFor($bookingType, $local, $personId);
        $transaction = $this->pendingTransaction($invoice);
        $local->update(['invoice_id' => $invoice->id]);

        $this->reserveBenefits(
            $hipUserId,
            $meta,
            $usageBookingType,
            (int) $local->getKey(),
            $usageType,
            $packageId,
            $confirmNow,
            $holdExpires
        );

        if ($confirmNow) {
            $this->confirmFree($bookingType, $local, $invoice, $transaction);
        }

        $hmsBooking = HmsBooking::query()->create([
            'integration_id' => $integration->getKey(),
            'hms_booking_id' => (string) $payload['hms_booking_id'],
            'booking_type' => $bookingType,
            'booking_id' => (int) $local->getKey(),
            'hip_user_id' => $hipUserId,
            'person_id' => $personId,
            'hospital_id' => (int) $hospital->getKey(),
            'status' => $confirmNow ? HmsBooking::STATUS_CONFIRMED : ($mismatch ? HmsBooking::STATUS_HELD : HmsBooking::STATUS_PENDING),
            'expected_amount' => $expected,
            'hms_payable_amount' => $hmsPayable,
            'price_mismatch' => $mismatch,
            'pricing_snapshot' => $block,
            'request_hash' => $hash,
            'invoice_id' => $invoice->id,
            'transaction_id' => $transaction->id,
            'hold_expires_at' => $holdExpires,
            'confirmed_at' => $confirmNow ? now() : null,
        ]);

        return [
            'booking' => $hmsBooking,
            'created' => true,
            'patient_created' => (bool) $patient['created'],
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function reserveBenefits(
        string $hipUserId,
        array $meta,
        string $usageBookingType,
        int $bookingId,
        ?string $usageType,
        ?int $packageId,
        bool $confirmNow,
        \DateTimeInterface $holdExpires,
    ): void {
        $membershipApplied = (bool) ($meta['membership_benefit_applied'] ?? false);
        if ($membershipApplied && $usageType && ! empty($meta['subscription_id'])) {
            $this->membership->reserveUsage(
                (int) $meta['subscription_id'],
                $usageType,
                $bookingId,
                $usageBookingType,
                $packageId
            );
            if ($confirmNow) {
                $this->membership->confirmUsage($bookingId, $usageBookingType, $usageType);
            }
        }

        if (! $membershipApplied && (float) ($meta['reward_discount'] ?? $meta['amount'] ?? 0) > 0) {
            $this->rewards->reserveDiscountUsage(
                $hipUserId,
                [
                    'amount' => $meta['reward_discount'] ?? $meta['amount'] ?? 0,
                    'tier_id' => $meta['reward_tier_id'] ?? $meta['tier_id'] ?? null,
                    'tier_generation' => $meta['reward_tier_generation'] ?? $meta['tier_generation'] ?? 0,
                    'limit' => $meta['reward_tier_limit'] ?? $meta['limit'] ?? 0,
                ],
                $usageBookingType,
                $bookingId,
                $confirmNow,
                $confirmNow ? null : $holdExpires
            );
        }
    }

    /**
     * @param  array{account: \App\Models\HIPUser, person: \App\Models\Persons, created: bool}  $patient
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $block
     */
    private function createDoctorBooking(array $patient, Hospital $hospital, Doctor $doctor, array $payload, array $block): DoctorBooking
    {
        $amounts = $this->amounts($block);

        return DoctorBooking::query()->create([
            'name' => (string) data_get($payload, 'patient.name'),
            'mobile_number' => HmsMobile::normalize((string) data_get($payload, 'patient.mobile')),
            'member_id' => $patient['account']->getKey(),
            'patient_id' => $patient['person']->getKey(),
            'relationship' => (string) data_get($payload, 'patient.relationship', 'Self'),
            'branch_id' => $hospital->getKey(),
            'hospital_id' => $hospital->getKey(),
            'doctor_id' => $doctor->getKey(),
            'consultation_type' => $this->consultationType($payload),
            'appointment_type' => $this->consultationType($payload) === 'Online' ? 'Online Consultation' : 'In-Clinic',
            'booking_date' => $payload['appointment_date'],
            'required_time_slots' => [(string) $payload['slot_time']],
            'message' => $payload['notes'] ?? null,
            'purpose' => $payload['notes'] ?? null,
            'is_coins_applied' => false,
            'coins_used' => 0,
            'consultation_fee' => $amounts['fee'],
            'service_charges' => $amounts['service'],
            'total_discount' => $amounts['discount'],
            'amount_after_discount' => $amounts['after'],
            'total_amount' => $amounts['total'],
            'is_online_payment' => true,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /**
     * @param  array{account: \App\Models\HIPUser, person: \App\Models\Persons, created: bool}  $patient
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $block
     */
    private function createSecondOpinion(array $patient, Hospital $hospital, Doctor $doctor, array $payload, array $block): SecondOpinion
    {
        $amounts = $this->amounts($block);

        return SecondOpinion::query()->create([
            'member_id' => $patient['account']->getKey(),
            'patient_id' => $patient['person']->getKey(),
            'patient_name' => (string) data_get($payload, 'patient.name'),
            'question_for_doctor' => $payload['notes'] ?? null,
            'branch_id' => $hospital->getKey(),
            'doctor_id' => $doctor->getKey(),
            'mode_of_consultation' => 'Online',
            'preferred_date' => $payload['appointment_date'],
            'preferred_time_slots' => [(string) $payload['slot_time']],
            'relationship' => (string) data_get($payload, 'patient.relationship', 'Self'),
            'is_coins_applied' => false,
            'coins_used' => 0,
            'consultation_fee' => $amounts['fee'],
            'service_charges' => $amounts['service'],
            'total_discount' => $amounts['discount'],
            'amount_after_discount' => $amounts['after'],
            'total_amount' => $amounts['total'],
            'is_online_payment' => true,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /**
     * @param  array{account: \App\Models\HIPUser, person: \App\Models\Persons, created: bool}  $patient
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $block
     */
    private function createPackageBooking(
        array $patient,
        Hospital $hospital,
        DiagnosticPackage $package,
        array $payload,
        array $block,
    ): DiagnosticTestBooking {
        $amounts = $this->amounts($block);
        $tests = is_array($package->lab_tests) ? $package->lab_tests : [];

        return DiagnosticTestBooking::query()->create([
            'name' => (string) data_get($payload, 'patient.name'),
            'mobile_number' => HmsMobile::normalize((string) data_get($payload, 'patient.mobile')),
            'member_id' => $patient['account']->getKey(),
            'patient_id' => $patient['person']->getKey(),
            'relationship' => (string) data_get($payload, 'patient.relationship', 'Self'),
            'branch_id' => $hospital->getKey(),
            'diagnostic_center_id' => $hospital->diagnostic_center_id,
            'package_id' => $package->getKey(),
            'package_type' => 'diagnostic',
            'test_type' => 'package',
            'test_items' => $tests,
            'sample_collection' => 'lab',
            'booking_date' => $payload['appointment_date'] ?? null,
            'required_time_slots' => filled($payload['slot_time'] ?? null) ? [(string) $payload['slot_time']] : [],
            'purpose' => $payload['notes'] ?? null,
            'status' => 'pending',
            'is_coins_applied' => false,
            'coins_used' => 0,
            'package_fee' => $amounts['fee'],
            'service_charges' => $amounts['service'],
            'total_discount' => $amounts['discount'],
            'amount_after_discount' => $amounts['after'],
            'total_amount' => $amounts['total'],
            'is_online_payment' => true,
            'payment_status' => 'pending',
        ]);
    }

    private function consultationType(array $payload): string
    {
        return strtolower((string) ($payload['consultation_type'] ?? 'in_person')) === 'online'
            ? 'Online'
            : 'In-Person';
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array{fee: float, discount: float, after: float, service: float, total: float}
     */
    private function amounts(array $block): array
    {
        $fee = round((float) ($block['original_fee'] ?? 0), 2);
        $discount = round(
            (float) data_get($block, 'reward_tier.discount_amount', 0)
            + (float) data_get($block, 'membership.discount_amount', 0)
            + (float) ($block['coin_discount'] ?? 0),
            2
        );

        return [
            'fee' => $fee,
            'discount' => $discount,
            'after' => round(max(0, $fee - $discount), 2),
            'service' => round((float) ($block['service_charge'] ?? 0), 2),
            'total' => round((float) ($block['total_payable'] ?? 0), 2),
        ];
    }

    private function invoiceFor(string $bookingType, Model $booking, string $personId): Invoice
    {
        $payments = app(\App\Services\Api\PaymentApiService::class);

        return match ($bookingType) {
            HmsBooking::TYPE_PACKAGE => $payments->createInvoiceForDiagnosticTestBooking($booking, $personId),
            HmsBooking::TYPE_SECOND_OPINION => $payments->createInvoiceForSecondOpinion($booking, $personId),
            default => $payments->createInvoiceForDoctorBooking($booking, $personId),
        };
    }

    private function pendingTransaction(Invoice $invoice): Transactions
    {
        return Transactions::query()->create([
            'invoice_id' => $invoice->id,
            'service_types' => $invoice->service_types,
            'invoice_details' => $invoice->invoice_details,
            'transaction_amount' => (float) $invoice->total_amount,
            'service_charges' => (float) ($invoice->service_charges ?? 0),
            'payment_gateway_charges' => 0,
            'discount_amount' => 0,
            'total_gst' => 0,
            'total_amount' => (float) $invoice->total_amount,
            'status' => 'pending',
            'payment_method' => null,
        ]);
    }

    private function confirmFree(string $bookingType, Model $booking, Invoice $invoice, Transactions $transaction): void
    {
        $invoice->update([
            'status' => 'completed',
            'payment_method' => 'free',
        ]);
        $transaction->update([
            'status' => 'completed',
            'payment_method' => 'free',
            'transaction_amount' => 0,
            'total_amount' => 0,
        ]);
        $booking->update(['payment_status' => 'paid']);
        if ($bookingType === HmsBooking::TYPE_DOCTOR && $booking instanceof DoctorBooking) {
            $this->doctorStatus->updateBookingStatus($booking->fresh(), 'confirmed', null, null, true);
        } else {
            $booking->update(['status' => 'confirmed']);
        }
        app(\App\Modules\Automation\Services\InvoiceGeneratedDispatcher::class)->dispatch($invoice->fresh());
    }

    private function doctorAt(HmsIntegration $integration, Hospital $hospital, string $doctorId): Doctor
    {
        $doctor = $this->catalog->resolveDoctor($integration, $doctorId);
        if (! $doctor) {
            throw new HmsClientException('not_found', 'Doctor was not found.', 404);
        }

        $hospitalIds = array_map('intval', $doctor->hospital_ids ?? []);
        if ($hospitalIds !== [] && ! in_array((int) $hospital->getKey(), $hospitalIds, true)) {
            throw new HmsClientException('not_found', 'Doctor was not found.', 404);
        }

        return $doctor;
    }

    private function packageAt(HmsIntegration $integration, Hospital $hospital, string $packageCode): DiagnosticPackage
    {
        $package = $this->catalog->resolvePackage($integration, $packageCode);
        $available = $package
            && $package->status === 'active'
            && $hospital->diagnostic_center_id
            && (int) $package->diagnostic_id === (int) $hospital->diagnostic_center_id;

        if (! $available) {
            throw new HmsClientException('not_found', 'Package was not found.', 404);
        }

        return $package;
    }

    private function lockedBooking(HmsIntegration $integration, string $hmsBookingId): ?HmsBooking
    {
        return HmsBooking::query()
            ->where('integration_id', $integration->getKey())
            ->where('hms_booking_id', $hmsBookingId)
            ->lockForUpdate()
            ->first();
    }

    private function findBooking(HmsIntegration $integration, string $hmsBookingId): ?HmsBooking
    {
        return HmsBooking::query()
            ->where('integration_id', $integration->getKey())
            ->where('hms_booking_id', $hmsBookingId)
            ->first();
    }

    /**
     * @return array{booking: HmsBooking, created: bool, patient_created: bool}
     */
    private function replay(HmsBooking $existing, string $hash): array
    {
        if (! hash_equals($existing->request_hash, $hash)) {
            throw new HmsClientException(
                'booking_conflict',
                'This hms_booking_id was already booked with a different payload.',
                409,
                ['request_hash' => $existing->request_hash]
            );
        }

        return ['booking' => $existing, 'created' => false, 'patient_created' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(HmsBooking $booking, bool $patientCreated): array
    {
        return [
            'status' => true,
            'booking_id' => (int) $booking->booking_id,
            'booking_type' => $booking->booking_type,
            'state' => $booking->clientState(),
            'payable_amount' => round((float) $booking->expected_amount, 2),
            'hms_payable_amount' => round((float) $booking->hms_payable_amount, 2),
            'price_mismatch' => (bool) $booking->price_mismatch,
            'hold_expires_at' => $booking->hold_expires_at?->toIso8601String(),
            'patient' => [
                'person_id' => (string) $booking->person_id,
                'created' => $patientCreated,
            ],
            'pricing' => $booking->pricing_snapshot,
        ];
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return $state === '23000' || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
