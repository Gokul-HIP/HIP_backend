<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\MasterWellnessCategories;

class MasterWellnessCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'parent_category' => 'General Wellness',
                'submenus' => [
                    ['value' => 'Fitness tracking'],
                    ['value' => 'Activity monitoring (steps, calories, workouts)'],
                    ['value' => 'Sleep tracking'],
                    ['value' => 'BMI & body measurements'],
                ],
            ],
            [
                'parent_category' => 'Preventive Care',
                'submenus' => [
                    ['value' => 'Routine health checkups'],
                    ['value' => 'Early disease screening'],
                    ['value' => 'Annual health packages'],
                    ['value' => 'Risk assessment'],
                ],
            ],
            [
                'parent_category' => 'Fitness & Exercise',
                'submenus' => [
                    ['value' => 'Gym workouts'],
                    ['value' => 'Home workouts'],
                    ['value' => 'Personal training'],
                    ['value' => 'Cardio programs'],
                    ['value' => 'Strength training'],
                ],
            ],
            [
                'parent_category' => 'Nutrition & Diet',
                'submenus' => [
                    ['value' => 'Diet planning'],
                    ['value' => 'Weight loss diet'],
                    ['value' => 'Weight gain diet'],
                    ['value' => 'Therapeutic diets'],
                    ['value' => 'Nutrition counseling'],
                ],
            ],
            [
                'parent_category' => 'Mental Health',
                'submenus' => [
                    ['value' => 'Anxiety management'],
                    ['value' => 'Depression support'],
                    ['value' => 'Counseling & therapy'],
                    ['value' => 'Emotional wellbeing'],
                ],
            ],
            [
                'parent_category' => 'Stress Management',
                'submenus' => [
                    ['value' => 'Relaxation techniques'],
                    ['value' => 'Breathing exercises'],
                    ['value' => 'Mindfulness programs'],
                ],
            ],
            [
                'parent_category' => 'Yoga & Meditation',
                'submenus' => [
                    ['value' => 'Guided meditation'],
                    ['value' => 'Yoga sessions'],
                    ['value' => 'Pranayama'],
                    ['value' => 'Mindfulness yoga'],
                ],
            ],
            [
                'parent_category' => 'Sleep Health',
                'submenus' => [
                    ['value' => 'Sleep analysis'],
                    ['value' => 'Insomnia management'],
                    ['value' => 'Sleep improvement programs'],
                ],
            ],
            [
                'parent_category' => 'Weight Management',
                'submenus' => [
                    ['value' => 'Obesity management'],
                    ['value' => 'Fat loss programs'],
                    ['value' => 'Lifestyle coaching'],
                ],
            ],
            [
                'parent_category' => "Women’s Health",
                'submenus' => [
                    ['value' => 'Menstrual health'],
                    ['value' => 'Pregnancy care'],
                    ['value' => 'PCOS management'],
                    ['value' => 'Menopause support'],
                ],
            ],
            [
                'parent_category' => "Men’s Health",
                'submenus' => [
                    ['value' => 'Hormonal health'],
                    ['value' => 'Prostate health'],
                    ['value' => 'Sexual wellness'],
                ],
            ],
            [
                'parent_category' => 'Child & Adolescent Health',
                'submenus' => [
                    ['value' => 'Growth monitoring'],
                    ['value' => 'Nutrition for kids'],
                    ['value' => 'Vaccination tracking'],
                ],
            ],
            [
                'parent_category' => 'Senior Citizen Health',
                'submenus' => [
                    ['value' => 'Geriatric care'],
                    ['value' => 'Mobility support'],
                    ['value' => 'Chronic disease monitoring'],
                ],
            ],
            [
                'parent_category' => 'Lifestyle Management',
                'submenus' => [
                    ['value' => 'Habit tracking'],
                    ['value' => 'Smoking cessation'],
                    ['value' => 'Alcohol reduction programs'],
                ],
            ],
            [
                'parent_category' => 'Chronic Disease Management',
                'submenus' => [
                    ['value' => 'Diabetes care'],
                    ['value' => 'Hypertension management'],
                    ['value' => 'Cardiac care'],
                ],
            ],
            [
                'parent_category' => 'Rehabilitation & Physiotherapy',
                'submenus' => [
                    ['value' => 'Injury recovery'],
                    ['value' => 'Post-surgery rehab'],
                    ['value' => 'Pain management'],
                ],
            ],
            [
                'parent_category' => 'Alternative Medicine',
                'submenus' => [
                    ['value' => 'Ayurveda'],
                    ['value' => 'Homeopathy'],
                    ['value' => 'Naturopathy'],
                ],
            ],
            [
                'parent_category' => 'Holistic Health',
                'submenus' => [
                    ['value' => 'Mind-body healing'],
                    ['value' => 'Wellness therapies'],
                    ['value' => 'Detox programs'],
                ],
            ],
            [
                'parent_category' => 'Immunization & Vaccination',
                'submenus' => [
                    ['value' => 'Adult vaccination'],
                    ['value' => 'Child vaccination'],
                    ['value' => 'Travel vaccines'],
                ],
            ],
            [
                'parent_category' => 'Health Screening Packages',
                'submenus' => [
                    ['value' => 'Basic health checkup'],
                    ['value' => 'Advanced health packages'],
                    ['value' => 'Full body checkup'],
                ],
            ],
            [
                'parent_category' => 'Corporate Wellness',
                'submenus' => [
                    ['value' => 'Employee health programs'],
                    ['value' => 'Workplace fitness'],
                    ['value' => 'Stress workshops'],
                ],
            ],
            [
                'parent_category' => 'Occupational Health',
                'submenus' => [
                    ['value' => 'Workplace health screening'],
                    ['value' => 'Injury prevention'],
                    ['value' => 'Ergonomic assessment'],
                ],
            ],
            [
                'parent_category' => 'Digital Health & Telemedicine',
                'submenus' => [
                    ['value' => 'Teleconsultation'],
                    ['value' => 'Remote monitoring'],
                    ['value' => 'Health apps'],
                ],
            ],
        ];

        foreach ($categories as $cat) {
            MasterWellnessCategories::updateOrCreate(
                ['parent_category' => $cat['parent_category']],
                [
                    // Store as an array so Eloquent casts it to JSON properly.
                    // Avoid json_encode() here to prevent double-encoding (JSON string instead of JSON array).
                    'submenus' => $cat['submenus'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}

