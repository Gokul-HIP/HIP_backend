<?php

namespace App\Modules\Automation\Engine;

use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Prescription;
use Carbon\Carbon;

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

        return [
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
            'meta' => [
                'booking_id' => (string) $booking->id,
                'booking_type' => 'doctor',
            ],
        ];
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
    public function fromInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['person', 'doctorBooking.hospital.organization', 'doctorBooking.doctor']);

        $booking = $invoice->doctorBooking;

        return array_merge(
            $booking ? $this->fromAppointment($booking) : [],
            [
                'invoice' => $invoice,
                'invoice_id' => $invoice->id,
                'invoice_amount' => $invoice->amount,
                'invoice_status' => $invoice->status,
                'payment_status' => $invoice->status,
                'patient' => $invoice->person ?? $booking?->patient,
                'patient_id' => $invoice->person_id ?? $booking?->patient_id,
                'meta' => ['invoice_id' => (string) $invoice->id],
            ]
        );
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

        if (isset($payload['prescription']) && $payload['prescription'] instanceof Prescription) {
            return array_merge($this->fromPrescription($payload['prescription']), $payload);
        }

        if (isset($payload['invoice']) && $payload['invoice'] instanceof Invoice) {
            return array_merge($this->fromInvoice($payload['invoice']), $payload);
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
