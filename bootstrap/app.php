<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\HandleCors;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Exceptions\HmsClientException;
use App\Http\Middleware\AuthenticateHmsClient;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Services\Hms\HmsResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            // HMS client API is intentionally outside the "api" prefix.
            // Booking and payment webhooks will be added in routes/hms.php.
            \Illuminate\Support\Facades\Route::prefix('apiv1/client')
                ->group(base_path('routes/hms.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->web(append: [
            PreventBackHistory::class,
        ]);

        $middleware->append(HandleCors::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'redirect.if.filament.user' => RedirectIfAuthenticated::class,
            'account.active' => EnsureAccountIsActive::class,
            'hms.client' => AuthenticateHmsClient::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->is('apiv1/client/*') || $request->expectsJson()) {
                return null;
            }

            return route('admin.auth.login');
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->is('apiv1/client/*') || $request->expectsJson();
        });

        $exceptions->render(function (HmsClientException $e, Request $request) {
            if ($request->is('apiv1/client/*')) {
                return HmsResponse::error($request, $e->errorCode, $e->getMessage(), $e->statusCode, $e->context);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Unauthenticated. Please login and send a valid Bearer token.',
                ], 401);
            }
        });
    })
    ->create();
