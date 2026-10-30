<?php

namespace App\Services\Hms;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HmsResponse
{
    public static function error(Request $request, string $code, string $message, int $status): JsonResponse
    {
        $requestId = self::requestId($request);

        return response()->json([
            'status' => false,
            'code' => $code,
            'message' => $message,
            'request_id' => $requestId,
        ], $status)->header('X-Request-Id', $requestId);
    }

    public static function ok(Request $request, array $payload): JsonResponse
    {
        return response()->json($payload)->header('X-Request-Id', self::requestId($request));
    }

    public static function requestId(Request $request): string
    {
        return (string) $request->attributes->get('hms_request_id', '');
    }
}
