<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\HIPUser;

class DiagnosticTestBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $members = HIPUser::pluck('id')->toArray();
        $member1 = $members[0] ?? null;
        $member2 = $members[1] ?? $member1;

        DB::table('diagnostic_test_bookings')->insert([

            [
                'name' => 'Aarthi Sharma',
                'mobile_number' => '9876543001',
                'member_id' => $member1,
                'diagnostic_center_id' => 1,

                'test_type' => 'single',
                'test_items' => json_encode([
                    [
                        'code' => 'PROC1001',
                        'name' => 'COVID-19 RT-PCR'
                    ]
                ]),

                'sample_collection' => 'home',
                'booking_date' => Carbon::now()->addDay(),

                'required_time_slots' => json_encode([
                    '09:00 AM',
                    '09:30 AM'
                ]),

                'status' => 'pending',
                'purpose' => 'Doctor recommended COVID test',

                'created_by' => $member1,
                'updated_by' => $member1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Neha Thakur',
                'mobile_number' => '9876543002',
                'member_id' => $member2,
                'diagnostic_center_id' => 1,

                'test_type' => 'multi',
                'test_items' => json_encode([
                    [
                        'code' => 'PROC2001',
                        'name' => 'Thyroid Profile'
                    ],
                    [
                        'code' => 'PROC2002',
                        'name' => 'Liver Function Test'
                    ],
                    [
                        'code' => 'PROC2003',
                        'name' => 'Blood Sugar (Fasting)'
                    ]
                ]),

                'sample_collection' => 'lab',
                'booking_date' => Carbon::now()->addDays(2),

                'required_time_slots' => json_encode([
                    '11:00 AM'
                ]),

                'status' => 'confirmed',
                'purpose' => 'Routine health checkup',

                'created_by' => $member2,
                'updated_by' => $member2,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Abhishek Verma',
                'mobile_number' => '9876543003',
                'member_id' => $member1,
                'diagnostic_center_id' => 2,

                'test_type' => 'package',
                'test_items' => json_encode([
                    'package_id' => 'PKG001',
                    'package_name' => 'Full Body Checkup',
                    'included_tests' => [
                        'Blood Test',
                        'ECG',
                        'X-Ray',
                        'Urine Test'
                    ]
                ]),

                'sample_collection' => 'home',
                'booking_date' => Carbon::now()->addDays(3),

                'required_time_slots' => json_encode([
                    '08:00 AM',
                    '08:30 AM'
                ]),

                'status' => 'completed',
                'purpose' => 'Annual preventive health package',

                'created_by' => $member1,
                'updated_by' => $member1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
