<?php

use App\Models\HIPUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->foreignUuid('member_user_id')
                ->nullable()
                ->after('referred_to_doctor_id')
                ->constrained('healthinpocket_users')
                ->nullOnDelete();
        });

        // DB::table('referrals')
        //     ->whereNull('member_user_id')
        //     ->orderBy('id')
        //     ->chunkById(100, function ($referrals): void {
        //         foreach ($referrals as $referral) {
        //             $member = null;

        //             if (!empty($referral->insurance_member_id)) {
        //                 $member = HIPUser::query()
        //                     ->where('hip_id', $referral->insurance_member_id)
        //                     ->first();
        //             }

        //             if (!$member && !empty($referral->phone_number)) {
        //                 $digits = preg_replace('/\D+/', '', $referral->phone_number) ?: '';
        //                 $lastTenDigits = strlen($digits) > 10 ? substr($digits, -10) : $digits;
        //                 $candidates = array_values(array_unique(array_filter([
        //                     $referral->phone_number,
        //                     $digits,
        //                     $lastTenDigits,
        //                 ])));

        //                 if ($candidates !== []) {
        //                     $member = HIPUser::query()
        //                         ->whereIn('mobile_num', $candidates)
        //                         ->first();
        //                 }
        //             }

        //             if (!$member) {
        //                 continue;
        //             }

        //             DB::table('referrals')
        //                 ->where('id', $referral->id)
        //                 ->update([
        //                     'member_user_id' => $member->id,
        //                     'insurance_member_id' => $member->hip_id,
        //                 ]);
        //         }
        //     });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_user_id');
        });
    }
};
