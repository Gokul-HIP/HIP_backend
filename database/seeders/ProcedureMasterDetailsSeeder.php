<?php

namespace Database\Seeders;

use App\Models\ProcedureMaster;
use Illuminate\Database\Seeder;

class ProcedureMasterDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $masters = ProcedureMaster::query()->get();

        if ($masters->isEmpty()) {
            $this->command->warn('No procedure masters found. Run ProcedureMasterSeeder first.');

            return;
        }

        $updated = 0;

        foreach ($masters as $master) {
            $recoveryFrom = fake()->numberBetween(1, 5);
            $recoveryTo = fake()->numberBetween($recoveryFrom + 1, $recoveryFrom + 14);
            $recoveryUnit = fake()->randomElement(['days', 'weeks']);
            $successRate = fake()->numberBetween(85, 99);
            $hospitalizationDays = fake()->numberBetween(0, 7);

            $master->update([
                'recovery_time' => "{$recoveryFrom} to {$recoveryTo} {$recoveryUnit}",
                'success_rate' => (string) $successRate,
                'hospitalization_days' => (string) $hospitalizationDays,
                'common_questions' => $this->commonQuestionsFor(
                    $master->name,
                    $recoveryFrom,
                    $recoveryTo,
                    $recoveryUnit,
                    $successRate,
                    $hospitalizationDays,
                    (int) $master->duration
                ),
            ]);

            $updated++;
        }

        $this->command->info("Updated {$updated} procedure masters with recovery, success rate, hospitalization, and FAQs.");
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    protected function commonQuestionsFor(
        string $procedureName,
        int $recoveryFrom,
        int $recoveryTo,
        string $recoveryUnit,
        int $successRate,
        int $hospitalizationDays,
        int $durationMinutes
    ): array {
        $name = trim($procedureName);
        $recoveryLabel = "{$recoveryFrom}–{$recoveryTo} {$recoveryUnit}";
        $hospitalizationAnswer = $hospitalizationDays === 0
            ? 'Hospitalization is usually not required. Most patients can go home the same day after observation.'
            : "Hospitalization of approximately {$hospitalizationDays} day(s) may be required depending on clinical assessment and recovery progress.";

        return [
            [
                'question' => "What is {$name} and why is it performed?",
                'answer' => "{$name} is a medical procedure used for diagnosis, treatment, or monitoring as recommended by your specialist. Your doctor will explain the specific reason based on your symptoms and medical history.",
            ],
            [
                'question' => 'How long does the procedure take?',
                'answer' => "The procedure typically takes around {$durationMinutes} minutes. Actual time may vary based on patient condition and clinical findings.",
            ],
            [
                'question' => 'What is the expected recovery time?',
                'answer' => "Most patients recover within {$recoveryLabel}. Follow your doctor's advice on rest, medication, and follow-up visits for the best outcome.",
            ],
            [
                'question' => 'What is the success rate for this procedure?',
                'answer' => "The reported success rate is approximately {$successRate}%. Individual results depend on overall health, adherence to post-procedure care, and underlying conditions.",
            ],
            [
                'question' => 'Will I need to stay in the hospital?',
                'answer' => $hospitalizationAnswer,
            ],
            [
                'question' => 'How should I prepare before the procedure?',
                'answer' => 'Follow fasting, medication, and hygiene instructions given by your care team. Bring prior reports, inform staff of allergies, and arrange for someone to accompany you if sedation is planned.',
            ],
        ];
    }
}
