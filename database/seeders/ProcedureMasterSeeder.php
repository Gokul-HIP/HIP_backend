<?php

namespace Database\Seeders;

use App\Models\ProcedureMaster;
use App\Models\SpecialitiesMaster;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

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

        $procedureImages = $this->procedureImageFilenames();
        if ($procedureImages === []) {
            $this->command->warn('No images in storage/app/public/procedures — procedure_masters.image will be null. Add .jpg/.png/etc. there to assign random images.');
        } else {
            $this->command->info('Using random images from storage/app/public/procedures');
        }

        for ($i = 0; $i < 100; $i++) {

            $name =
                $faker->randomElement($modifiers) . ' ' .
                $faker->randomElement($procedureTypes);

            ProcedureMaster::create([
                'name' => $name . ' ' . ($i + 1),
                'image' => $procedureImages !== [] ? $procedureImages[array_rand($procedureImages)] : null,
                'duration' => $faker->randomElement([15, 30, 45, 60, 90, 120]),
                'speciality_master_id' => $faker->randomElement($specialityMasterIds),
                'cost' => round($faker->numberBetween(500, 50000) / 100) * 100,
                'description' => $faker->sentence(12),
                'status' => $faker->randomElement(['active', 'active', 'inactive']),
            ]);
        }

        $this->command->info('Procedure Masters seeded successfully!');
    }

    /**
     * Basenames under public disk procedures/ (same as ProcedureService uploads, URL storage/procedures/...).
     *
     * @return list<string>
     */
    private function procedureImageFilenames(): array
    {
        $dir = storage_path('app/public/procedures');

        if (! is_dir($dir)) {
            return [];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $names = [];

        foreach (File::files($dir) as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $allowed, true)) {
                $names[] = $file->getFilename();
            }
        }

        return $names;
    }
}
