<?php

namespace App\Modules\Workflow\Services\Runtime;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class VariableResolver
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function resolve(string $template, array $context): string
    {
        $variables = $this->buildVariables($context);

        $resolved = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($variables) {
            $key = $matches[1];
            $normalizedKey = $this->normalizeKey($key);

            if (array_key_exists($normalizedKey, $variables)) {
                return (string) $variables[$normalizedKey];
            }

            if (array_key_exists($key, $variables)) {
                return (string) $variables[$key];
            }

            return $matches[0];
        }, $template);

        return $resolved ?? $template;
    }

    protected function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $key) ?? $key);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    public function buildVariables(array $context): array
    {
        $appointment = $this->entity($context, 'appointment');
        $prescription = $this->entity($context, 'prescription');
        $invoice = $this->entity($context, 'invoice');

        $patient = $this->entity($context, 'patient')
            ?? $this->attr($prescription, 'patient')
            ?? $this->attr($appointment, 'patient')
            ?? $this->attr($invoice, 'person');

        $hospital = $this->entity($context, 'hospital')
            ?? $this->attr($prescription, 'hospital')
            ?? $this->attr($appointment, 'hospital');

        $doctor = $this->entity($context, 'doctor')
            ?? $this->attr($prescription, 'doctor')
            ?? $this->attr($appointment, 'doctor');

        $organization = $this->entity($context, 'organization')
            ?? $this->attr($hospital, 'organization');

        $medication = $context['medication'] ?? [];
        if (! is_array($medication)) {
            $medication = [];
        }

        $scheduledAt = $context['scheduled_at'] ?? $context['appointment_date'] ?? $this->attr($appointment, 'booking_date');
        $workflowVariables = is_array($context['variables'] ?? null) ? $context['variables'] : [];

        $scheduled = $this->parseDate($scheduledAt) ?? Carbon::now();

        $patientName = trim(implode(' ', array_filter([
            $this->attr($patient, 'first_name'),
            $this->attr($patient, 'last_name'),
        ], fn ($value) => filled($value))));

        $hospitalName = (string) (
            $this->attr($hospital, 'name')
            ?? $this->attr($organization, 'name')
            ?? ''
        );

        $doctorName = (string) ($this->attr($doctor, 'name') ?? '');

        $bookingDate = $this->attr($appointment, 'booking_date');
        $formattedBookingDate = '';
        if ($bookingDate instanceof Carbon) {
            $formattedBookingDate = $bookingDate->format('d M Y');
        } elseif (is_string($bookingDate) && $bookingDate !== '') {
            try {
                $formattedBookingDate = Carbon::parse($bookingDate)->format('d M Y');
            } catch (\Throwable) {
                $formattedBookingDate = $bookingDate;
            }
        }

        $base = [
            'patient_name' => $patientName !== '' ? $patientName : 'Patient',
            'patientname' => $patientName !== '' ? $patientName : 'Patient',
            'patient_mobile' => (string) ($this->attr($patient, 'mobile') ?? $context['patient_mobile'] ?? ''),
            'hospital_name' => $hospitalName,
            'hospitalname' => $hospitalName,
            'doctor_name' => $doctorName,
            'doctorname' => $doctorName,
            'medicine_name' => (string) ($medication['name'] ?? $context['medicine_name'] ?? ''),
            'medicinename' => (string) ($medication['name'] ?? $context['medicine_name'] ?? ''),
            'dosage' => (string) ($medication['dosage'] ?? $context['dosage'] ?? ''),
            'frequency' => (string) ($medication['frequency'] ?? $context['frequency'] ?? ''),
            'time' => $scheduled->format('h:i A'),
            'date' => $scheduled->format('d M Y'),
            'appointment_date' => (string) ($context['appointment_date'] ?? $formattedBookingDate),
            'appointment_time' => (string) ($context['appointment_time'] ?? ''),
            'appointment_id' => (string) ($context['appointment_id'] ?? $this->attr($appointment, 'id') ?? ''),
            'invoice_amount' => (string) ($context['invoice_amount'] ?? $this->attr($invoice, 'amount') ?? ''),
            'invoice_id' => (string) ($context['invoice_id'] ?? $this->attr($invoice, 'id') ?? ''),
            'payment_status' => (string) ($context['payment_status'] ?? $this->attr($invoice, 'status') ?? ''),
            'lab_test_name' => (string) ($context['lab_test_name'] ?? ''),
            'lab_report_id' => (string) ($context['lab_report_id'] ?? ''),
            'membership_tier' => (string) ($context['membership_tier'] ?? ''),
            'membership_expiry' => (string) ($context['membership_expiry'] ?? ''),
            'reward_points' => (string) ($context['reward_points'] ?? ''),
            'coupon_code' => (string) ($context['coupon_code'] ?? ''),
            'feedback_url' => (string) ($context['feedback_url'] ?? ''),
            'ai_summary' => (string) ($context['ai_summary'] ?? $workflowVariables['ai_summary'] ?? ''),
        ];

        foreach ($workflowVariables as $key => $value) {
            if (is_scalar($value)) {
                $normalized = $this->normalizeKey((string) $key);
                $base[$normalized] = (string) $value;
                $base[(string) $key] = (string) $value;
            }
        }

        return $base;
    }

    /**
     * Resolve a top-level context entity, preferring non-empty values.
     *
     * @param  array<string, mixed>  $context
     */
    protected function entity(array $context, string $key): mixed
    {
        $value = $context[$key] ?? null;

        return $this->isPresent($value) ? $value : null;
    }

    /**
     * Read an attribute from an object or array entity.
     */
    protected function attr(mixed $entity, string $key, mixed $default = null): mixed
    {
        if (! $this->isPresent($entity)) {
            return $default;
        }

        if (is_array($entity)) {
            $value = Arr::get($entity, $key);

            return $this->isPresent($value) ? $value : $default;
        }

        if (is_object($entity)) {
            $value = data_get($entity, $key);

            return $this->isPresent($value) ? $value : $default;
        }

        return $default;
    }

    protected function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTimeImmutable::createFromInterface($value));
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
