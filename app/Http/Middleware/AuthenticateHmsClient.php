<?php

namespace App\Http\Middleware;

use App\Models\HmsIntegration;
use App\Services\Hms\HmsResponse;
use App\Services\Hms\HmsSignature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateHmsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = trim((string) $request->headers->get('X-HMS-Key', ''));
        $timestamp = trim((string) $request->headers->get('X-HMS-Timestamp', ''));
        $signature = trim((string) $request->headers->get('X-HMS-Signature', ''));

        if ($apiKey === '' || $timestamp === '' || $signature === '' || ! ctype_digit($timestamp)) {
            return $this->fail($request, 'invalid_signature', 'Signature is invalid.', 401);
        }

        $integration = HmsIntegration::query()
            ->where('api_key_hash', HmsIntegration::hashKey($apiKey))
            ->first();

        if (! $integration) {
            return $this->fail($request, 'invalid_signature', 'Signature is invalid.', 401);
        }

        $request->attributes->set('hms_integration', $integration);

        $pathAndQuery = HmsSignature::pathAndQuery($request);
        $valid = HmsSignature::matches(
            (string) $integration->signing_secret,
            $timestamp,
            $request->getMethod(),
            $pathAndQuery,
            $request->getContent(),
            $signature
        );

        if (! $valid) {
            return $this->fail($request, 'invalid_signature', 'Signature is invalid.', 401);
        }

        $skew = abs(now()->getTimestamp() - (int) $timestamp);
        if ($skew > HmsSignature::MAX_SKEW_SECONDS) {
            return $this->fail($request, 'stale_timestamp', 'Timestamp is outside the allowed window.', 401);
        }

        $replayKey = 'hms-replay:'.$integration->getKey().':'.strtolower($signature);
        if (! Cache::add($replayKey, HmsResponse::requestId($request), now()->addSeconds(HmsSignature::MAX_SKEW_SECONDS))) {
            return $this->fail($request, 'invalid_signature', 'Signature has already been used.', 401);
        }

        if (! $integration->is_enabled) {
            return $this->fail($request, 'invalid_signature', 'Integration is disabled.', 403);
        }

        $allowedIps = $integration->allowed_ips;
        if (is_array($allowedIps) && $allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return $this->fail($request, 'invalid_signature', 'Request IP is not allowed.', 403);
        }

        $limit = max(1, (int) $integration->rate_limit_per_minute);
        $rateKey = 'hms-integration:'.$integration->getKey();
        if (RateLimiter::tooManyAttempts($rateKey, $limit)) {
            return $this->fail($request, 'rate_limited', 'Rate limit exceeded.', 429);
        }
        RateLimiter::hit($rateKey, 60);

        HmsIntegration::query()->whereKey($integration->getKey())->update([
            'last_used_at' => now(),
        ]);

        return $next($request);
    }

    private function fail(Request $request, string $code, string $message, int $status): Response
    {
        return HmsResponse::error($request, $code, $message, $status);
    }
}
