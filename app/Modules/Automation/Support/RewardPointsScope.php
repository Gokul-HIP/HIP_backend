<?php

namespace App\Modules\Automation\Support;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\PharmacyOrder;
use App\Models\SecondOpinion;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital for a reward-points event is the payment's own booking or order.
 */
final class RewardPointsScope
{
    public static function forInvoice(Invoice $invoice, string $changeType, ?int $transactionId = null, ?int $coinsEarned = null): RewardPointsContext
    {
        if ($invoice->doctor_booking_id && Schema::hasTable('doctor_bookings')) {
            $booking = DoctorBooking::query()->find($invoice->doctor_booking_id);
            if ($booking) {
                return self::make(
                    $changeType,
                    'appointment_payment',
                    self::hospitalId($booking->hospital_id ?? null, $booking->branch_id ?? null),
                    $invoice->id,
                    $transactionId,
                    'doctor_booking',
                    $booking->id,
                    $coinsEarned,
                );
            }
        }

        if ($invoice->diagnostic_test_booking_id && Schema::hasTable('diagnostic_test_bookings')) {
            $booking = DiagnosticTestBooking::query()->find($invoice->diagnostic_test_booking_id);
            if ($booking) {
                return self::make(
                    $changeType,
                    'package_booking',
                    self::hospitalId($booking->branch_id ?? null, null),
                    $invoice->id,
                    $transactionId,
                    'diagnostic_test_booking',
                    $booking->id,
                    $coinsEarned,
                );
            }
        }

        if ($invoice->second_opinion_id && Schema::hasTable('second_opinions')) {
            $booking = SecondOpinion::query()->find($invoice->second_opinion_id);
            if ($booking) {
                return self::make(
                    $changeType,
                    'second_opinion_payment',
                    self::hospitalId($booking->branch_id ?? null, null),
                    $invoice->id,
                    $transactionId,
                    'second_opinion',
                    $booking->id,
                    $coinsEarned,
                );
            }
        }

        if ($invoice->pharmacy_order_id && Schema::hasTable('pharmacy_orders')) {
            $order = PharmacyOrder::query()->find($invoice->pharmacy_order_id);
            if ($order) {
                return self::make(
                    $changeType,
                    'pharmacy_payment',
                    self::hospitalId($order->hospital_id ?? null, null),
                    $invoice->id,
                    $transactionId,
                    'pharmacy_order',
                    $order->id,
                    $coinsEarned,
                );
            }
        }

        return self::make($changeType, 'payment', null, $invoice->id, $transactionId, null, null, $coinsEarned);
    }

    public static function forBookingHospital(
        string $changeType,
        string $source,
        mixed $hospitalId,
        ?string $bookingSource = null,
        int|string|null $bookingId = null,
        ?int $invoiceId = null,
        ?int $transactionId = null,
    ): RewardPointsContext {
        return self::make(
            $changeType,
            $source,
            self::hospitalId($hospitalId, null),
            $invoiceId,
            $transactionId,
            $bookingSource,
            $bookingId,
        );
    }

    public static function organizationIdForHospital(?int $hospitalId): ?int
    {
        if ($hospitalId === null || $hospitalId <= 0 || ! Schema::hasTable('hospitals')) {
            return null;
        }

        $organizationId = Hospital::query()->whereKey($hospitalId)->value('organization_id');

        return is_numeric($organizationId) ? (int) $organizationId : null;
    }

    private static function make(
        string $changeType,
        string $source,
        ?int $hospitalId,
        ?int $invoiceId = null,
        ?int $transactionId = null,
        ?string $bookingSource = null,
        int|string|null $bookingId = null,
        ?int $coinsEarned = null,
    ): RewardPointsContext {
        return new RewardPointsContext(
            changeType: $changeType,
            source: $source,
            hospitalId: $hospitalId,
            invoiceId: $invoiceId,
            transactionId: $transactionId,
            bookingSource: $bookingSource,
            bookingId: $bookingId,
            coinsEarned: $coinsEarned,
        );
    }

    private static function hospitalId(mixed $primary, mixed $fallback): ?int
    {
        $value = $primary ?: $fallback;

        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }
}
