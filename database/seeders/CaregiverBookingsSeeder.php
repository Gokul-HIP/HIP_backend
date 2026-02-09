<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\HIPUser;
use App\Models\CareGiver;
use App\Models\WellnessCenters;

class CaregiverBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $members = HIPUser::pluck('id')->toArray();
        $caregivers = CareGiver::pluck('id')->toArray();
        $centers = WellnessCenters::pluck('id')->toArray();

        if (empty($members) || empty($caregivers) || empty($centers)) {
            $this->command->warn('Skipping CaregiverBookingsSeeder - Required data missing.');
            return;
        }

        DB::table('caregiver_bookings')->insert([
            
            [
                'name' => 'Ravi Kumar',
                'mobile_number' => '9876543210',
                'member_id' => $members[0],
                'caregiver_id' => $caregivers[0],
                'wellness_center_id' => $centers[0],
                'booking_date' => Carbon::now()->addDay(),

                'required_time_slots' => json_encode([
                    '10:00 AM',
                    '11:00 AM'
                ]),

                'status' => 'pending',
                'purpose' => 'Home care assistance for elderly patient',

                'created_by' => $members[0],
                'updated_by' => $members[0],

                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Priya Sharma',
                'mobile_number' => '9123456780',
                'member_id' => $members[1] ?? $members[0],
                'caregiver_id' => $caregivers[1] ?? $caregivers[0],
                'wellness_center_id' => $centers[1] ?? $centers[0],
                'booking_date' => Carbon::now()->addDays(2),

                'required_time_slots' => json_encode([
                    '02:00 PM',
                    '03:00 PM'
                ]),

                'status' => 'confirmed',
                'purpose' => 'Post-surgery recovery support',

                'created_by' => $members[0],
                'updated_by' => $members[0],

                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Anil Verma',
                'mobile_number' => '9988776655',
                'member_id' => $members[0],
                'caregiver_id' => $caregivers[0],
                'wellness_center_id' => $centers[0],
                'booking_date' => Carbon::now()->subDay(),

                'required_time_slots' => json_encode([
                    '09:00 AM'
                ]),

                'status' => 'completed',
                'purpose' => 'Daily nursing care',

                'created_by' => $members[0],
                'updated_by' => $members[0],

                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
