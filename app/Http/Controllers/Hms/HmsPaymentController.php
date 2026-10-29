<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsClientException;
use App\Services\Hms\HmsResponse;
use App\Services\Hms\HmsSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HmsPaymentController extends HmsController
{
    public function verify(Request $request, HmsSettlementService $settlement): JsonResponse
    {
        if (trim((string) $request->headers->get('X-HMS-Event-Id', '')) === '') {
            throw new HmsClientException('missing_event_id', 'X-HMS-Event-Id is required.', 422);
        }

        $request->attributes->set('hms_event_type', 'payment.verification');
        $result = $settlement->verify($this->integration($request), $request->all());
        if (($result['body']['status'] ?? true) === false) {
            return HmsResponse::error(
                $request,
                (string) $result['body']['code'],
                (string) $result['body']['message'],
                $result['http_status'],
                array_diff_key($result['body'], array_flip(['status', 'code', 'message']))
            );
        }

        return response()->json($result['body'], $result['http_status'])
            ->header('X-Request-Id', HmsResponse::requestId($request));
    }

    public function status(Request $request, HmsSettlementService $settlement): JsonResponse
    {
        $request->attributes->set('hms_event_type', 'booking.status');

        return HmsResponse::ok($request, $settlement->status($this->integration($request), $request->query()));
    }
}
