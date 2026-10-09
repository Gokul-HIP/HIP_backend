<?php

namespace App\Http\Middleware;

use App\Models\HIPUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public const MESSAGE = 'Your account has been deactivated. Please contact support.';

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('hip.enforce_active')) {
            return $next($request);
        }

        $user = $this->authenticatedUser($request);

        if (! $user instanceof HIPUser || (bool) ($user->is_active ?? true)) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => self::MESSAGE,
            ], 403);
        }

        $loginRoute = $this->loginRoute($user);
        Auth::guard('filament')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute)->with('error', self::MESSAGE);
    }

    protected function authenticatedUser(Request $request): mixed
    {
        if ($request->user()) {
            return $request->user();
        }

        foreach (['filament', 'sanctum', 'web'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    protected function loginRoute(HIPUser $user): string
    {
        foreach ([
            'healthcare_admin' => 'healthcare.auth.login',
            'cashier_admin' => 'cashier.auth.login',
            'pharmacist' => 'pharmacist.auth.login',
            'technician' => 'technician.auth.login',
            'receptionist' => 'receptionist.auth.login',
            'doctor' => 'doctor.auth.login',
        ] as $role => $route) {
            if ($user->hasRole($role)) {
                return $route;
            }
        }

        return 'admin.auth.login';
    }
}
