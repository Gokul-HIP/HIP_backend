<?php

namespace Database\Seeders;

use App\Models\HIPCard;
use App\Models\Hospital;
use Illuminate\Database\Seeder;

class HIPCardSeeder extends Seeder
{
    public function run(): void
    {
        $hospitals = Hospital::query()->orderBy('id')->pluck('id');

        if ($hospitals->isEmpty()) {
            $this->command?->warn('HIPCardSeeder skipped: no hospitals found.');
            return;
        }

        $cardsPerHospital = 20;

        foreach ($hospitals as $hospitalId) {
            for ($index = 1; $index <= $cardsPerHospital; $index++) {
                $hipCardId = sprintf('HIPC-%04d-%05d', (int) $hospitalId, $index);

                HIPCard::query()->firstOrCreate(
                    ['hip_card_id' => $hipCardId],
                    ['hip_card_id' => $hipCardId]
                );
            }
        }

        $this->command?->info('HIP cards seeded successfully.');
    }
}
