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
                'booking_date' => Carbon::now()->addDay(),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '11:00 AM',
                    '12:00 PM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(2),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '10:30 AM',
                    '11:30 AM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(3),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '2:00 PM',
                    '3:00 PM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(4),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '9:00 AM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(5),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '4:00 PM',
                    '5:00 PM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(6),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '1:00 PM',
                    '2:00 PM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(7),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '3:00 PM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(8),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '10:00 AM',
                    '11:00 AM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(9),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '5:00 PM',
                    '6:00 PM'
                ]),
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
                'booking_date' => Carbon::now()->addDays(10),
                'consultation_type' => 'In-Person',
                'required_time_slots' => json_encode([
                    '2:30 PM'
                ]),
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

