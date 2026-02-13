<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;

class addDoctorSeeder extends Seeder
{
    public function run()
    {
        
        for ($i = 0; $i < 10; $i++) {
            Doctor::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->email(),
                'mobile_number' => rand(1000000000, 9999999999),
                'qualifications' => json_encode([1, 2]),
                'speciality' => json_encode([1, 2]),
                'hospital_ids' => json_encode([4]),
                'status' => 'active',
                'doctor_image' => '39d0cddd-9586-4d09-ab53-417515ff243a_c4948b475f31a042bf5fa62e22a373fda368f217dd6a5e3c4976573bf4b5dead.png',
                'gender' => 'male',
                'organization_id' => 1,
            ]);
        }
    }
}