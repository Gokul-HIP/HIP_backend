<?php

if (!function_exists('sendApiResponse')) {
    function sendApiResponse($status, $message, $data = null, $total = null, $page = null, $page_limit = null, $errors = null)
    {
        $statusMessage = 'success';
        if ($status >= 200 && $status < 300) {
            $statusMessage = 'success';
        } else {
            $statusMessage = 'failed';
        }
        $data = [
            'status' => $statusMessage,
            'message' => $message,
            'data' => $data,
        ];
        if (env('DEBUG', true) && $errors != null) {
            $data['errors'] = $errors;
        }
        if ($total) {
            $data['total'] = $total;
        }
        if ($page) {
            $data['page'] = $page;
        }
        if ($page_limit) {
            $data['page_limit'] = $page_limit;
        }

        return response()->json($data, $status);
    }
}

if (!function_exists('versioned_asset')) {
    /**
     * Public asset URL with file modification time for cache busting.
     */
    function versioned_asset(string $path): string
    {
        $normalized = ltrim($path, '/');
        $fullPath = public_path($normalized);
        $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();

        return asset($normalized) . '?v=' . $version;
    }
}

if (!function_exists('app_setting')) {
    /**
     * Read a setting from the database (cached), with .env / config fallback.
     */
    function app_setting(string $key, mixed $default = null): mixed
    {
        try {
            if (!app()->bound('db')) {
                return $default;
            }

            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return $default;
            }
        } catch (\Throwable) {
            return $default;
        }

        return \App\Models\Setting::get($key, $default);
    }
}
