<?php

namespace App\Services;

use App\Models\HIPUser;
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

        $this->profileUpdate($user);    

        return [
            'otp'      => $otp,
            'user_id'  => $user->id
        ];

    }


    public function otpVerification(array $data){

        $user = HIPUser::where('id',$data['user_id'])
             ->where('otp',$data['otp'])
                 ->where('otp_expires','>',Carbon::now())->first();
         
        if(!$user){
            abort(422,'Invalid or Expired OTP Please Enter OTP Befor 5 Minuts');
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

    public function resendOTP(int $user_Id){

        $user = HIPUser::find( $user_Id);

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


    public function login(array $data){

        $user = HIPUser::where('mobile_num', $data['mobile'])->first();

        if(!$user){
            $user = HIPUser::create([
                'mobile_num'  => $data['mobile'],
                'first_name'  => null,
                'last_name'   => null,
                'email'       => null,
                'gender'      => null,
                'dob'         => null,
                'password'    => null,
            ]);
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

        if ($imageFile) {
         
            if ($user->profile_image && Storage::disk('public')->exists('users/' . $user->profile_image)) {
                Storage::disk('public')->delete('users/' . $user->profile_image);
            }

            $extension = $imageFile->getClientOriginalExtension();
            $filename = Str::uuid() . '_' . hash('sha256', $user->id . time()) . '.' . $extension;
            $imageFile->storeAs('users', $filename, 'public');
            $updateData['profile_image'] = $filename;
        }

        $user->update($updateData);
        $this->profileUpdate($user);

        return $user;
    }

    public function getProfile($user)
    {
        return [
            'firstName'      => $user->first_name,
            'lastName'       => $user->last_name,
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

}