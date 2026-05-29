<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeMedicalBillJob;
use App\Models\Coins;
use App\Models\HIPCard;
use App\Models\Organization;
use App\Models\Persons;
use App\Models\Report;
use App\Services\OpenRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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

            $coinsMeta = $this->applyCoinsFromReport(
                (string) ($analysis['patient_id'] ?? ''),
                (string) ($report->patient_id ?? ''),
                $report->total_amount
            );

            $queued = false;
            try {
                AnalyzeMedicalBillJob::dispatch(
                    $report->id,
                    $validated['document_text'],
                    $validated['model'] ?? null,
                    $validated['fallback_model'] ?? null
                );
                $queued = true;
            } catch (Throwable $dispatchException) {
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
                    'coins' => $coinsMeta,
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

    /**
     * Resolve person via HIP card and add report-based coins.
     * Rule: add 1% of report total amount as coins.
     *
     * @return array<string, mixed>
     */
    private function applyCoinsFromReport(string $extractedPatientId, string $storedPatientId, mixed $reportAmount): array
    {
        $reportAmountFloat = (float) $reportAmount;
        if ($reportAmountFloat <= 0) {
            return ['updated' => false, 'reason' => 'invalid_report_amount'];
        }

        $patientKey = trim($extractedPatientId) !== '' ? trim($extractedPatientId) : trim($storedPatientId);
        if ($patientKey === '') {
            return ['updated' => false, 'reason' => 'missing_patient_id'];
        }

        $hipCard = $this->resolveHipCard($patientKey);
        $personId = (string) ($hipCard?->patient_id ?? $patientKey);

        $person = Persons::query()->with('hipUser')->where('id', $personId)->first();
        if (! $person) {
            return [
                'updated' => false,
                'reason' => 'person_not_found',
                'patient_key' => $patientKey,
                'resolved_person_id' => $personId,
            ];
        }

        $hipCard = HIPCard::where('patient_id', $person->id)->first();
        if(!$hipCard){
            return [
                'updated' => false,
                'reason' => 'hip_card_not_found',
                'person_id' => $person->id,
            ];
        }

        $hipCard->update([
            'hip_points' => ((int) ($hipCard->hip_points ?? 0)) + (int) round($reportAmountFloat * 0.01),
        ]);

        $coinsToAdd = (int) round($reportAmountFloat * 0.01);
        if ($coinsToAdd <= 0) {
            return ['updated' => false, 'reason' => 'calculated_zero', 'person_id' => $person->id];
        }

        $walletService = app(\App\Services\CoinsWalletService::class);
        $coinsRow = Coins::query()->where('person_id', $person->id)->first();

        if ($coinsRow) {
            $walletService->credit($coinsRow, $coinsToAdd);
        } else {
            $organizationId = $person->hipUser?->organization_id;
            if ($organizationId === null) {
                $organizationId = Organization::query()->orderBy('id', 'asc')->value('id');
            }

            $coinsRow = Coins::query()->create([
                'person_id' => $person->id,
                'organization_id' => $organizationId !== null ? (int) $organizationId : null,
                'coins' => $coinsToAdd,
            ]);
        }

        return [
            'updated' => true,
            'person_id' => $person->id,
            'hip_card_id' => $hipCard?->hip_card_id,
            'coins_added' => $coinsToAdd,
            'coins_total' => (int) ($coinsRow->fresh()->coins ?? $coinsRow->coins),
        ];
    }

    private function resolveHipCard(string $patientKey): ?HIPCard
    {
        $patientKey = trim($patientKey);
        if ($patientKey === '') {
            return null;
        }

        $exact = HIPCard::query()
            ->where('hip_card_id', $patientKey)
            ->orWhere('patient_id', $patientKey)
            ->first();

        if ($exact) {
            return $exact;
        }

        $normalized = $this->normalizeIdentifier($patientKey);
        if ($normalized === '') {
            return null;
        }

        return HIPCard::query()
            ->get()
            ->first(function (HIPCard $card) use ($normalized) {
                return $this->normalizeIdentifier((string) ($card->hip_card_id ?? '')) === $normalized;
            });
    }

    private function normalizeIdentifier(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = str_replace(['–', '—', '_'], '-', $value);
        $value = preg_replace('/\s+/', '', $value) ?? $value;
        $value = str_replace('-', '', $value);

        return $value;
    }
}