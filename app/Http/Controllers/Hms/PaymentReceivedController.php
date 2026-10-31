<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsPaymentSyncException;
use App\Http\Controllers\Controller;
use App\Services\Hms\HmsPaymentReceivedService;
use App\Services\Hms\HmsResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentReceivedController extends Controller
{
    public function __invoke(Request $request, HmsPaymentReceivedService $payments): JsonResponse
    {
        $request->attributes->set('hms_event_type', 'payment.received');

        try {
            $payload = $payments->receive($request);
        } catch (HmsPaymentSyncException $e) {
            return response()->json([
                'status' => false,
                'is_synched' => false,
                'error' => $e->error,
                'message' => $e->getMessage(),
                'request_id' => HmsResponse::requestId($request),
            ], $e->statusCode)->header('X-Request-Id', HmsResponse::requestId($request));
        }

        return response()->json($payload)->header('X-Request-Id', HmsResponse::requestId($request));
    }
}
