<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     * 
     * This middleware prevents authenticated users from accessing login pages
     * and redirects them to their appropriate dashboard
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? ['filament'] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Don't redirect if already on a dashboard route
                if ($request->is('admin') || $request->is('admin/*') || 
                    $request->is('hospital') || $request->is('hospital/*') ||
                    $request->is('master') || $request->is('master/*')) {
                    return $next($request);
                }
                
                // Redirect to appropriate dashboard based on role
                // super-admin-hip → custom dashboard
                // super-admin → should use Filament panel, redirect there if trying to access custom dashboard
                if ($user->hasRole('super-admin-hip')) {
                    return redirect()->route('admin.dashboard.index');
                }
                
                if ($user->hasRole('super-admin')) {
                    return redirect('/master');
                }
                
                if ($user->hasRole('hospital_admin')) {
                    return redirect()->route('hospital.dashboard.index');
                }
                
                // Default redirect - logout if no valid role
                Auth::guard($guard)->logout();
                return redirect()->route('admin.auth.login')->with('error', 'No valid role assigned.');
            }
        }

        return $next($request);
    }
}