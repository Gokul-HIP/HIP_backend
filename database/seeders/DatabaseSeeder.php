<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application in one shot.
     *
     * Run: php artisan db:seed
     * Or:  php artisan migrate:fresh --seed
     *
     * Ensure storage folders have images where seeders expect them (doctor, diagnostic-lab-test,
     * pharmacy/products, procedures, speciality) or those seeders will warn / skip / throw as coded.
     * addDoctorSeeder needs at least one organization and hospital in the database.
     */
    public function run(): void
    {
        $this->command?->info('Running DatabaseSeeder…');

        $this->call([
            BangaloreLocationSeeder::class,
            MasterQualificationSeeder::class,
            MasterLabtestCategoriesSeeder::class,
            MasterWellnessCategoriesSeeder::class,
            SpecialitiesMasterSeeder::class,
            ProcedureMasterSeeder::class,
            LabTestMasterSeeder::class,
            MedicineMasterSeeder::class,
            addDoctorSeeder::class,
            HIPCardSeeder::class,
            SettingsSeeder::class,
            DiseaseSeeder::class,
        ]);

        // Demo booking rows: many still use legacy integer IDs — enable only after aligning with your schema (UUIDs).
        // $this->call([
        //     DoctorBookingsSeeder::class,
        //     DiagnosticTestBookingsSeeder::class,
        //     WellnessBookingsSeeder::class,
        //     StemCellBookingsSeeder::class,
        //     CaregiverBookingsSeeder::class,
        // ]);

        $this->command?->info('DatabaseSeeder finished.');
    }
}
