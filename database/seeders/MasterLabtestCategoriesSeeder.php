<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MasterLabtestCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            'Hematology',
            'Clinical Biochemistry',
            'Microbiology',
            'Immunology',
            'Serology',
            'Pathology',
            'Histopathology',
            'Cytology',
            'Molecular Diagnostics',
            'Genetic Testing',
            'Endocrinology',
            'Toxicology',
            'Urinalysis',
            'Stool Analysis',
            'Parasitology',
            'Virology',
            'Bacteriology',
            'Mycology',
            'Blood Bank / Transfusion Medicine',
            'Radiology',
            'Imaging',
            'Nuclear Medicine',
            'Cardiology Diagnostics',
            'Pulmonology Diagnostics',
            'Allergy Testing',
            'Fertility / Reproductive Testing',
            'Oncology Testing',
            'Preventive Health Checkups',
            'Point of Care Testing (POCT)',
        ];

        $rows = [];
        foreach ($categories as $name) {
            $rows[] = [
                'category_name' => $name,
                'created_by'    => null,
                'updated_by'    => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        DB::table('master_labtest_categories')->insert($rows);
    }
}

