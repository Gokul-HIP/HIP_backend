<?php

namespace App\Services\Hms;

use Illuminate\Http\Request;

class HmsSignature
{
    public const MAX_SKEW_SECONDS = 300;

    public static function pathAndQuery(Request $request): string
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');
        // Use the raw query string. Symfony's getQueryString() sorts and re-encodes it,
        // which would not match the bytes the client signed.
        $query = $request->server->get('QUERY_STRING');

        return is_string($query) && $query !== '' ? $path.'?'.$query : $path;
    }

    public static function canonical(string $timestamp, string $method, string $pathAndQuery, string $rawBody): string
    {
        return $timestamp.'.'.strtoupper($method).'.'.$pathAndQuery.'.'.$rawBody;
    }

    public static function sign(string $secret, string $timestamp, string $method, string $pathAndQuery, string $rawBody): string
    {
        return hash_hmac('sha256', self::canonical($timestamp, $method, $pathAndQuery, $rawBody), $secret);
    }

    public static function matches(string $secret, string $timestamp, string $method, string $pathAndQuery, string $rawBody, string $provided): bool
    {
        $expected = self::sign($secret, $timestamp, $method, $pathAndQuery, $rawBody);
        $provided = strtolower(trim($provided));

        return strlen($provided) === strlen($expected) && hash_equals($expected, $provided);
    }
}
