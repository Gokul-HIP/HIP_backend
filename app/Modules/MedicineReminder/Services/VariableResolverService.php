<?php

namespace App\Modules\MedicineReminder\Services;

use App\Models\Prescription;
use Carbon\Carbon;

class VariableResolverService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function resolve(string $template, array $context): string
    {
        $variables = $this->buildVariables($context);

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($variables) {
            $key = $matches[1];

            return array_key_exists($key, $variables) ? (string) $variables[$key] : $matches[0];
        }, $template) ?? $template;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    public function buildVariables(array $context): array
    {
        /** @var Prescription|null $prescription */
        $prescription = $context['prescription'] ?? null;
        $patient = $context['patient'] ?? $prescription?->patient;
        $hospital = $context['hospital'] ?? $prescription?->hospital;
        $doctor = $context['doctor'] ?? $prescription?->doctor;
        $organization = $context['organization'] ?? $hospital?->organization;
        $medication = $context['medication'] ?? [];
        $scheduledAt = $context['scheduled_at'] ?? null;

        $scheduled = $scheduledAt instanceof Carbon
            ? $scheduledAt
            : ($scheduledAt ? Carbon::parse($scheduledAt) : Carbon::now());

        $patientName = trim(implode(' ', array_filter([
            $patient->first_name ?? null,
            $patient->last_name ?? null,
        ])));

        return [
            'patient_name' => $patientName !== '' ? $patientName : 'Patient',
            'patient_mobile' => (string) ($patient->mobile ?? ''),
            'hospital_name' => (string) ($hospital->name ?? $organization->name ?? ''),
            'doctor_name' => (string) ($doctor->name ?? ''),
            'medicine_name' => (string) ($medication['name'] ?? $context['medicine_name'] ?? ''),
            'dosage' => (string) ($medication['dosage'] ?? $context['dosage'] ?? ''),
            'frequency' => (string) ($medication['frequency'] ?? $context['frequency'] ?? ''),
            'time' => $scheduled->format('h:i A'),
            'date' => $scheduled->format('d M Y'),
        ];
    }
}
