<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcedureMaster;
use App\Models\SpecialitiesMaster;
use Faker\Factory as Faker;

class ProcedureMasterSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $specialityMasterIds = SpecialitiesMaster::pluck('id')->toArray();

        if (empty($specialityMasterIds)) {
            $this->command->error('No speciality masters found. Seed SpecialitiesMaster first.');
            return;
        }

        $procedureTypes = [
            'Blood Test', 'X-Ray', 'MRI Scan', 'CT Scan', 'Ultrasound',
            'ECG', 'Echocardiogram', 'Endoscopy', 'Colonoscopy', 'Biopsy',
            'Physical Examination', 'Dialysis Session', 'Chemotherapy Session',
            'Radiation Therapy', 'Minor Surgery'
        ];

        $modifiers = ['Basic', 'Advanced', 'Routine', 'Emergency', 'Preventive'];

        for ($i = 0; $i < 100; $i++) {

            $name =
                $faker->randomElement($modifiers) . ' ' .
                $faker->randomElement($procedureTypes);

            ProcedureMaster::create([
                'name' => $name . ' ' . ($i + 1),
                'duration' => $faker->randomElement([15, 30, 45, 60, 90, 120]),
                'speciality_master_id' => $faker->randomElement($specialityMasterIds),
                'cost' => round($faker->numberBetween(500, 50000) / 100) * 100,
                'description' => $faker->sentence(12),
                'status' => $faker->randomElement(['active', 'active', 'inactive']),
            ]);
        }

        $this->command->info('Procedure Masters seeded successfully!');
    }
}
