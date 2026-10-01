<?php

namespace App\Services;

use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class PrescriptionDocumentService
{
    public function downloadFilename(Prescription $prescription): string
    {
        return 'prescription-'.$prescription->id.'.pdf';
    }

    public function renderPdfBinary(Prescription $prescription): string
    {
        $prescription->loadMissing(['patient', 'doctor', 'hospital']);

        try {
            $output = Pdf::loadView('pdf.prescription', [
                'prescription' => $prescription,
                'patientName' => $this->personName($prescription->patient),
                'doctorName' => $this->personName($prescription->doctor),
                'hospitalName' => (string) ($prescription->hospital?->name ?? ''),
                'medications' => $this->medications($prescription),
            ])->output();
        } catch (\Throwable $e) {
            throw new RuntimeException('Prescription PDF generation failed: '.$e->getMessage(), 0, $e);
        }

        if (! is_string($output) || $output === '') {
            throw new RuntimeException('Prescription PDF generation failed: renderer returned empty output.');
        }

        return $output;
    }

    /**
     * @return list<array{name: string, dosage: string, frequency: string, duration: string, when_to_take: string, special_instruction: string}>
     */
    protected function medications(Prescription $prescription): array
    {
        $rows = [];
        foreach ($prescription->medications ?? [] as $med) {
            if (! is_array($med)) {
                continue;
            }
            $rows[] = [
                'name' => (string) ($med['name'] ?? $med['medicine_name'] ?? ''),
                'dosage' => (string) ($med['dosage'] ?? ''),
                'frequency' => (string) ($med['frequency'] ?? ''),
                'duration' => (string) ($med['duration'] ?? ''),
                'when_to_take' => (string) ($med['when_to_take'] ?? ''),
                'special_instruction' => (string) ($med['special_instruction'] ?? $med['special_instructions'] ?? ''),
            ];
        }

        return $rows;
    }

    protected function personName(mixed $person): string
    {
        if (! is_object($person)) {
            return '';
        }

        $name = trim((string) (($person->first_name ?? '').' '.($person->last_name ?? '')));
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($person->name ?? ''));
    }
}
