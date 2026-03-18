<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MasterWellnessCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'parent_category' => 'General Wellness',
                'submenus' => [
                    'Fitness tracking',
                    'Activity monitoring (steps, calories, workouts)',
                    'Sleep tracking',
                    'BMI & body measurements',
                ],
            ],
            [
                'parent_category' => 'Preventive Care',
                'submenus' => [
                    'Routine health checkups',
                    'Early disease screening',
                    'Annual health packages',
                    'Risk assessment',
                ],
            ],
            [
                'parent_category' => 'Fitness & Exercise',
                'submenus' => [
                    'Gym workouts',
                    'Home workouts',
                    'Personal training',
                    'Cardio programs',
                    'Strength training',
                ],
            ],
            [
                'parent_category' => 'Nutrition & Diet',
                'submenus' => [
                    'Diet planning',
                    'Weight loss diet',
                    'Weight gain diet',
                    'Therapeutic diets',
                    'Nutrition counseling',
                ],
            ],
            [
                'parent_category' => 'Mental Health',
                'submenus' => [
                    'Anxiety management',
                    'Depression support',
                    'Counseling & therapy',
                    'Emotional wellbeing',
                ],
            ],
            [
                'parent_category' => 'Stress Management',
                'submenus' => [
                    'Relaxation techniques',
                    'Breathing exercises',
                    'Mindfulness programs',
                ],
            ],
            [
                'parent_category' => 'Yoga & Meditation',
                'submenus' => [
                    'Guided meditation',
                    'Yoga sessions',
                    'Pranayama',
                    'Mindfulness yoga',
                ],
            ],
            [
                'parent_category' => 'Sleep Health',
                'submenus' => [
                    'Sleep analysis',
                    'Insomnia management',
                    'Sleep improvement programs',
                ],
            ],
            [
                'parent_category' => 'Weight Management',
                'submenus' => [
                    'Obesity management',
                    'Fat loss programs',
                    'Lifestyle coaching',
                ],
            ],
            [
                'parent_category' => "Women’s Health",
                'submenus' => [
                    'Menstrual health',
                    'Pregnancy care',
                    'PCOS management',
                    'Menopause support',
                ],
            ],
            [
                'parent_category' => "Men’s Health",
                'submenus' => [
                    'Hormonal health',
                    'Prostate health',
                    'Sexual wellness',
                ],
            ],
            [
                'parent_category' => 'Child & Adolescent Health',
                'submenus' => [
                    'Growth monitoring',
                    'Nutrition for kids',
                    'Vaccination tracking',
                ],
            ],
            [
                'parent_category' => 'Senior Citizen Health',
                'submenus' => [
                    'Geriatric care',
                    'Mobility support',
                    'Chronic disease monitoring',
                ],
            ],
            [
                'parent_category' => 'Lifestyle Management',
                'submenus' => [
                    'Habit tracking',
                    'Smoking cessation',
                    'Alcohol reduction programs',
                ],
            ],
            [
                'parent_category' => 'Chronic Disease Management',
                'submenus' => [
                    'Diabetes care',
                    'Hypertension management',
                    'Cardiac care',
                ],
            ],
            [
                'parent_category' => 'Rehabilitation & Physiotherapy',
                'submenus' => [
                    'Injury recovery',
                    'Post-surgery rehab',
                    'Pain management',
                ],
            ],
            [
                'parent_category' => 'Alternative Medicine',
                'submenus' => [
                    'Ayurveda',
                    'Homeopathy',
                    'Naturopathy',
                ],
            ],
            [
                'parent_category' => 'Holistic Health',
                'submenus' => [
                    'Mind-body healing',
                    'Wellness therapies',
                    'Detox programs',
                ],
            ],
            [
                'parent_category' => 'Immunization & Vaccination',
                'submenus' => [
                    'Adult vaccination',
                    'Child vaccination',
                    'Travel vaccines',
                ],
            ],
            [
                'parent_category' => 'Health Screening Packages',
                'submenus' => [
                    'Basic health checkup',
                    'Advanced health packages',
                    'Full body checkup',
                ],
            ],
            [
                'parent_category' => 'Corporate Wellness',
                'submenus' => [
                    'Employee health programs',
                    'Workplace fitness',
                    'Stress workshops',
                ],
            ],
            [
                'parent_category' => 'Occupational Health',
                'submenus' => [
                    'Workplace health screening',
                    'Injury prevention',
                    'Ergonomic assessment',
                ],
            ],
            [
                'parent_category' => 'Digital Health & Telemedicine',
                'submenus' => [
                    'Teleconsultation',
                    'Remote monitoring',
                    'Health apps',
                ],
            ],
        ];

        foreach ($categories as $cat) {
            DB::table('master_wellness_categories')->updateOrInsert(
                ['parent_category' => $cat['parent_category']],
                [
                    'submenus'    => json_encode($cat['submenus'], JSON_UNESCAPED_UNICODE),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]
            );
        }
    }
}

