<?php

namespace App\Services;

use App\Models\HIPUser;
use App\Models\Persons;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AuthService
{
    private function getDefaultOrganizationId(): ?string
    {
        $orgId = DB::table('organizations')->orderBy('id')->value('id');

        return $orgId ? (string) $orgId : null;
    }

    private function profileUpdate(HIPUser $user): void
    {
        $requiredFields = [
            'first_name',
            'gender',
            'email',
            'dob',
            'marital_status',
            'blood_group',
            'preferred_branch_id',
        ];

        $allFilled = true;

        foreach ($requiredFields as $field) {
            if ($user->$field === null || $user->$field === '') {
                $allFilled = false;
                break;
            }
        }

        $user->profile_update = $allFilled ? 1 : 0;
        $user->save();
    }

    private function isProfileFieldFilled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    private function buildVerificationUrl(string $token): string
    {
        return rtrim((string) config('app.url'), '/') . '/api/verify-email/' . $token;
    }

    public function sendVerificationEmail(HIPUser $user, string $email): HIPUser
    {
        $email = strtolower(trim($email));

        if (HIPUser::query()
            ->where('email', $email)
            ->where('id', '!=', $user->id)
            ->exists()) {
            abort(422, 'This email is already registered to another account.');
        }

        if (
            $user->email_verified_at !== null
            && strtolower(trim((string) ($user->email ?? ''))) === $email
        ) {
            abort(422, 'This email is already verified.');
        }

        $token = Str::random(64);
        $verificationUrl = $this->buildVerificationUrl($token);

        try {
            return DB::transaction(function () use ($user, $email, $token, $verificationUrl) {
                $user->update([
                    'email'                               => $email,
                    'email_verified_at'                   => null,
                    'email_verification_token'            => $token,
                    'email_verification_token_expires_at' => Carbon::now()->addHours(24),
                ]);

                $person = Persons::where('mobile', $user->mobile_num)->first();
                if ($person) {
                    $person->update(['email' => $email]);
                }

                $user = $user->fresh();

                Mail::to($email)->send(new VerifyEmailMail($user, $verificationUrl));

                return $user;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send email verification', [
                'user_id' => $user->id,
                'email'   => $email,
                'error'   => $e->getMessage(),
            ]);

            $message = app()->environment('local')
                ? 'Failed to send verification email. Check MAIL_* settings in .env (Gmail app password, SSL/CA).'
                : 'Failed to send verification email. Please try again later.';

            abort(500, $message);
        }
    }

    public function verifyEmailToken(string $token): HIPUser
    {
        $user = HIPUser::query()
            ->where('email_verification_token', $token)
            ->first();

        if (!$user) {
            abort(422, 'Invalid or expired verification link.');
        }

        if (
            $user->email_verification_token_expires_at
            && Carbon::parse($user->email_verification_token_expires_at)->lte(Carbon::now())
        ) {
            abort(422, 'Verification link has expired. Please request a new verification email.');
        }

        $user->update([
            'email_verified_at'                  => Carbon::now(),
            'email_verification_token'           => null,
            'email_verification_token_expires_at' => null,
        ]);

        return $user->fresh();
    }

    private function formatUserPayload(HIPUser $user): array
    {
        return [
            'firstName'                        => $user->first_name,
            'lastName'                         => $user->last_name,
            'email'                            => $user->email,
            'gender'                           => $user->gender,
            'dob'                              => $user->dob,
            'mobile'                           => $user->mobile_num,
            'maritalStatus'                    => $user->marital_status,
            'bloodGroup'                       => $user->blood_group,
            'preferredBranchId'              => $user->preferred_branch_id,
            'emergencyContactPersonName'       => $user->emergency_contact_person_name,
            'emergencyContactPersonPhone'      => $user->emergency_contact_person_phone,
            'emergencyContactPersonRelationship' => $user->emergency_contact_person_relationship,
            'houseNumber'                      => $user->house_number,
            'street'                           => $user->street,
            'city'                             => $user->city,
            'state'                            => $user->state,
            'zipCode'                          => $user->zip_code,
            'profile_image'                    => $user->profile_image ? asset('storage/users/' . $user->profile_image) : null,
            'profile_update'                   => (int) $user->profile_update,
            'mobile_verified'                  => $user->mobile_verified_at !== null,
            'email_verified'                   => $user->email_verified_at !== null,
        ];
    }

    public function register(array $data){

        return DB::transaction(function () use ($data){

        if(HIPUser::where('mobile_num',$data['mobile'])->exists()){
            abort(422,'Mobile Number Already Exists , Please Login');
        }

        if(HIPUser::where('email',$data['email'])->exists()){
            abort(422,'Email already Taken');
        }

        $otp = random_int(1000,9999);

        $user = HIPUser::create([
            'first_name'   => $data['firstName'] ?? null,
            'last_name'    => $data['lastName']  ?? null,
            'email'        => $data['email']     ?? null,
            'mobile_num'   => $data['mobile'],
            'gender'       => $data['gender']    ?? null,
            'dob'          => $data['dob']       ?? null,
            'organization_id' => $data['organization_id'] ?? $this->getDefaultOrganizationId(),
            'password'     => null,
            'otp'          => $otp,
            'otp_expires'  => Carbon::now()->addMinutes(5)
        ]);

        $person = persons::where('mobile', $data['mobile'])
            ->whereNull('hip_user_id')
            ->first();

        if ($person) {
            $person->update([
                'hip_user_id' => $user->id,
            ]);
    
            return [
                'otp'     => $otp,
                'user_id' => $user->id,
            ];
        }
        
        if(!$person){
            $person = persons::create([
                'first_name'   => $data['firstName'] ?? null,
                'last_name'    => $data['lastName']  ?? null,
                'email'        => $data['email']     ?? null,
                'mobile'       => $data['mobile'],
                'gender'       => $data['gender']    ?? null,
                'dob'          => $data['dob']       ?? null,
                'hip_user_id'  => $user->id,
                'is_primary'   => true
            ]);

            if($person){
                $person->update([
                    'parent_id' => $person->id
                ]);
            }
        }

        $this->profileUpdate($user);    

        return [
            'otp'      => $otp,
            'user_id'  => $user->id
        ];

        });
    }


    public function otpVerification(array $data){

        $user = HIPUser::find($data['user_id']);
        if (!$user) {
            abort(422, 'User not found. Please login again.');
        }

        $storedOtp = trim((string) ($user->otp ?? ''));
        $submittedOtp = trim((string) ($data['otp'] ?? ''));
        $otpExpiresAt = $user->otp_expires ? Carbon::parse($user->otp_expires) : null;

        if ($storedOtp === '' || $submittedOtp === '' || !hash_equals($storedOtp, $submittedOtp)) {
            abort(422, 'Invalid OTP.');
        }

        if (!$otpExpiresAt || $otpExpiresAt->lte(Carbon::now())) {
            abort(422, 'OTP expired. Please resend OTP.');
        }
        
        $user->update([
            'otp'                 => null,
            'otp_expires'         => null,
            'expires_at'          => Carbon::now()->addMonths(6),
            'mobile_verified_at'  => Carbon::now(),
        ]);

        $user->refresh();
        $this->profileUpdate($user);
        $user->refresh();

        $loginToken = $user->createToken('Login_token', ['*'], Carbon::now()->addMonths(6))->plainTextToken;

        return [
            'token'           => $loginToken,
            'user_id'         => $user->id,
            'profile_update'  => (int) $user->profile_update,
            'mobile_verified' => $user->mobile_verified_at !== null,
            'email_verified'  => $user->email_verified_at !== null,
            'preferred_branch_id' => $user->preferred_branch_id,
        ];
    }

    public function resendOTP(string $user_Id){

        $user = HIPUser::find($user_Id);

        if(!$user){
            abort(422,'User Not Found,Please Register');
        }

        $otp = random_int(1000,9999);

        $user->update([
            'otp'         => $otp,
            'otp_expires' => Carbon::now()->addMinutes(5)
        ]);

        $this->profileUpdate($user);

        return[
            'otp'     => $otp,
            'user_id' => $user->id
        ];

    }

    public function login(array $data)
    {
        $defaultOrganizationId = $this->getDefaultOrganizationId();

        $user = HIPUser::firstOrCreate(
            ['mobile_num' => $data['mobile']],
            [
                'first_name' => null,
                'last_name'  => null,
                'email'      => null,
                'gender'     => null,
                'dob'        => null,
                'password'   => null,
                'organization_id' => $defaultOrganizationId,
            ]
        );

        if (! $user->organization_id && $defaultOrganizationId) {
            $user->organization_id = $defaultOrganizationId;
            $user->save();
        }

        $otp = random_int(1000, 9999);

        $person = Persons::where('mobile', $data['mobile'])
            ->where('hip_user_id', $user->id)
            ->first();

        if (!$person) {
            $person = Persons::where('mobile', $data['mobile'])
                ->whereNull('hip_user_id')
                ->first();
        }

        if (!$person) {
            $person = Persons::create([
                'first_name'  => $data['firstName'] ?? null,
                'last_name'   => $data['lastName']  ?? null,
                'email'       => $data['email']     ?? null,
                'mobile'      => $data['mobile'],
                'gender'      => $data['gender']    ?? null,
                'dob'         => $data['dob']       ?? null,
                'hip_user_id' => $user->id,
                'is_primary'  => true,
                'relationship' => 'self',
            ]);

            $person->parent_id = $person->id;
            $person->save();
        } else {
            if ($person->hip_user_id !== $user->id) {
                $person->update([
                    'hip_user_id' => $user->id,
                    // 'is_primary'  => true,
                ]);
            }
        }

        $user->update([
            'otp'         => $otp,
            'otp_expires' => Carbon::now()->addMinutes(5),
        ]);

        $this->profileUpdate($user);

        return [
            'otp'     => $otp,
            'user_id' => $user->id,
        ];
    }

    public function logout($user){

        if(!$user){
            abort(401, 'User not logged in');
        }

        $user->currentAccessToken()->delete();
        return true;

    }

    public function formUpdate($user, array $data, $imageFile = null)
    {
        $previousEmail = $user->email;
        $newEmail = array_key_exists('email', $data) ? $data['email'] : $user->email;

        $updateData = [
            'first_name'                         => $data['firstName'] ?? $user->first_name,
            'last_name'                          => $data['lastName'] ?? $user->last_name,
            'email'                              => $newEmail,
            'gender'                             => $data['gender'] ?? $user->gender,
            'dob'                                => $data['dob'] ?? $user->dob,
            'marital_status'                     => $data['maritalStatus'] ?? $user->marital_status,
            'blood_group'                        => $data['bloodGroup'] ?? $user->blood_group,
            'preferred_branch_id'                => $data['preferredBranchId'] ?? $user->preferred_branch_id,
            'emergency_contact_person_name'      => $data['emergencyContactPersonName'] ?? $user->emergency_contact_person_name,
            'emergency_contact_person_phone'     => $data['emergencyContactPersonPhone'] ?? $user->emergency_contact_person_phone,
            'emergency_contact_person_relationship' => $data['emergencyContactPersonRelationship'] ?? $user->emergency_contact_person_relationship,
            'house_number'                       => $data['houseNumber'] ?? $user->house_number,
            'street'                             => $data['street'] ?? $user->street,
            'city'                               => $data['city'] ?? $user->city,
            'state'                              => $data['state'] ?? $user->state,
            'zip_code'                           => $data['zipCode'] ?? $user->zip_code,
        ];

        $emailChanged = $this->isProfileFieldFilled($newEmail)
            && (
                !$this->isProfileFieldFilled($previousEmail)
                || strtolower(trim((string) $previousEmail)) !== strtolower(trim((string) $newEmail))
            );

        if ($emailChanged) {
            $updateData['email_verified_at'] = null;
            $updateData['email_verification_token'] = null;
            $updateData['email_verification_token_expires_at'] = null;
        }

        $person = Persons::where('mobile', $user->mobile_num)->first();

        if ($person) {
            $person->update([
                'first_name' => $updateData['first_name'],
                'last_name'  => $updateData['last_name'],
                'email'      => $updateData['email'],
                'gender'     => $updateData['gender'],
                'dob'        => $updateData['dob'],
            ]);
        }

        if ($imageFile) {
            if ($user->profile_image && Storage::disk('public')->exists('users/' . $user->profile_image)) {
                Storage::disk('public')->delete('users/' . $user->profile_image);
            }

            $extension = $imageFile->getClientOriginalExtension();
            $filename = Str::uuid() . '_' . hash('sha256', $user->id . time()) . '.' . $extension;
            $imageFile->storeAs('users', $filename, 'public');
            $updateData['profile_image'] = $filename;

            if ($person) {
                $person->update(['image' => $filename]);
            }
        }

        $user->update($updateData);
        $user->refresh();

        $this->profileUpdate($user);

        return $user->fresh();
    }

    public function getProfile($user): array
    {
        return array_merge(
            ['id' => $user->id],
            $this->formatUserPayload($user)
        );
    }

    // public function deleteUser(HIPUser $user){

    //     DB::transaction(function () use ($user){

    //         $user->tokens()->delete();

    //         if($user->profile_image && Storage::disk('public')->exists('users/' . $user->profile_image)){
    //             Storage::disk('public')->delete('users/' . $user->profile_image);
    //         }
    //         $user->delete();

    //     });

    //     return true;

    // }

    public function getDependentMembers($user)
    {
        $primaryPerson = Persons::where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->first();

        if (!$primaryPerson) {
            return [];
        }

        $dependentMembers = Persons::where('parent_id', $primaryPerson->id)
            ->where('id', '!=', $primaryPerson->id)
            ->get();

        return $dependentMembers->map(function (Persons $member) {
            return [
                'id'            => $member->id,
                'name'          => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')),
                'mobile'        => $member->mobile,
                'gender'        => $member->gender,
                'dob'           => $member->dob,
                'profile_image' => $member->image ? asset('storage/users/' . $member->image) : null,
            ];
        })->values()->all();
    }

    public function updateDependentMember($user, $id, $imageFile = null)
    {
        $dependentMember = Persons::find($id);
        if (!$dependentMember) {
            abort(404, 'Dependent member not found');
        }

        if ($imageFile) {
            if ($dependentMember->image && Storage::disk('public')->exists('users/' . $dependentMember->image)) {
                Storage::disk('public')->delete('users/' . $dependentMember->image);
            }
            $extension = $imageFile->getClientOriginalExtension();
            $filename = Str::uuid() . '_' . hash('sha256', $dependentMember->id . time()) . '.' . $extension;
            $imageFile->storeAs('users', $filename, 'public');
            $dependentMember->image = $filename;
        }

        $dependentMember->save();
        return $dependentMember;
    }
}
