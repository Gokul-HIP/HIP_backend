<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorBookingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('doctor_bookings')->insert([

            [
                'name' => 'Ramesh Kumar',
                'mobile_number' => '9876543210',
                'member_id' => 10,
                'hospital_id' => 4,
                'doctor_id' => 4,
                'booking_date' => Carbon::now()->addDay(),

                'required_time_slots' => json_encode([
                    '10:00 AM',
                    '10:30 AM'
                ]),

                'status' => 'pending',

                'created_by' => 10,
                'updated_by' => 10,

                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Sita Devi',
                'mobile_number' => '9876543222',
                'member_id' => 11,
                'hospital_id' => 4,
                'doctor_id' => 8,
                'booking_date' => Carbon::now()->addDays(2),

                'required_time_slots' => json_encode([
                    '11:00 AM'
                ]),

                'status' => 'confirmed',

                'created_by' => 11,
                'updated_by' => 11,

                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
