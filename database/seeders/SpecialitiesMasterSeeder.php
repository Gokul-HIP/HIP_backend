<?php

namespace Database\Seeders;

use App\Models\SpecialitiesMaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SpecialitiesMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialities = [
            [
                'name' => 'Cardiology',
                'code' => 'CARD',
                'description' => 'Heart and cardiovascular system diseases',
                'status' => 'active',
            ],
            [
                'name' => 'Orthopedics',
                'code' => 'ORTH',
                'description' => 'Bones, joints, muscles, and ligaments',
                'status' => 'active',
            ],
            [
                'name' => 'Neurology',
                'code' => 'NEUR',
                'description' => 'Nervous system disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Pediatrics',
                'code' => 'PEDI',
                'description' => 'Medical care for infants, children, and adolescents',
                'status' => 'active',
            ],
            [
                'name' => 'General Surgery',
                'code' => 'GSUR',
                'description' => 'Surgical procedures for various conditions',
                'status' => 'active',
            ],
            [
                'name' => 'Dermatology',
                'code' => 'DERM',
                'description' => 'Skin, hair, and nail disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Ophthalmology',
                'code' => 'OPTH',
                'description' => 'Eye and vision care',
                'status' => 'active',
            ],
            [
                'name' => 'ENT (Ear, Nose, Throat)',
                'code' => 'ENT',
                'description' => 'Ear, nose, throat, head and neck disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Gynecology',
                'code' => 'GYNE',
                'description' => 'Women\'s reproductive health',
                'status' => 'active',
            ],
            [
                'name' => 'Urology',
                'code' => 'UROL',
                'description' => 'Urinary tract and male reproductive system',
                'status' => 'active',
            ],
            [
                'name' => 'Gastroenterology',
                'code' => 'GAST',
                'description' => 'Digestive system disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Pulmonology',
                'code' => 'PULM',
                'description' => 'Respiratory system and lung diseases',
                'status' => 'active',
            ],
            [
                'name' => 'Oncology',
                'code' => 'ONCO',
                'description' => 'Cancer diagnosis and treatment',
                'status' => 'active',
            ],
            [
                'name' => 'Psychiatry',
                'code' => 'PSYC',
                'description' => 'Mental health and behavioral disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Radiology',
                'code' => 'RADI',
                'description' => 'Medical imaging and diagnostic procedures',
                'status' => 'active',
            ],
            [
                'name' => 'Anesthesiology',
                'code' => 'ANES',
                'description' => 'Anesthesia and pain management',
                'status' => 'active',
            ],
            [
                'name' => 'Emergency Medicine',
                'code' => 'EMER',
                'description' => 'Acute care and emergency treatment',
                'status' => 'active',
            ],
            [
                'name' => 'Internal Medicine',
                'code' => 'INTM',
                'description' => 'Adult medicine and disease prevention',
                'status' => 'active',
            ],
            [
                'name' => 'Endocrinology',
                'code' => 'ENDO',
                'description' => 'Hormone and metabolic disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Nephrology',
                'code' => 'NEPH',
                'description' => 'Kidney diseases and disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Hematology',
                'code' => 'HEMA',
                'description' => 'Blood and blood-forming organs',
                'status' => 'active',
            ],
            [
                'name' => 'Rheumatology',
                'code' => 'RHEU',
                'description' => 'Autoimmune and joint diseases',
                'status' => 'active',
            ],
            [
                'name' => 'Plastic Surgery',
                'code' => 'PLAS',
                'description' => 'Reconstructive and cosmetic surgery',
                'status' => 'active',
            ],
            [
                'name' => 'Neurosurgery',
                'code' => 'NEUS',
                'description' => 'Surgical treatment of nervous system',
                'status' => 'active',
            ],
            [
                'name' => 'Cardiac Surgery',
                'code' => 'CARS',
                'description' => 'Surgical procedures on the heart',
                'status' => 'active',
            ],
            [
                'name' => 'Pathology',
                'code' => 'PATH',
                'description' => 'Disease diagnosis through laboratory analysis',
                'status' => 'active',
            ],
            [
                'name' => 'Immunology',
                'code' => 'IMMU',
                'description' => 'Immune system disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Geriatrics',
                'code' => 'GERI',
                'description' => 'Healthcare for elderly patients',
                'status' => 'active',
            ],
            [
                'name' => 'Sports Medicine',
                'code' => 'SPOR',
                'description' => 'Physical fitness and sports-related injuries',
                'status' => 'active',
            ],
            [
                'name' => 'Critical Care',
                'code' => 'CRIT',
                'description' => 'Intensive care for critically ill patients',
                'status' => 'active',
            ],
        ];

        $imagePaths = $this->specialityImagePathsFromDisk();
        if ($imagePaths !== []) {
            $this->command?->info('SpecialitiesMasterSeeder: random display_image from public disk folder speciality/');
        } else {
            $this->command?->warn('SpecialitiesMasterSeeder: no images in storage/app/public/speciality — display_image left unchanged on existing rows, omitted for new rows.');
        }

        foreach ($specialities as $speciality) {
            $payload = $speciality;
            if ($imagePaths !== []) {
                $payload['display_image'] = $imagePaths[array_rand($imagePaths)];
            }

            SpecialitiesMaster::updateOrCreate(
                ['code' => $speciality['code']],
                $payload
            );
        }

        $this->command->info('Specialities Master seeded successfully!');
    }

    /**
     * Paths relative to the public disk (e.g. speciality/foo.jpg), matching Filament directory('speciality').
     *
     * @return list<string>
     */
    private function specialityImagePathsFromDisk(): array
    {
        $disk = Storage::disk('public');

        if (! is_dir(storage_path('app/public/speciality'))) {
            return [];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $paths = [];

        foreach ($disk->files('speciality') as $path) {
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}

