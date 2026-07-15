<?php

namespace App\Http\Controllers\Api\Automation;

use App\Http\Controllers\Controller;
use App\Models\HIPUser;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AutomationController extends Controller
{
    public function UILogo()
    {
        $logo = app_logo_url();

        if (! $logo) {
            return response()->json([
                'status' => false,
                'message' => 'Logo not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Logo fetched successfully',
            'logo' => $logo,
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $user = HIPUser::where('email', $request->email)->first();

            if (! $user) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'User not found',
                ], 404);
            }

            if (! $user->hasRole('automation-admin', 'filament')) {
                return response()->json([
                    'status_code' => 403,
                    'message' => 'Access denied. Automation admin role required.',
                ], 403);
            }

            if (! $user->password || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $token = $user->createToken(
                'automation_admin_token',
                ['*'],
                Carbon::now()->addMonths(6)
            )->plainTextToken;

            return response()->json([
                'status_code' => 200,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Automation admin login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof HIPUser) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! $user->hasRole('automation-admin', 'filament')) {
            return response()->json([
                'status_code' => 403,
                'message' => 'Access denied. Automation admin role required.',
            ], 403);
        }

        $user->currentAccessToken()?->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Logged out successfully',
        ], 200);
    }
}
