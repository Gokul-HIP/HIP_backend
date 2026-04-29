<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeMedicalBillJob;
use App\Models\Report;
use App\Services\OpenRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MedicalBillAnalysisController extends Controller
{
    public function analyze(Request $request, OpenRouterService $openRouterService): JsonResponse
    {
        $validated = $request->validate([
            'document_text' => ['required', 'string', 'min:20'],
            'model' => ['nullable', 'string'],
            'fallback_model' => ['nullable', 'string'],
        ]);

        try {
            $result = $openRouterService->analyzeMedicalBill(
                $validated['document_text'],
                [
                    'model' => $validated['model'] ?? null,
                    'fallback_model' => $validated['fallback_model'] ?? null,
                ]
            );

            $analysis = is_array($result['analysis'] ?? null) ? $result['analysis'] : [];

            $report = Report::create([
                'original_file_name' => 'manual_text_input',
                'content' => $validated['document_text'],
                'patient_id' => $this->emptyToNull((string) ($analysis['patient_id'] ?? '')),
                'patient_name' => $this->emptyToNull((string) ($analysis['patient_name'] ?? '')),
                'invoice_id' => $this->emptyToNull((string) ($analysis['bill_number'] ?? '')),
                'total_amount' => $this->normalizeAmount((string) ($analysis['total_bill_amount'] ?? '')),
            ]);

            $queued = false;
            try {
                AnalyzeMedicalBillJob::dispatch(
                    $report->id,
                    $validated['document_text'],
                    $validated['model'] ?? null,
                    $validated['fallback_model'] ?? null
                );
                $queued = true;
            } catch (\Throwable $dispatchException) {
                Log::warning('Medical bill analysis background dispatch failed.', [
                    'report_id' => $report->id,
                    'error' => $dispatchException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Medical bill analysis completed successfully.',
                'data' => $analysis,
                'meta' => [
                    'report_id' => $report->id,
                    'queued' => $queued,
                    'model' => $result['model'] ?? null,
                    'fallback_used' => (bool) ($result['fallback_used'] ?? false),
                    'usage' => $result['usage'] ?? null,
                ],
            ]);
        } catch (RuntimeException $e) {
            Log::error('Medical bill analysis failed.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function emptyToNull(string $value): ?string
    {
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    private function normalizeAmount(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/([0-9][0-9,]*\.?[0-9]{0,2})/', $value, $matches)) {
            $value = $matches[1];
        }

        $numeric = str_replace(',', '', $value);
        if (! is_numeric($numeric)) {
            return null;
        }

        return number_format((float) $numeric, 2, '.', '');
    }
}