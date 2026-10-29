<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsClientException;
use App\Services\Hms\HmsCancellationService;
use App\Services\Hms\HmsMembershipPurchaseService;
use App\Services\Hms\HmsResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HmsMembershipController extends HmsController
{
    public function purchased(Request $request, HmsMembershipPurchaseService $memberships): JsonResponse
    {
        if (trim((string) $request->headers->get('X-HMS-Event-Id', '')) === '') {
            throw new HmsClientException('missing_event_id', 'X-HMS-Event-Id is required.', 422);
        }

        $request->attributes->set('hms_event_type', 'membership.purchased');
        $result = $memberships->purchase($this->integration($request), $request->all());

        return response()
            ->json($result['body'], $result['http_status'])
            ->header('X-Request-Id', HmsResponse::requestId($request));
    }

    public function cancelled(Request $request, HmsCancellationService $cancellations): JsonResponse
    {
        if (trim((string) $request->headers->get('X-HMS-Event-Id', '')) === '') {
            throw new HmsClientException('missing_event_id', 'X-HMS-Event-Id is required.', 422);
        }

        $request->attributes->set('hms_event_type', 'membership.cancelled');
        $result = $cancellations->cancelMembership($this->integration($request), $request->all());

        return response()
            ->json($result['body'], $result['http_status'])
            ->header('X-Request-Id', HmsResponse::requestId($request));
    }
}
