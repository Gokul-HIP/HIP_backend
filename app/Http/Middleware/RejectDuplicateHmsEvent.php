<?php

namespace App\Http\Middleware;

use App\Models\HmsIntegration;
use App\Models\HmsWebhookInbox;
use App\Services\Hms\HmsResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency guard for a later booking/payment webhook step.
 * Read APIs can send X-HMS-Event-Id; a repeated id returns the stored response.
 */
class RejectDuplicateHmsEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        $eventId = trim((string) $request->headers->get('X-HMS-Event-Id', ''));
        $integration = $request->attributes->get('hms_integration');

        if ($eventId === '' || ! $integration instanceof HmsIntegration) {
            return $next($request);
        }

        $existing = HmsWebhookInbox::query()
            ->where('integration_id', $integration->getKey())
            ->where('event_id', $eventId)
            ->first();

        if (! $existing) {
            return $next($request);
        }

        $request->attributes->set('hms_inbox_state', HmsWebhookInbox::STATE_DUPLICATE);
        $request->attributes->set('hms_inbox_event_id', null);
        $request->attributes->set('hms_inbox_error', 'Duplicate event_id '.$eventId);

        $status = (int) ($existing->response_status ?: 200);
        $body = is_array($existing->response_body) ? $existing->response_body : [
            'status' => false,
            'code' => 'duplicate',
            'message' => 'This event was already received.',
            'request_id' => HmsResponse::requestId($request),
        ];

        return response()->json($body, $status)->header('X-Request-Id', HmsResponse::requestId($request));
    }
}
