<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\OpenRouterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeMedicalBillJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $reportId,
        public string $documentText,
        public ?string $model = null,
        public ?string $fallbackModel = null
    ) {}

    public function handle(OpenRouterService $openRouterService): void
    {
        $report = Report::query()->find($this->reportId);
        if (! $report) {
            return;
        }

        $analysis = [];
        try {
            $result = $openRouterService->analyzeMedicalBill($this->documentText, [
                'model' => $this->model,
                'fallback_model' => $this->fallbackModel,
            ]);
            $analysis = is_array($result['analysis'] ?? null) ? $result['analysis'] : [];
        } catch (Throwable $e) {
            Log::warning('LLM bill analysis failed. Falling back to rule-based extraction.', [
                'report_id' => $this->reportId,
                'error' => $e->getMessage(),
            ]);
        }

        $patientId = $this->resolvePatientId(
            (string) ($analysis['patient_id'] ?? ''),
            $this->extractPatientId($this->documentText)
        );
        $patientName = $this->resolvePatientName(
            (string) ($analysis['patient_name'] ?? ''),
            $this->extractPatientName($this->documentText)
        );
        $billNumber = $this->resolveBillNumber(
            (string) ($analysis['bill_number'] ?? ''),
            $this->extractBillNumber($this->documentText)
        );
        $totalAmount = $this->resolveAmount(
            (string) ($analysis['total_bill_amount'] ?? ''),
            $this->extractTotalAmount($this->documentText)
        );

        Log::info('Medical bill final extracted fields.', [
            'report_id' => $this->reportId,
            'patient_id' => $patientId,
            'patient_name' => $patientName,
            'invoice_id' => $billNumber,
            'total_amount' => $totalAmount,
        ]);

        $report->update([
            'patient_id' => $patientId !== '' ? $patientId : null,
            'patient_name' => $patientName !== '' ? $patientName : null,
            'invoice_id' => $billNumber !== '' ? $billNumber : null,
            'total_amount' => $totalAmount,
            'content' => $this->documentText,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('AnalyzeMedicalBillJob failed.', [
            'report_id' => $this->reportId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function pickValue(?string $first, ?string $fallback): string
    {
        $first = trim((string) $first);
        if ($first !== '') {
            return $first;
        }

        return trim((string) $fallback);
    }

    private function sanitizePatientName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = preg_replace('/\s*Bill\s*No\.?.*/i', '', $name) ?? $name;
        return trim($name);
    }

    private function resolvePatientId(?string $llmValue, ?string $ocrValue): string
    {
        foreach ([$llmValue, $ocrValue] as $value) {
            $value = trim((string) $value);
            if ($value !== '' && preg_match('/^[A-Z0-9\-]{3,}$/i', $value)) {
                return $value;
            }
        }

        return '';
    }

    private function resolvePatientName(?string $llmValue, ?string $ocrValue): string
    {
        foreach ([$llmValue, $ocrValue] as $value) {
            $value = $this->sanitizePatientName((string) $value);
            if (! $this->isValidPatientName($value)) {
                continue;
            }

            return $value;
        }

        return '';
    }

    private function resolveBillNumber(?string $llmValue, ?string $ocrValue): string
    {
        foreach ([$llmValue, $ocrValue] as $value) {
            $value = trim((string) $value);
            if ($value !== '' && preg_match('/^[A-Z0-9\-]{3,}$/i', $value)) {
                return $value;
            }
        }

        return '';
    }

    private function resolveAmount(?string $llmValue, ?string $ocrValue): ?string
    {
        $llmAmount = $this->normalizeAmount($llmValue);
        if ($llmAmount !== null) {
            return $llmAmount;
        }

        return $this->normalizeAmount($ocrValue);
    }

    private function isValidPatientName(string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if (preg_match('/\d/', $name)) {
            return false;
        }

        $upper = strtoupper($name);
        $blocked = ['BILL', 'PATIENT', 'DETAILS', 'INPATIENT', 'HOSPITALS'];
        if (in_array($upper, $blocked, true)) {
            return false;
        }

        return strlen($name) >= 3;
    }

    private function extractPatientId(string $text): ?string
    {
        if (preg_match('/(?:ID|IP|1P)\s*No\.?\s*:\s*([A-Z0-9]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractPatientName(string $text): ?string
    {
        $normalizedText = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;

        if (preg_match('/(?:^|\n)\s*([A-Za-z][A-Za-z .]{2,}?)\s+Bill\s*No\.?\s*:/i', $normalizedText, $matches)) {
            $candidate = trim((string) ($matches[1] ?? ''));
            if ($this->isValidPatientName($candidate)) {
                return $candidate;
            }
        }

        $lines = preg_split('/\n/', $normalizedText) ?: [];
        foreach ($lines as $index => $line) {
            if (stripos($line, 'PATIENT DETAILS') !== false) {
                for ($next = $index + 1; $next <= $index + 4; $next++) {
                    $candidate = trim((string) ($lines[$next] ?? ''));
                    if ($candidate === '') {
                        continue;
                    }

                    $candidate = preg_replace('/\s*Bill\s*No\.?.*/i', '', $candidate) ?? $candidate;
                    $candidate = trim($candidate);
                    if ($this->isValidPatientName($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private function extractBillNumber(string $text): ?string
    {
        if (preg_match('/Bill\s*No\.?\s*:\s*([A-Z0-9]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractTotalAmount(string $text): ?string
    {
        $patterns = [
            '/Bill\s*Amount\s*[:\-]?\s*([0-9][0-9,]*\.?[0-9]{0,2})/i',
            '/Total\s*Amount\s*[:\-]?\s*([0-9][0-9,]*\.?[0-9]{0,2})/i',
            '/Amount\s*Due\s*[:\-]?\s*([0-9][0-9,]*\.?[0-9]{0,2})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function normalizeAmount(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/([0-9][0-9,]*\.?[0-9]{0,2})/', $value, $match)) {
            $value = $match[1];
        }

        $normalized = str_replace(',', '', $value);
        if (! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }
}

