<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

            return response()->json([
                'success' => true,
                'message' => 'Medical bill analysis completed successfully.',
                'data' => $result['analysis'],
                'meta' => [
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
}