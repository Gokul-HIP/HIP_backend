<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Services\MedicineReminderScheduleService;
use Carbon\Carbon;
use Tests\TestCase;

class MedicineReminderScheduleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'medicine_reminder.when_to_take_times' => [
                'morning' => '07:15',
                'afternoon' => '13:45',
                'evening' => '19:30',
                'night' => '21:00',
                'bedtime' => '21:00',
            ],
            'medicine_reminder.frequency_times' => [
                'once_daily' => ['08:05'],
                'twice_daily' => ['08:05', '20:05'],
                'thrice_daily' => ['08:05', '14:05', '20:05'],
                'four_times_daily' => ['08:00', '12:00', '16:00', '20:00'],
                'every_6_hours' => ['06:00', '12:00', '18:00', '00:00'],
                'every_8_hours' => ['08:00', '16:00', '00:00'],
                'every_12_hours' => ['09:10', '21:10'],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-30 06:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function service(): MedicineReminderScheduleService
    {
        return $this->app->make(MedicineReminderScheduleService::class);
    }

    public function test_once_daily_creates_one_slot_per_duration_day(): void
    {
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'duration' => '3 Days',
        ], Carbon::now());

        $this->assertCount(3, $times);
        $this->assertSame(['08:05', '08:05', '08:05'], array_map(fn ($t) => $t->format('H:i'), $times));
    }

    public function test_twice_daily_creates_two_slots_per_day(): void
    {
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Twice Daily',
            'duration' => '2 Days',
        ], Carbon::now());

        $this->assertCount(4, $times);
    }

    public function test_thrice_daily_creates_three_slots_per_day(): void
    {
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Thrice Daily',
            'duration' => '1 Day',
        ], Carbon::now());

        $this->assertCount(3, $times);
        $this->assertSame(['08:05', '14:05', '20:05'], array_map(fn ($t) => $t->format('H:i'), $times));
    }

    public function test_morning_afternoon_evening_use_configured_clocks_not_hardcoded_0900(): void
    {
        $service = $this->service();
        $from = Carbon::now();

        $morning = $service->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'when_to_take' => 'Morning',
            'duration' => '1 Day',
        ], $from);
        $afternoon = $service->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'when_to_take' => 'Afternoon',
            'duration' => '1 Day',
        ], $from);
        $evening = $service->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'when_to_take' => 'Evening',
            'duration' => '1 Day',
        ], $from);

        $this->assertSame(['07:15'], array_map(fn ($t) => $t->format('H:i'), $morning));
        $this->assertSame(['13:45'], array_map(fn ($t) => $t->format('H:i'), $afternoon));
        $this->assertSame(['19:30'], array_map(fn ($t) => $t->format('H:i'), $evening));
        $this->assertNotSame('09:00', $morning[0]->format('H:i'));
    }

    public function test_duration_controls_number_of_days(): void
    {
        $one = $this->service()->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'duration' => '1 Day',
        ], Carbon::now());
        $five = $this->service()->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'duration' => '5 Days',
        ], Carbon::now());

        $this->assertCount(1, $one);
        $this->assertCount(5, $five);
    }

    public function test_unknown_frequency_does_not_invent_clocks(): void
    {
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'as directed',
            'duration' => '1 Day',
        ], Carbon::now());

        $this->assertSame([], $times);
    }

    public function test_every_twelve_hours_uses_frequency_config(): void
    {
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Every 12 Hours',
            'duration' => '1 Day',
        ], Carbon::now());

        $this->assertSame(['09:10', '21:10'], array_map(fn ($t) => $t->format('H:i'), $times));
    }

    public function test_after_food_afternoon_uses_afternoon_not_once_daily(): void
    {
        $clocks = $this->service()->clocksForMedication([
            'frequency' => 'Once Daily',
            'when_to_take' => 'After Food, Afternoon',
        ]);

        $this->assertSame(
            [config('medicine_reminder.when_to_take_times.afternoon')],
            $clocks
        );
    }

    public function test_after_food_afternoon_array_uses_afternoon(): void
    {
        $clocks = $this->service()->clocksForMedication([
            'frequency' => 'Once Daily',
            'when_to_take' => ['After Food', 'Afternoon'],
        ]);

        $this->assertSame(
            [config('medicine_reminder.when_to_take_times.afternoon')],
            $clocks
        );
    }

    public function test_after_food_only_uses_once_daily_frequency(): void
    {
        $clocks = $this->service()->clocksForMedication([
            'frequency' => 'Once Daily',
            'when_to_take' => 'After Food',
        ]);

        $this->assertSame(config('medicine_reminder.frequency_times.once_daily'), $clocks);
    }

    public function test_morning_night_uses_both_configured_times(): void
    {
        $clocks = $this->service()->clocksForMedication([
            'frequency' => 'Twice Daily',
            'when_to_take' => 'Morning, Night',
        ]);

        $this->assertSame([
            config('medicine_reminder.when_to_take_times.morning'),
            config('medicine_reminder.when_to_take_times.night'),
        ], $clocks);
    }

    public function test_with_food_evening_uses_evening(): void
    {
        $clocks = $this->service()->clocksForMedication([
            'frequency' => 'Once Daily',
            'when_to_take' => 'With Food, Evening',
        ]);

        $this->assertSame(
            [config('medicine_reminder.when_to_take_times.evening')],
            $clocks
        );
    }

    public function test_before_food_uses_frequency_fallback(): void
    {
        $clocks = $this->service()->clocksForMedication([
            'frequency' => 'Once Daily',
            'when_to_take' => 'Before Food',
        ]);

        $this->assertSame(config('medicine_reminder.frequency_times.once_daily'), $clocks);
    }

    public function test_created_before_morning_schedules_morning_today(): void
    {
        $from = Carbon::parse('2026-09-30 06:00:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Twice Daily',
            'when_to_take' => 'After Food, Morning, Afternoon',
            'duration' => '1 Day',
        ], $from);

        $this->assertSame([
            '2026-09-30 '.config('medicine_reminder.when_to_take_times.morning'),
            '2026-09-30 '.config('medicine_reminder.when_to_take_times.afternoon'),
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $times));
    }

    public function test_created_between_morning_and_afternoon_schedules_afternoon_today(): void
    {
        $from = Carbon::parse('2026-09-30 10:00:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Twice Daily',
            'when_to_take' => 'After Food, Morning, Afternoon',
            'duration' => '1 Day',
        ], $from);

        $this->assertSame([
            '2026-09-30 '.config('medicine_reminder.when_to_take_times.afternoon'),
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $times));
    }

    public function test_created_after_afternoon_rolls_morning_and_afternoon_to_tomorrow(): void
    {
        $from = Carbon::parse('2026-09-30 18:09:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Twice Daily',
            'when_to_take' => 'After Food, Morning, Afternoon',
            'duration' => '1 Day',
        ], $from);

        $this->assertSame([
            '2026-10-01 '.config('medicine_reminder.when_to_take_times.morning'),
            '2026-10-01 '.config('medicine_reminder.when_to_take_times.afternoon'),
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $times));
    }

    public function test_twice_daily_without_time_of_day_uses_remaining_frequency_slot_today(): void
    {
        $from = Carbon::parse('2026-09-30 18:09:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Twice Daily',
            'when_to_take' => 'After Food',
            'duration' => '1 Day',
        ], $from);

        $slots = config('medicine_reminder.frequency_times.twice_daily');
        $this->assertSame([
            '2026-09-30 '.$slots[1],
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $times));
    }

    public function test_twice_daily_all_frequency_slots_passed_starts_next_day(): void
    {
        $from = Carbon::parse('2026-09-30 21:30:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Twice Daily',
            'when_to_take' => 'After Food',
            'duration' => '1 Day',
        ], $from);

        $slots = config('medicine_reminder.frequency_times.twice_daily');
        $this->assertSame([
            '2026-10-01 '.$slots[0],
            '2026-10-01 '.$slots[1],
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $times));
        $this->assertSame($slots[0], $times[0]->format('H:i'));
    }

    public function test_duration_one_day_uses_rolled_over_first_treatment_day(): void
    {
        $from = Carbon::parse('2026-09-30 18:09:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'when_to_take' => 'Morning, Afternoon',
            'duration' => '1 Day',
        ], $from);

        $this->assertCount(2, $times);
        $this->assertSame('2026-10-01', $times[0]->format('Y-m-d'));
        $this->assertSame('2026-10-01', $times[1]->format('Y-m-d'));
        $this->assertSame([
            config('medicine_reminder.when_to_take_times.morning'),
            config('medicine_reminder.when_to_take_times.afternoon'),
        ], array_map(fn ($t) => $t->format('H:i'), $times));
    }

    public function test_after_food_afternoon_schedules_afternoon_not_frequency(): void
    {
        $from = Carbon::parse('2026-09-30 10:00:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'when_to_take' => 'After Food, Afternoon',
            'duration' => '1 Day',
        ], $from);

        $this->assertSame([
            '2026-09-30 '.config('medicine_reminder.when_to_take_times.afternoon'),
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $times));
    }

    public function test_after_food_only_schedules_frequency_fallback(): void
    {
        $from = Carbon::parse('2026-09-30 06:00:00', config('app.timezone'));
        $times = $this->service()->calculateReminderTimes([
            'frequency' => 'Once Daily',
            'when_to_take' => 'After Food',
            'duration' => '1 Day',
        ], $from);

        $this->assertSame([
            '2026-09-30 '.config('medicine_reminder.frequency_times.once_daily')[0],
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $times));
    }

    public function test_amlodipine_aspirin_calcium_prescription_at_1844_yields_six_slots(): void
    {
        config([
            'medicine_reminder.when_to_take_times' => [
                'morning' => '09:00',
                'afternoon' => '14:00',
                'evening' => '18:00',
                'night' => '21:00',
                'bedtime' => '21:00',
            ],
            'medicine_reminder.frequency_times' => [
                'once_daily' => ['09:00'],
                'twice_daily' => ['09:00', '21:00'],
            ],
        ]);

        $from = Carbon::parse('2026-09-30 18:44:00', config('app.timezone'));
        $service = $this->service();

        $amlodipine = [
            'medicine_id' => 39,
            'name' => 'Amlodipine 250mg',
            'frequency' => 'Twice Daily',
            'duration' => '1 Day',
            'when_to_take' => 'After Food, Afternoon, Morning',
            'quantity' => 2,
        ];
        $aspirin = [
            'medicine_id' => 22,
            'name' => 'Aspirin 50mg',
            'frequency' => 'Once Daily',
            'duration' => '1 Day',
            'when_to_take' => 'After Food',
            'quantity' => 1,
        ];
        $calcium = [
            'medicine_id' => 10,
            'name' => 'Calcium Carbonate 10mg',
            'frequency' => 'Once Daily',
            'duration' => '3 Days',
            'when_to_take' => 'After Food, Night',
            'quantity' => 3,
        ];

        $this->assertSame(['afternoon', 'morning'], $service->parseTimeOfDayLabels($amlodipine['when_to_take']));
        $this->assertSame([], $service->parseTimeOfDayLabels($aspirin['when_to_take']));
        $this->assertSame(['night'], $service->parseTimeOfDayLabels($calcium['when_to_take']));

        $amlodipineTimes = $service->calculateReminderTimes($amlodipine, $from);
        $aspirinTimes = $service->calculateReminderTimes($aspirin, $from);
        $calciumTimes = $service->calculateReminderTimes($calcium, $from);

        $this->assertSame([
            '2026-10-01 09:00',
            '2026-10-01 14:00',
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $amlodipineTimes));
        $this->assertSame([
            '2026-10-01 09:00',
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $aspirinTimes));
        $this->assertSame([
            '2026-09-30 21:00',
            '2026-10-01 21:00',
            '2026-10-02 21:00',
        ], array_map(fn ($t) => $t->format('Y-m-d H:i'), $calciumTimes));
        $this->assertCount(6, array_merge($amlodipineTimes, $aspirinTimes, $calciumTimes));
    }

    public function test_when_to_take_array_is_not_cast_away_as_array_string(): void
    {
        $this->assertSame(
            ['afternoon', 'morning'],
            $this->service()->parseTimeOfDayLabels(['After Food', 'Afternoon', 'Morning'])
        );
    }
}
