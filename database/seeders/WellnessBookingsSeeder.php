<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\HIPUser;

class WellnessBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $members = HIPUser::pluck('id')->toArray();
        $member1 = $members[0] ?? null;
        $member2 = $members[1] ?? $member1;
        DB::table('wellness_bookings')->insert([

            [
                'name' => 'Aarthi Sharma',
                'mobile_number' => '9856543218',
                'member_id' => $member1,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'pending',
                'purpose' => 'wants to know more',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Neha Thakur',
                'mobile_number' => '9876543211',
                'member_id' => $member2,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'confirmed',
                'purpose' => 'Regular wellness checkup',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Abhishek V',
                'mobile_number' => '9876543212',
                'member_id' => $member1,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'pending',
                'purpose' => 'Fitness consultation',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rajesh Kumar',
                'mobile_number' => '9876543213',
                'member_id' => $member2,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'completed',
                'purpose' => 'Mental health session',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Anita Desai',
                'mobile_number' => '9876543214',
                'member_id' => $member1,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'cancelled',
                'purpose' => 'Yoga class booking',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Vikram Singh',
                'mobile_number' => '9876543215',
                'member_id' => $member2,
                'center_id' => 1,
                'consultation_type' => 'In-Person',
                'status' => 'confirmed',
                'purpose' => 'Employee coaching session',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Priya Patel',
                'mobile_number' => '9876543216',
                'member_id' => $member1,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'pending',
                'purpose' => 'Wellness assessment',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Suresh Reddy',
                'mobile_number' => '9876543217',
                'member_id' => $member2,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'confirmed',
                'purpose' => 'Physical therapy session',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Meera Nair',
                'mobile_number' => '9876543218',
                'member_id' => $member1,
                'center_id' => 1,
                'consultation_type' => 'In-Person',

                'status' => 'pending',
                'purpose' => 'Nutrition consultation',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Kiran Rao',
                'mobile_number' => '9876543219',
                'member_id' => $member2,
                'center_id' => 1,
                'consultation_type' => 'In-Person',
                'status' => 'completed',
                'purpose' => 'Stress management session',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}

