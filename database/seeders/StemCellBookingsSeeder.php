<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\HIPUser;

class StemCellBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $members = HIPUser::pluck('id')->toArray();
        $member1 = $members[0] ?? null;
        $member2 = $members[1] ?? $member1;
        
        DB::table('stem_cell_bookings')->insert([

            [
                'name' => 'Rajesh Kumar',
                'mobile_number' => '9876543210',
                'member_id' => $member1,
                'booking_date' => Carbon::now()->addDay(),
                'required_time_slots' => json_encode([
                    '10:00 AM',
                    '11:00 AM'
                ]),
                'status' => 'enquiry',
                'purpose' => 'Stem cell therapy consultation for knee injury',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Priya Sharma',
                'mobile_number' => '9876543211',
                'member_id' => $member2,
                'booking_date' => Carbon::now()->addDays(2),
                'required_time_slots' => json_encode([
                    '2:00 PM',
                    '3:00 PM'
                ]),
                'status' => 'enquiry',
                'purpose' => 'Stem cell treatment for chronic back pain',
                'created_by' => 4,
                'updated_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}

