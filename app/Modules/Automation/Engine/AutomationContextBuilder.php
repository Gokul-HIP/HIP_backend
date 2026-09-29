<?php

namespace App\Modules\Automation\Engine;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Prescription;
use App\Models\SecondOpinion;
use App\Models\Transactions;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use App\Modules\Automation\Support\PaymentAutomationFacts;
use Illuminate\Support\Facades\Schema;

class AutomationContextBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function fromAppointment(DoctorBooking $booking): array
    {
        $booking->loadMissing(['doctor', 'hospital.organization', 'patient', 'department']);

        $timeSlot = is_array($booking->required_time_slots)
            ? ($booking->required_time_slots[0] ?? null)
            : null;

        $paymentFacts = PaymentAutomationFacts::fromPayableSource($booking);

        $context = [
            'appointment' => $booking,
            'appointment_id' => $booking->id,
            'patient' => $booking->patient,
            'patient_id' => $booking->patient_id,
            'doctor' => $booking->doctor,
            'doctor_id' => $booking->doctor_id,
            'hospital' => $booking->hospital,
            'hospital_id' => $booking->hospital_id,
            'organization' => $booking->hospital?->organization,
            'organization_id' => $booking->hospital?->organization_id,
            'member_id' => $booking->member_id,
            'patient_mobile' => $booking->mobile_number ?? $booking->patient?->mobile,
            'patient_email' => $booking->patient?->email,
            'appointment_date' => $booking->booking_date?->format('Y-m-d'),
            'appointment_time' => $timeSlot,
            'appointment_status' => $booking->appointment_status,
            'consultation_type' => $booking->consultation_type,
            'payment' => $paymentFacts['payment'],
            'meta' => [
                'booking_id' => (string) $booking->id,
                'booking_type' => 'doctor',
            ],
        ];

        if ($paymentFacts['invoice'] !== null) {
            $context['invoice'] = $paymentFacts['invoice'];
            $context['invoice_id'] = $paymentFacts['invoice']['id'];
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function fromSecondOpinion(SecondOpinion $booking): array
    {
        $booking->loadMissing(['doctor', 'branch.organization', 'patient']);

        $paymentFacts = PaymentAutomationFacts::fromPayableSource($booking);
        $hospital = $booking->branch;
        $status = strtolower((string) $booking->status);
        $timeSlot = is_array($booking->preferred_time_slots)
            ? ($booking->preferred_time_slots[0] ?? null)
            : null;

        $appointmentView = [
            'id' => $booking->id,
            'status' => $status,
            'appointment_status' => null,
            'booking_type' => 'second_opinion',
        ];

        $context = [
            'second_opinion' => $booking,
            'second_opinion_id' => $booking->id,
            'appointment' => $appointmentView,
            'patient' => $booking->patient,
            'patient_id' => $booking->patient_id,
            'doctor' => $booking->doctor,
            'doctor_id' => $booking->doctor_id,
            'hospital' => $hospital,
            'hospital_id' => $booking->branch_id,
            'organization' => $hospital?->organization,
            'organization_id' => $hospital?->organization_id,
            'member_id' => $booking->member_id,
            'patient_mobile' => $booking->patient?->mobile,
            'patient_email' => $booking->patient?->email,
            'appointment_date' => $booking->preferred_date?->format('Y-m-d'),
            'appointment_time' => $timeSlot,
            'appointment_status' => null,
            'payment' => $paymentFacts['payment'],
            'meta' => [
                'booking_id' => (string) $booking->id,
                'booking_type' => 'second_opinion',
            ],
        ];

        if ($paymentFacts['invoice'] !== null) {
            $context['invoice'] = $paymentFacts['invoice'];
            $context['invoice_id'] = $paymentFacts['invoice']['id'];
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function fromDiagnosticOrder(DiagnosticTestBooking $booking): array
    {
        $booking->loadMissing(['patient', 'branch.organization', 'diagnosticPackage', 'diseasePackage']);

        $paymentFacts = PaymentAutomationFacts::fromPayableSource($booking);
        $hospital = $booking->branch;
        $status = strtolower((string) $booking->status);
        $testName = $booking->package_type === 'disease'
            ? (string) ($booking->diseasePackage?->name ?? $booking->test_type ?? '')
            : (string) ($booking->diagnosticPackage?->name ?? $booking->test_type ?? '');

        $context = [
            'diagnostic_test_booking' => $booking,
            'diagnostic_test_booking_id' => $booking->id,
            'order' => [
                'id' => $booking->id,
                'status' => $status,
            ],
            'patient' => $booking->patient,
            'patient_id' => $booking->patient_id,
            'hospital' => $hospital,
            'hospital_id' => $booking->branch_id,
            'organization' => $hospital?->organization,
            'organization_id' => $hospital?->organization_id,
            'member_id' => $booking->member_id,
            'patient_mobile' => $booking->mobile_number ?? $booking->patient?->mobile,
            'patient_email' => $booking->patient?->email,
            'lab_test_name' => $testName,
            'payment' => $paymentFacts['payment'],
            'meta' => [
                'booking_id' => (string) $booking->id,
                'booking_type' => 'lab',
            ],
        ];

        if ($paymentFacts['invoice'] !== null) {
            $context['invoice'] = $paymentFacts['invoice'];
            $context['invoice_id'] = $paymentFacts['invoice']['id'];
        }

        return $context;
    }

    /**
     * Queue-safe LabTestOrdered payload: scalars and nested arrays only.
     *
     * @return array<string, mixed>
     */
    public function labTestOrderedEventContext(DiagnosticTestBooking $booking): array
    {
        $built = $this->fromDiagnosticOrder($booking);

        $context = [
            'diagnostic_test_booking_id' => $built['diagnostic_test_booking_id'],
            'hospital_id' => $built['hospital_id'],
            'organization_id' => $built['organization_id'],
            'member_id' => $built['member_id'],
            'patient_id' => $built['patient_id'],
            'patient_mobile' => $built['patient_mobile'] ?? null,
            'order' => $built['order'],
            'payment' => $built['payment'],
            'lab_test_name' => $built['lab_test_name'] ?? '',
            'meta' => $built['meta'],
        ];

        if (isset($built['invoice'])) {
            $context['invoice'] = $built['invoice'];
            $context['invoice_id'] = $built['invoice_id'] ?? null;
        }

        $context['event_occurrence_id'] = \App\Modules\Automation\Events\LabTestOrdered::occurrenceIdFrom($context);

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function fromPrescription(Prescription $prescription): array
    {
        $prescription->loadMissing(['patient', 'doctor', 'hospital.organization', 'member']);

        return [
            'prescription' => $prescription,
            'prescription_id' => $prescription->id,
            'patient' => $prescription->patient,
            'patient_id' => $prescription->patient_id,
            'doctor' => $prescription->doctor,
            'doctor_id' => $prescription->doctor_id,
            'hospital' => $prescription->hospital,
            'hospital_id' => $prescription->hospital_id,
            'organization' => $prescription->hospital?->organization,
            'organization_id' => $prescription->hospital?->organization_id,
            'member_id' => $prescription->member_id,
            'patient_mobile' => $prescription->patient?->mobile,
            'patient_email' => $prescription->patient?->email ?? $prescription->member?->email,
            'meta' => ['prescription_id' => (string) $prescription->id],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function fromInvoice(Invoice $invoice, array $payload = []): array
    {
        InvoiceAutomationScope::hydrate($invoice);

        $booking = $invoice->doctorBooking;
        $hospital = InvoiceAutomationScope::hospital($invoice);
        $organization = $hospital?->organization;
        $organizationId = InvoiceAutomationScope::organizationId($invoice);
        $hospitalId = InvoiceAutomationScope::hospitalId($invoice);
        $patient = $invoice->person ?? $invoice->primaryPerson ?? $booking?->patient;
        $status = strtolower((string) $invoice->status);
        $paymentStatus = $payload['payment_status'] ?? $status;
        $originalAmount = (float) ($invoice->amount ?? 0);
        $discountAmount = (float) ($invoice->discount_price ?? 0);
        $discountedAmount = round(max(0, $originalAmount - $discountAmount), 2);
        $invoiceAmount = $invoice->total_amount ?? $invoice->amount;

        $invoiceView = [
            'id' => $invoice->id,
            'status' => $status,
            'total_amount' => $invoice->total_amount,
            'amount' => $invoice->amount,
            'payment_status' => $paymentStatus,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'discounted_amount' => $discountedAmount,
            'service_charges' => $invoice->service_charges,
            'payment_gateway_charges' => $invoice->payment_gateway_charges,
            'gst_amount' => $invoice->total_gst,
            'invoice_total' => $invoice->total_amount,
        ];

        return array_merge(
            $booking ? $this->fromAppointment($booking) : [],
            [
                'invoice' => $invoiceView,
                'invoice_id' => $invoice->id,
                'invoice_amount' => $invoiceAmount,
                'invoice_status' => $status,
                'payment_status' => $paymentStatus,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'discounted_amount' => $discountedAmount,
                'service_charges' => $invoice->service_charges,
                'payment_gateway_charges' => $invoice->payment_gateway_charges,
                'gst_amount' => $invoice->total_gst,
                'invoice_total' => $invoice->total_amount,
                'patient' => $patient,
                'patient_id' => $invoice->person_id ?? $booking?->patient_id,
                'member_id' => $invoice->person?->hip_user_id
                    ?? $invoice->primaryPerson?->hip_user_id
                    ?? $booking?->member_id,
                'hospital' => $hospital ?? $booking?->hospital,
                'hospital_id' => $hospitalId ?? $booking?->hospital_id,
                'organization' => $organization ?? $booking?->hospital?->organization,
                'organization_id' => $organizationId ?? $booking?->hospital?->organization_id,
                'appointment_id' => $invoice->doctor_booking_id ?? $booking?->id,
                'patient_mobile' => $patient?->mobile ?? $booking?->mobile_number,
                'patient_email' => $patient?->email,
                'meta' => ['invoice_id' => (string) $invoice->id],
            ]
        );
    }

    /**
     * Normalized payment facts from the invoices.transactions row.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function fromPayment(Transactions $transaction, array $payload = []): array
    {
        $status = strtolower((string) $transaction->status);
        $amount = (float) ($transaction->total_amount ?? $transaction->transaction_amount ?? 0);
        $displayTxnId = 'TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT);
        $gatewayId = $payload['gateway_payment_id']
            ?? $payload['razorpay_payment_id']
            ?? null;
        $currency = $payload['payment_currency'] ?? null;

        $invoice = null;
        try {
            $invoice = $transaction->relationLoaded('invoice')
                ? $transaction->invoice
                : $transaction->invoice()->first();
        } catch (\Throwable) {
            $invoice = null;
        }

        if ($invoice instanceof Invoice) {
            InvoiceAutomationScope::hydrate($invoice);
        }

        $paymentView = [
            'id' => $transaction->id,
            'status' => $status,
            'amount' => $amount,
            'transaction_id' => $displayTxnId,
            'gateway_payment_id' => $gatewayId !== null && $gatewayId !== '' ? (string) $gatewayId : null,
            'method' => $transaction->payment_method,
            'created_at' => optional($transaction->created_at)?->toIso8601String(),
        ];

        $paymentView = PaymentAutomationFacts::withPayByHospitalFromInvoice(
            $paymentView,
            $invoice instanceof Invoice ? $invoice : null
        );

        if (is_string($currency) && $currency !== '') {
            $paymentView['currency'] = $currency;
        }

        return [
            'payment' => $paymentView,
            'payment_id' => $transaction->id,
            'payment_amount' => $amount,
            'payment_method' => $transaction->payment_method,
            'transaction_id' => $displayTxnId,
            'gateway_payment_id' => $paymentView['gateway_payment_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $labContext
     * @return array<string, mixed>
     */
    public function fromLab(array $labContext): array
    {
        return array_merge($labContext, [
            'lab_report_id' => $labContext['report_id'] ?? $labContext['lab_report_id'] ?? null,
            'lab_test_name' => $labContext['test_name'] ?? $labContext['lab_test_name'] ?? '',
            'meta' => array_merge($labContext['meta'] ?? [], [
                'lab_report_id' => (string) ($labContext['report_id'] ?? ''),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fromPatient(Persons $patient, ?int $organizationId = null): array
    {
        return [
            'patient' => $patient,
            'patient_id' => $patient->id,
            'patient_mobile' => $patient->mobile,
            'patient_email' => $patient->email,
            'organization_id' => $organizationId,
            'meta' => ['patient_id' => (string) $patient->id],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function merge(array $payload): array
    {
        if (isset($payload['appointment']) && $payload['appointment'] instanceof DoctorBooking) {
            return array_merge($this->fromAppointment($payload['appointment']), $payload);
        }

        if (isset($payload['second_opinion']) && $payload['second_opinion'] instanceof SecondOpinion) {
            return array_merge($this->fromSecondOpinion($payload['second_opinion']), $payload);
        }

        if (isset($payload['diagnostic_test_booking']) && $payload['diagnostic_test_booking'] instanceof DiagnosticTestBooking) {
            return array_merge($this->fromDiagnosticOrder($payload['diagnostic_test_booking']), $payload);
        }

        if (! empty($payload['diagnostic_test_booking_id']) && Schema::hasTable('diagnostic_test_bookings')) {
            $labOrder = DiagnosticTestBooking::query()->find($payload['diagnostic_test_booking_id']);
            if ($labOrder instanceof DiagnosticTestBooking) {
                return array_merge($this->fromDiagnosticOrder($labOrder), $payload);
            }
        }

        if (isset($payload['prescription']) && $payload['prescription'] instanceof Prescription) {
            return array_merge($this->fromPrescription($payload['prescription']), $payload);
        }

        if (isset($payload['invoice']) && $payload['invoice'] instanceof Invoice) {
            // fromInvoice must win for `invoice` so JEXL sees invoice.status / invoice.payment_status
            // instead of the Eloquent model (which has no payment_status attribute).
            $merged = array_merge($payload, $this->fromInvoice($payload['invoice'], $payload));

            if (isset($payload['transaction']) && $payload['transaction'] instanceof Transactions) {
                $merged = array_merge($merged, $this->fromPayment($payload['transaction'], $payload));
            }

            return $merged;
        }

        if (isset($payload['patient']) && $payload['patient'] instanceof Persons) {
            return array_merge(
                $this->fromPatient($payload['patient'], $payload['organization_id'] ?? null),
                $payload
            );
        }

        return $payload;
    }

    public function resolveOrganizationId(array $payload): ?int
    {
        $orgId = $payload['organization_id'] ?? null;

        return $orgId ? (int) $orgId : null;
    }
}
