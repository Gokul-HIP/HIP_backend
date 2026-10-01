<?php

namespace App\Modules\MedicineReminder\Services;

use App\Models\Prescription;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MedicineReminderScheduleService
{
    /** Meal/instruction tokens — never used as reminder clocks. */
    protected const MEAL_MODIFIERS = [
        'before food',
        'after food',
        'with food',
        'before meals',
        'after meals',
        'with meals',
    ];

    /** Explicit time-of-day labels; clocks come from config, not PHP literals. */
    protected const TIME_OF_DAY_LABELS = [
        'morning',
        'afternoon',
        'evening',
        'night',
        'bedtime',
    ];

    public function __construct(
        protected MedicineReminderInterface $repository,
    ) {}

    public function createFromPrescription(Prescription $prescription): Collection
    {
        $prescription->loadMissing(['hospital']);

        $medications = array_values($prescription->medications ?? []);
        if ($medications === []) {
            Log::info('Medicine reminder schedules skipped: no medications', [
                'prescription_id' => $prescription->id,
            ]);

            return collect();
        }

        $from = $prescription->created_at instanceof Carbon
            ? $prescription->created_at->copy()->timezone($this->timezone())
            : Carbon::now($this->timezone());

        $rows = [];

        foreach ($medications as $index => $medication) {
            if (! is_array($medication)) {
                Log::warning('Medicine reminder skipped: medication is not an object', [
                    'prescription_id' => $prescription->id,
                    'index' => $index,
                    'type' => get_debug_type($medication),
                ]);

                continue;
            }

            try {
                $itemId = $this->prescriptionItemId($medication, $index);
                $medicineId = filled($medication['medicine_id'] ?? null)
                    ? (int) $medication['medicine_id']
                    : null;

                $times = $this->calculateReminderTimes($medication, $from);

                foreach ($times as $scheduledAt) {
                    $rows[] = [
                        'workflow_id' => null,
                        'patient_id' => $prescription->patient_id,
                        'prescription_id' => $prescription->id,
                        'prescription_item_id' => $itemId,
                        'medicine_id' => $medicineId,
                        'scheduled_at' => $scheduledAt->timezone($this->timezone())->format('Y-m-d H:i:s'),
                        'status' => ScheduleStatus::Pending->value,
                        'retry_count' => 0,
                        'next_retry_at' => null,
                        'channels' => null,
                        'message_template' => null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Medicine reminder skipped: medication scheduling failed', [
                    'prescription_id' => $prescription->id,
                    'index' => $index,
                    'medicine_id' => $medication['medicine_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($rows === []) {
            Log::info('Medicine reminder schedules skipped: no future slots', [
                'prescription_id' => $prescription->id,
            ]);

            return collect();
        }

        return $this->repository->insertSchedules($rows);
    }

    /**
     * @param  array<string, mixed>  $medication
     * @return list<Carbon>
     */
    public function calculateReminderTimes(array $medication, Carbon $from): array
    {
        $days = $this->parseDurationDays($medication['duration'] ?? null);
        if ($days === null) {
            Log::warning('Medicine reminder skipped: duration missing or unreadable', [
                'duration' => $medication['duration'] ?? null,
                'medicine_id' => $medication['medicine_id'] ?? null,
            ]);

            return [];
        }

        $explicitLabels = $this->parseTimeOfDayLabels($medication['when_to_take'] ?? null);
        $frequencyKey = $this->normalizeFrequencyKey((string) ($medication['frequency'] ?? ''));
        $clocks = $this->clocksForMedication($medication);

        Log::info('Medicine reminder medication normalized', [
            'medicine_id' => $medication['medicine_id'] ?? null,
            'name' => $medication['name'] ?? $medication['medicine_name'] ?? null,
            'frequency' => $medication['frequency'] ?? null,
            'frequency_key' => $frequencyKey,
            'duration' => $medication['duration'] ?? null,
            'duration_days' => $days,
            'quantity' => $medication['quantity'] ?? null,
            'when_to_take' => $medication['when_to_take'] ?? null,
            'explicit_time_of_day' => $explicitLabels,
            'resolved_clocks' => $clocks,
            'frequency_times' => $frequencyKey !== ''
                ? (config('medicine_reminder.frequency_times.'.$frequencyKey) ?: [])
                : [],
        ]);

        if ($clocks === []) {
            Log::warning('Medicine reminder skipped: no configured clocks for frequency/when_to_take', [
                'frequency' => $medication['frequency'] ?? null,
                'when_to_take' => $medication['when_to_take'] ?? null,
                'medicine_id' => $medication['medicine_id'] ?? null,
            ]);

            return [];
        }

        $tz = $this->timezone();
        $from = $from->copy()->timezone($tz);
        $creationDay = $from->copy()->startOfDay();
        $remainingToday = $this->slotsOnDate($creationDay, $clocks, $tz, $from, true);
        $effectiveStart = $remainingToday === []
            ? $creationDay->copy()->addDay()
            : $creationDay->copy();

        Log::info('Medicine reminder first treatment day', [
            'medicine_id' => $medication['medicine_id'] ?? null,
            'created_at' => $from->toDateTimeString(),
            'remaining_slots_today' => array_map(fn (Carbon $at) => $at->toDateTimeString(), $remainingToday),
            'rolled_to_next_day' => $remainingToday === [],
            'effective_first_treatment_date' => $effectiveStart->toDateString(),
            'duration_days' => $days,
        ]);

        $scheduled = [];
        for ($day = 0; $day < $days; $day++) {
            $date = $effectiveStart->copy()->addDays($day);
            $onlyFuture = $date->toDateString() === $from->toDateString();
            foreach ($this->slotsOnDate($date, $clocks, $tz, $from, $onlyFuture) as $at) {
                $scheduled[] = $at;
            }
        }

        return $scheduled;
    }

    /**
     * @param  list<string>  $clocks
     * @return list<Carbon>
     */
    protected function slotsOnDate(Carbon $date, array $clocks, string $tz, Carbon $from, bool $onlyFuture): array
    {
        $slots = [];

        foreach ($clocks as $time) {
            $at = $this->clockOnDate($date, (string) $time, $tz);
            if ($at === null) {
                continue;
            }
            if ($onlyFuture && ! $at->greaterThan($from)) {
                continue;
            }
            $slots[] = $at;
        }

        usort($slots, fn (Carbon $a, Carbon $b) => $a <=> $b);

        return $slots;
    }

    protected function clockOnDate(Carbon $date, string $clock, string $tz): ?Carbon
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', trim($clock), $match)) {
            return null;
        }

        $hour = str_pad((string) min(23, max(0, (int) $match[1])), 2, '0', STR_PAD_LEFT);
        $minute = str_pad((string) min(59, max(0, (int) $match[2])), 2, '0', STR_PAD_LEFT);

        try {
            $at = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $date->copy()->timezone($tz)->format('Y-m-d').' '.$hour.':'.$minute.':00',
                $tz
            );
        } catch (\Throwable) {
            return null;
        }

        return $at ?: null;
    }

    /**
     * @param  array<string, mixed>  $medication
     * @return list<string>
     */
    public function clocksForMedication(array $medication): array
    {
        $fromWhen = $this->clocksFromWhenToTake($medication['when_to_take'] ?? null);
        if ($fromWhen !== []) {
            return $fromWhen;
        }

        $frequencyKey = $this->normalizeFrequencyKey((string) ($medication['frequency'] ?? ''));
        $times = config('medicine_reminder.frequency_times.'.$frequencyKey);

        if (! is_array($times) || $times === []) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $times)));
    }

    public function normalizeFrequencyKey(string $frequency): string
    {
        $normalized = strtolower(trim($frequency));

        return match (true) {
            str_contains($normalized, 'every 12') => 'every_12_hours',
            str_contains($normalized, 'every 8') => 'every_8_hours',
            str_contains($normalized, 'every 6') => 'every_6_hours',
            str_contains($normalized, 'four') || str_contains($normalized, 'qid') || (bool) preg_match('/\b4\b/', $normalized) => 'four_times_daily',
            str_contains($normalized, 'thrice') || str_contains($normalized, 'three') || str_contains($normalized, 'tid') || (bool) preg_match('/\b3\b/', $normalized) => 'thrice_daily',
            str_contains($normalized, 'twice') || (bool) preg_match('/\bbd\b/', $normalized) || (bool) preg_match('/\b2\b/', $normalized) => 'twice_daily',
            str_contains($normalized, 'once')
                || (bool) preg_match('/\bod\b/', $normalized)
                || (bool) preg_match('/\bqd\b/', $normalized) => 'once_daily',
            $normalized === '' => '',
            default => '',
        };
    }

    public function parseDurationDays(mixed $duration): ?int
    {
        if ($duration === null || $duration === '') {
            return null;
        }

        $duration = trim((string) $duration);
        if ($duration === '') {
            return null;
        }

        if (preg_match('/(\d+)\s*week/i', $duration, $m)) {
            return max(1, (int) $m[1] * 7);
        }

        if (preg_match('/(\d+)\s*month/i', $duration, $m)) {
            return max(1, (int) $m[1] * 30);
        }

        if (preg_match('/(\d+)/', $duration, $m)) {
            return max(1, min(90, (int) $m[1]));
        }

        return null;
    }

    /**
     * Explicit Morning/Afternoon/Evening/Night/Bedtime only.
     * Meal modifiers (After Food, With Meals, …) never become clocks.
     *
     * @return list<string>
     */
    public function clocksFromWhenToTake(mixed $whenToTake): array
    {
        $map = config('medicine_reminder.when_to_take_times', []);
        if (! is_array($map) || $map === []) {
            return [];
        }

        $explicitLabels = $this->parseTimeOfDayLabels($whenToTake);
        if ($explicitLabels === []) {
            return [];
        }

        $clocks = [];
        foreach ($explicitLabels as $label) {
            $clock = $map[$label] ?? null;
            if (! is_string($clock) || trim($clock) === '') {
                continue;
            }
            $clocks[] = $clock;
        }

        return array_values(array_unique($clocks));
    }

    /**
     * @return list<string>
     */
    public function parseTimeOfDayLabels(mixed $whenToTake): array
    {
        $haystack = implode(', ', $this->whenToTakeTokens($whenToTake));
        if ($haystack === '') {
            return [];
        }

        $pattern = '/\b('.implode('|', array_map(
            static fn (string $label) => preg_quote($label, '/'),
            self::TIME_OF_DAY_LABELS
        )).')\b/i';

        if (! preg_match_all($pattern, $haystack, $matches)) {
            return [];
        }

        $labels = [];
        foreach ($matches[1] as $label) {
            $labels[] = strtolower((string) $label);
        }

        return array_values(array_unique($labels));
    }

    /**
     * @return list<string>
     */
    protected function whenToTakeTokens(mixed $whenToTake): array
    {
        $chunks = [];

        if (is_array($whenToTake)) {
            foreach ($whenToTake as $part) {
                $chunks = array_merge($chunks, $this->whenToTakeTokens($part));
            }

            return $chunks;
        }

        $raw = strtolower(trim((string) $whenToTake));
        if ($raw === '' || $raw === 'array') {
            return [];
        }

        $decoded = json_decode((string) $whenToTake, true);
        if (is_array($decoded)) {
            return $this->whenToTakeTokens($decoded);
        }

        foreach (preg_split('/[,\/|;]+/', $raw) ?: [] as $token) {
            $token = trim($token);
            if ($token === '' || $this->isMealModifierOnly($token)) {
                continue;
            }
            $chunks[] = $token;
        }

        return $chunks;
    }

    protected function isMealModifierOnly(string $token): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $token) ?? $token));

        return in_array($normalized, self::MEAL_MODIFIERS, true);
    }

    protected function timezone(): string
    {
        $tz = trim((string) config('app.timezone'));

        return $tz !== '' ? $tz : 'UTC';
    }

    /**
     * @param  array<string, mixed>  $medication
     */
    protected function prescriptionItemId(array $medication, int $index): int
    {
        if (isset($medication['prescription_item_id']) && is_numeric($medication['prescription_item_id'])) {
            return (int) $medication['prescription_item_id'];
        }

        return $index;
    }
}
