<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Disease;

class DiseaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diseases = [
            'Diabetes',
            'Hypertension',
            'Asthma',
            'Heart Disease',
            'Cancer',
            'Tuberculosis',
            'COVID-19',
            'Dengue',
            'Malaria',
            'Typhoid',
            'Pneumonia',
            'Migraine',
            'Arthritis',
            'Kidney Stone',
            'Liver Disease',
            'Thyroid Disorder',
            'Epilepsy',
            'Stroke',
            'Depression',
            'Anxiety Disorder',
            'Obesity',
            'Cholesterol',
            'Gastric Ulcer',
            'GERD',
            'Anemia',
            'Skin Allergy',
            'Psoriasis',
            'Eczema',
            'Chickenpox',
            'Hepatitis',
            'HIV/AIDS',
            'Parkinson Disease',
            'Alzheimer Disease',
            'Osteoporosis',
            'PCOD',
            'Infertility',
            'Sinusitis',
            'Bronchitis',
            'Jaundice',
            'Appendicitis',
            'Food Poisoning',
            'Eye Infection',
            'Glaucoma',
            'Cataract',
            'Ear Infection',
            'UTI',
            'Back Pain',
            'Slip Disc',
            'Fracture',
            'Leukemia',
        ];

        foreach ($diseases as $disease) {
            Disease::updateOrCreate(
                ['name' => $disease],
                [
                    'is_active' => true,
                ]
            );
        }
    }
}