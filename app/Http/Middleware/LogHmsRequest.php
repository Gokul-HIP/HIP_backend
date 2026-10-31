<?php

namespace App\Http\Middleware;

use App\Models\HmsIntegration;
use App\Models\HmsWebhookInbox;
use App\Services\Hms\HmsSignature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogHmsRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $request->attributes->set('hms_request_id', $requestId);
        $request->attributes->set('hms_received_at', now());

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $this->write($request, $response);
        } catch (Throwable $e) {
            Log::error('HMS inbox log failed', [
                'request_id' => $request->attributes->get('hms_request_id'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function write(Request $request, Response $response): void
    {
        $integration = $request->attributes->get('hms_integration');
        $decoded = json_decode($response->getContent(), true);
        $responseBody = is_array($decoded) ? $decoded : null;
        $status = $response->getStatusCode();

        $state = $request->attributes->get('hms_inbox_state');
        if (! is_string($state) || $state === '') {
            $state = $status >= 200 && $status < 300
                ? HmsWebhookInbox::STATE_PROCESSED
                : HmsWebhookInbox::STATE_FAILED;
        }

        $error = $request->attributes->get('hms_inbox_error');
        if (! is_string($error) || $error === '') {
            $error = $status >= 400 && is_array($decoded) ? ($decoded['message'] ?? null) : null;
        }

        if ($request->attributes->has('hms_inbox_event_id')) {
            $eventId = $request->attributes->get('hms_inbox_event_id');
            $eventId = is_string($eventId) && $eventId !== '' ? $eventId : null;
        } else {
            $header = trim((string) $request->headers->get('X-HMS-Event-Id', ''));
            $eventId = $header !== '' ? $header : null;
        }

        $raw = $request->getContent();
        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            $payload = [
                'query' => $request->query(),
                'raw' => $raw,
            ];
        }

        HmsWebhookInbox::query()->create([
            'integration_id' => $integration instanceof HmsIntegration ? $integration->getKey() : null,
            'request_id' => (string) $request->attributes->get('hms_request_id'),
            'event_id' => $eventId,
            'event_type' => (string) ($request->attributes->get('hms_event_type') ?: 'client.request'),
            'method' => strtoupper($request->getMethod()),
            'path' => substr(HmsSignature::pathAndQuery($request), 0, 1024),
            'headers' => $this->redactedHeaders($request),
            'payload' => $payload,
            'state' => $state,
            'response_status' => $status,
            'response_body' => $responseBody,
            'error' => $error,
            'received_at' => $request->attributes->get('hms_received_at') ?? now(),
            'processed_at' => now(),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function redactedHeaders(Request $request): array
    {
        $sensitive = ['x-hms-key', 'x-hms-signature', 'authorization', 'cookie', 'x-api-key'];
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = in_array(strtolower((string) $name), $sensitive, true)
                ? ['[redacted]']
                : array_values($values);
        }

        return $headers;
    }
}
