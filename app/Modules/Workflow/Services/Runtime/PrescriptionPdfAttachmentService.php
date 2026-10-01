<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Models\Prescription;
use App\Services\PrescriptionDocumentService;
use RuntimeException;

class PrescriptionPdfAttachmentService
{
    public function __construct(
        protected PrescriptionDocumentService $prescriptionDocumentService,
    ) {}

    /**
     * @param  array<string, mixed>  $nodeData
     */
    public function isEnabled(array $nodeData): bool
    {
        $raw = $nodeData['attachPrescriptionPdf']
            ?? $nodeData['attach_prescription_pdf']
            ?? false;

        if (is_bool($raw)) {
            return $raw;
        }

        if (is_int($raw) || is_float($raw)) {
            return (int) $raw === 1;
        }

        if (! is_string($raw)) {
            return false;
        }

        return in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{filename: string, content: string, mime: string, prescription_id?: int}>
     */
    public function attachments(array $context, string $channelLabel = 'Send Email'): array
    {
        $prescriptionId = $this->prescriptionIdFromContext($context);

        if ($prescriptionId === null) {
            throw new RuntimeException($channelLabel.': prescription_id is required to attach the prescription PDF.');
        }

        $prescription = $context['prescription'] ?? null;
        if (! $prescription instanceof Prescription || (int) $prescription->id !== $prescriptionId) {
            $prescription = Prescription::query()->find($prescriptionId);
        }

        if (! $prescription) {
            throw new RuntimeException($channelLabel.': prescription not found for prescription_id '.$prescriptionId.'.');
        }

        try {
            $binary = $this->prescriptionDocumentService->renderPdfBinary($prescription);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                $channelLabel.': failed to generate prescription PDF. '.$e->getMessage(),
                0,
                $e
            );
        }

        if ($binary === '') {
            throw new RuntimeException($channelLabel.': failed to generate prescription PDF. Renderer returned empty output.');
        }

        return [[
            'filename' => $this->prescriptionDocumentService->downloadFilename($prescription),
            'content' => $binary,
            'mime' => 'application/pdf',
            'prescription_id' => $prescription->id,
        ]];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function prescriptionIdFromContext(array $context): ?int
    {
        $raw = $context['prescription_id']
            ?? data_get($context, 'prescription.id')
            ?? data_get($context, 'meta.prescription_id');

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}
