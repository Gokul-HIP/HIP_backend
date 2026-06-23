<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HIPUser;
use App\Models\Organization;
use App\Models\Persons;
use App\Models\PersonalAccessToken;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            'firstName' => 'nullable|string|max:255',
            'lastName'  => 'nullable|string|max:255',
            'email'     => 'required|email',
            'mobile'    => 'required|string',
            'gender'    => 'nullable|string',
            'dob'       => 'nullable|date',
        ]);

        $firstOrganizationId = Organization::query()->orderBy('id')->value('id');
        if (! $firstOrganizationId) {
            return response()->json([
                'status_code' => 422,
                'message' => 'No organization found for registration.',
            ], 422);
        }

        $data['organization_id'] = (string) $firstOrganizationId;

        try {
            $result = $this->authService->register($data);

            return response()->json([
                'status_code' => 201,
                'message'     => 'OTP Sent Successfully',
                'otp'         => $result['otp'],
                'user_id'     => $result['user_id'],
            ], 201);

        } catch (HttpException $e) {
            return response()->json([
                'status_code' => $e->getStatusCode(),
                'message'     => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    public function otpVerification(Request $request)
    {
        $request->merge([
            'user_id' => $request->input('user_id', $request->input('userId')),
            'otp' => preg_replace('/\D+/', '', (string) $request->input('otp')),
        ]);

        $data = $request->validate([
            'user_id'  => 'required|uuid|exists:healthinpocket_users,id',
            'otp'      => 'required|digits:4',
        ]);

        try {
            $result = $this->authService->otpVerification($data);
            
            return response()->json([
                'status_code'     => 200,
                'message'         => 'Logged In successfully',
                'token'           => $result['token'],
                'profile_update'  => $result['profile_update'],
                'mobile_verified' => $result['mobile_verified'],
                'email_verified'  => $result['email_verified'],
                'preferred_branch_id' => $result['preferred_branch_id'] ?? null,
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
            'user_id' => 'required|uuid|exists:healthinpocket_users,id',
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
        $request->validate([
            'device_id' => 'required|string'
        ]);

        UserDevice::where('user_id', $request->user()->id)
        ->where('device_id', $request->device_id)
        ->delete();

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
            'firstName'                        => 'nullable|string|max:255|min:3',
            'lastName'                         => 'nullable|string|max:255',
            'email'                            => 'nullable|email|unique:healthinpocket_users,email,' . $user->id,
            'gender'                           => 'nullable|string',
            'dob'                              => 'nullable|date',
            'maritalStatus'                    => 'nullable|string|max:50',
            'bloodGroup'                       => 'nullable|string|max:10',
            'preferredBranchId'                => 'nullable|integer|exists:hospitals,id',
            'emergencyContactPersonName'       => 'nullable|string|max:255',
            'emergencyContactPersonPhone'      => 'nullable|string|max:20',
            'emergencyContactPersonRelationship' => 'nullable|string|max:100',
            'houseNumber'                      => 'nullable|string|max:100',
            'street'                           => 'nullable|string|max:255',
            'city'                             => 'nullable|string|max:100',
            'state'                            => 'nullable|string|max:100',
            'zipCode'                          => 'nullable|string|max:20',
            'profile_image'                    => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        $imageFile = $request->hasFile('profile_image') ? $request->file('profile_image') : null;

        $user = $this->authService->formUpdate($user, $data, $imageFile);
        $profile = $this->authService->getProfile($user);

        return response()->json([
            'status_code'     => 200,
            'message'         => 'Profile updated successfully',
            'user'            => $profile,
            'mobile_verified' => $profile['mobile_verified'],
            'email_verified'  => $profile['email_verified'],
        ], 200);
    }

    public function sendVerificationEmail(Request $request)
    {
        $request->merge([
            'user_id' => $request->input('user_id', $request->input('userId')),
        ]);

        $data = $request->validate([
            'email'   => 'required|email',
            'user_id' => 'nullable|uuid',
        ]);

        $user = $this->resolveHipUserFromRequest($request, $data['user_id'] ?? null);

        if (! $user instanceof HIPUser) {
            $statusCode = ! empty($data['user_id']) ? 404 : 401;
            $message = ! empty($data['user_id'])
                ? 'User not found for the provided user_id. Use the account user id or primary person id.'
                : 'Unauthenticated. Provide a valid Bearer token or user_id.';

            return response()->json([
                'status_code' => $statusCode,
                'message'     => $message,
            ], $statusCode);
        }

        try {
            $user = $this->authService->sendVerificationEmail($user, $data['email']);
            $profile = $this->authService->getProfile($user);

            return response()->json([
                'status_code'     => 200,
                'message'         => 'Verification email sent successfully. Please check your inbox.',
                'user'            => $profile,
                'mobile_verified' => $profile['mobile_verified'],
                'email_verified'  => $profile['email_verified'],
            ], 200);
        } catch (HttpException $e) {
            return response()->json([
                'status_code' => $e->getStatusCode(),
                'message'     => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 422,
                'message'     => $e->getMessage(),
            ], 422);
        }
    }

    public function verifyEmail(Request $request, string $token)
    {
        try {
            $user = $this->authService->verifyEmailToken($token);

            if ($request->expectsJson()) {
                return response()->json([
                    'status_code'     => 200,
                    'message'         => 'Email verified successfully',
                    'email_verified'  => $user->email_verified_at !== null,
                    'mobile_verified' => $user->mobile_verified_at !== null,
                ], 200);
            }

            $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return response()->view('auth.email-verification-result', [
                'success'  => true,
                'userName' => $userName !== '' ? $userName : null,
                'message'  => 'Email verified successfully',
            ]);
        } catch (HttpException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status_code' => $e->getStatusCode(),
                    'message'     => $e->getMessage(),
                ], $e->getStatusCode());
            }

            return response()->view('auth.email-verification-result', [
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode() >= 400 ? $e->getStatusCode() : 422);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status_code' => 422,
                    'message'     => $e->getMessage(),
                ], 422);
            }

            return response()->view('auth.email-verification-result', [
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function userProfile(Request $request)
    {
        $user = $request->user();
        $profile = $this->authService->getProfile($user);

        return response()->json([
            'status_code'     => 200,
            'message'         => 'User Data Fetch Successfully',
            'user'            => $profile,
            'mobile_verified' => $profile['mobile_verified'],
            'email_verified'  => $profile['email_verified'],
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

    public function getDependentMembers(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        try {
            $dependentMembers = $this->authService->getDependentMembers($user);
            return response()->json([
                'status_code' => 200,
                'message' => 'Dependent members fetched successfully',
                'dependent_members' => $dependentMembers
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to fetch dependent members',
            ], 500);
        }
    }

    private function resolveHipUserFromRequest(Request $request, ?string $userId = null): ?HIPUser
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);

            if ($accessToken?->tokenable instanceof HIPUser) {
                return $accessToken->tokenable;
            }
        }

        if ($userId) {
            $user = HIPUser::find($userId);

            if ($user instanceof HIPUser) {
                return $user;
            }

            $person = Persons::find($userId);

            if ($person?->hip_user_id) {
                return HIPUser::find($person->hip_user_id);
            }
        }

        return null;
    }

    public function updateDependentMember(Request $request){

        $request->validate([
            'id' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:10240'
        ]);

        $imageFile = $request->hasFile('image') ? $request->file('image') : null;

        $dependentMember = $this->authService->updateDependentMember($request->user(), $request->id, $imageFile);

        return response()->json([
            'status_code' => 200,
            'message' => 'Dependent member updated successfully',
        ], 200);
    }

    public function emailverified(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof HIPUser) {
            return response()->json([
                'status_code' => 401,
                'message'     => 'Unauthenticated. Please login and send a valid Bearer token.',
            ], 401);
        }

        return response()->json([
            'status_code'    => 200,
            'email_verified' => $user->email_verified_at !== null,
        ], 200);
    }

}
