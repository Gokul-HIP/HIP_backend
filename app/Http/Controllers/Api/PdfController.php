<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleVisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PdfController extends Controller
{
    public function store(Request $request, GoogleVisionService $googleVisionService): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        try {
            $report = $googleVisionService->processPdfReport($validated['file']);
        } catch (RuntimeException $exception) {
            $response = [
                'success' => false,
                'message' => $exception->getMessage(),
            ];

            if (app()->environment(['local', 'testing'])) {
                $response['exception'] = class_basename($exception);
                $response['previous'] = $exception->getPrevious()?->getMessage();
            }

            return response()->json($response, 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'PDF bill processed successfully.',
            'data' => [
                'id' => $report->id,
                'original_file_name' => $report->original_file_name,
                'content' => $report->content,
                'patient_id' => $report->patient_id,
                'patient_name' => $report->patient_name,
                'invoice_id' => $report->invoice_id,
                'total_amount' => $report->total_amount,
            ],
        ], 201);
    }
}
