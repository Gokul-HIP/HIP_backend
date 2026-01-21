<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'firstName'   => 'nullable|string|max:255',
            'lastName'    => 'nullable|string|max:255',
            'email'       => 'required|email',
            'mobile'      => 'required',
            'gender'      => 'nullable|string',
            'dob'         => 'nullable|date',
        ]);

        try {
            $result = $this->authService->register($data);
            
            return response()->json([
                'status_code' => 201,
                'message'     => 'OTP Send Successfully',
                'otp'         => $result['otp'],
                'user_id'     => $result['user_id'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 422,
                'message'     => $e->getMessage(),
            ], 422);
        }
    }

    public function otpVerification(Request $request)
    {
        $data = $request->validate([
            'user_id'  => 'required|string',
            'otp'      => 'required|string'
        ]);

        try {
            $result = $this->authService->otpVerification($data);
            
            return response()->json([
                'status_code'     => 200,
                'message'         => 'Logged In successfully',
                'token'           => $result['token'],
                'profile_update'  => $result['profile_update']
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 422,
                'message'     => $e->getMessage(),
            ], 422);
        }
    }

    public function resendOTP(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer'
        ]);

        try {
            $result = $this->authService->resendOTP($data['user_id']);
            
            return response()->json([
                'status_code' => 200,
                'message'     => 'OTP Send Successfully',
                'otp'         => $result['otp'],
                'user_id'     => $result['user_id']
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 422,
                'message'     => $e->getMessage(),
            ], 422);
        }
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'mobile' => 'required|min:10'
        ]);

        try {
            $result = $this->authService->login($data);
            
            return response()->json([
                'status_code' => 200,
                'message'     => 'OTP Send Successfully',
                'otp'         => $result['otp'],
                'user_id'     => $result['user_id']
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 422,
                'message'     => $e->getMessage(),
            ], 422);
        }
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'status_code' => 200,
            'message' => 'Logged out successfully',
        ]);
    }

    public function formUpdate(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'firstName'     => 'nullable|string|max:255|min:3',
            'lastName'      => 'nullable|string|max:255',
            'email'         => 'nullable|email|unique:healthinpocket_users,email,' . $user->id,
            'gender'        => 'nullable|string',
            'dob'           => 'nullable|string|date',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png|max:10240'
        ]);

        $imageFile = $request->hasFile('profile_image') ? $request->file('profile_image') : null;
        
        $user = $this->authService->formUpdate($user, $data, $imageFile);

        return response()->json([
            'status_code'     => 200,
            'message'         => 'Profile updated successfully',
            'user'            => [
                'firstName'      => $user->first_name,
                'lastName'       => $user->last_name,
                'email'          => $user->email,
                'gender'         => $user->gender,
                'dob'            => $user->dob,
                'mobile'         => $user->mobile_num,
                'profile_image'  => $user->profile_image ? asset('storage/users/' . $user->profile_image) : null,
                'profile_update' => $user->profile_update,
            ]
        ], 200);
    }

    public function userProfile(Request $request)
    {
        $user = $request->user();
        $profile = $this->authService->getProfile($user);

        return response()->json([
            'status_code' => 200,
            'message'     => 'User Data Fetch Successfully',
            'user'        => $profile
        ], 200);
    }

    // public function deleteUser(Request $request)
    // {
    //     $user = $request->user();

    //     if (!$user) {
    //         return response()->json([
    //             'status_code' => 401,
    //             'message' => 'Unauthenticated',
    //         ], 401);
    //     }

    //     $this->authService->deleteUser($user);

    //     return response()->json([
    //         'status_code' => 200,
    //         'message' => 'User deleted successfully',
    //     ]);
    // }
}
