<?php

namespace App\Services;

use App\Models\HIPUser;
use App\Models\Persons;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AuthService
{
    private function profileUpdate(HIPUser $user)
    {
        $requiredFields = ['first_name', 'last_name', 'gender', 'dob'];
        $allFilled = true;

        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                $allFilled = false;
                break;
            }
        }

        $user->profile_update = $allFilled ? 1 : 0;
        $user->save();
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
            'otp'           => null,
            'otp_expires'   => null,
            'expires_at'    => Carbon::now()->addMonths(6)
        ]);

        $this->profileUpdate($user);

        $loginToken = $user->createToken('Login_token', ['*'], Carbon::now()->addMonths(6))->plainTextToken;

        return[
            'token'          => $loginToken,
            'user_id'        => $user->id,
            'profile_update' => $user->profile_update
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
        $user = HIPUser::firstOrCreate(
            ['mobile_num' => $data['mobile']],
            [
                'first_name' => null,
                'last_name'  => null,
                'email'      => null,
                'gender'     => null,
                'dob'        => null,
                'password'   => null,
            ]
        );

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
        $updateData = [
            'first_name' => $data['firstName'] ?? $user->first_name,
            'last_name'  => $data['lastName'] ?? $user->last_name,
            'email'      => $data['email'] ?? $user->email,
            'gender'     => $data['gender'] ?? $user->gender,
            'dob'        => $data['dob'] ?? $user->dob,
        ];

        $person = persons::where('mobile', $user->mobile_num)->first();

        if ($person) {
            $person->update([
                'first_name' => $data['firstName'] ?? $person->first_name,
                'last_name'  => $data['lastName'] ?? $person->last_name,
                'email'      => $data['email'] ?? $person->email,
                'gender'     => $data['gender'] ?? $person->gender,
                'dob'        => $data['dob'] ?? $person->dob,
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
            if($person){
                $person->update([
                    'image' => $filename,
                ]);
            }
        }

        $user->update($updateData);
        $this->profileUpdate($user);

        return $user;
    }

    public function getProfile($user)
    {
        return [
            'id'             => $user->id,
            'first_name'     => $user->first_name,
            'last_name'      => $user->last_name,
            'email'          => $user->email,
            'mobile'         => $user->mobile_num,
            'gender'         => $user->gender,
            'dob'            => $user->dob,
            'profile_image'  => $user->profile_image ? asset('storage/users/' . $user->profile_image) : null,
            'profile_update' => $user->profile_update
        ];
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
