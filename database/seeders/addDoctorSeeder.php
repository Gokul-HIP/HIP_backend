<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class addDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctorImages = $this->doctorImagesFromPublicStorage();

        if ($doctorImages === []) {
            throw new \RuntimeException(
                'No images in storage. Add .jpg/.jpeg/.png/.gif/.webp files to storage/app/public/doctor (same as public/storage/doctor after storage:link).'
            );
        }

        $organization = Organization::query()->orderBy('id')->first();
        if ($organization === null) {
            throw new \RuntimeException(
                'No organizations found. Create or seed at least one organization before running addDoctorSeeder.'
            );
        }

        $hospitalIds = Hospital::query()
            ->where('organization_id', $organization->id)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($hospitalIds === []) {
            throw new \RuntimeException(
                "Organization \"{$organization->name}\" (id {$organization->id}) has no hospitals. Add at least one hospital for this organization before seeding doctors."
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $count = fake()->numberBetween(1, min(3, count($hospitalIds)));
            $picked = fake()->randomElements($hospitalIds, $count);
            sort($picked);

            Doctor::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'mobile_number' => fake()->numerify('##########'),
                'qualifications' => [1, 2],
                'speciality' => [1, 2],
                'hospital_ids' => array_values($picked),
                'status' => 'active',
                'doctor_image' => $doctorImages[array_rand($doctorImages)],
                'gender' => 'male',
                'organization_id' => $organization->id,
            ]);
        }
    }

    /**
     * Filenames already under public storage doctor (storage/app/public/doctor).
     *
     * @return list<string>
     */
    private function doctorImagesFromPublicStorage(): array
    {
        $dir = storage_path('app/public/doctor');

        if (! is_dir($dir)) {
            return [];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $names = [];

        foreach (File::files($dir) as $file) {
            if (! $file->isFile()) {
                continue;
            }
            if (in_array(strtolower($file->getExtension()), $allowed, true)) {
                $names[] = $file->getFilename();
            }
        }

        return $names;
    }
}
