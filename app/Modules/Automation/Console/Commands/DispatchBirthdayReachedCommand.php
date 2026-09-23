<?php

namespace App\Modules\Automation\Console\Commands;

use App\Models\Persons;
use App\Modules\Automation\Events\BirthdayReached;
use App\Modules\Automation\Support\EventDispatchGuard;
use App\Modules\Automation\Support\PatientAutomationIdentity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchBirthdayReachedCommand extends Command
{
    protected $signature = 'hospital-automation:dispatch-birthdays
        {--date= : Calendar date (Y-m-d). Defaults to today.}';

    protected $description = 'Dispatch BirthdayReached for patients whose DOB month/day is today (on_birthday). daysBefore is trigger-node config (Step 3).';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $expires = $date->copy()->endOfDay()->addHour();
        $count = 0;
        $skipped = 0;

        Persons::query()
            ->with('hipUser')
            ->whereNotNull('dob')
            ->whereMonth('dob', $date->month)
            ->whereDay('dob', $date->day)
            ->orderBy('id')
            ->cursor()
            ->each(function (Persons $person) use ($date, $expires, &$count, &$skipped): void {
                $key = 'birthday:'.$person->id.':'.$date->year;
                if (! EventDispatchGuard::claim($key, $expires)) {
                    $skipped++;

                    return;
                }

                BirthdayReached::dispatch(
                    $person,
                    PatientAutomationIdentity::organizationId($person),
                    PatientAutomationIdentity::hospitalId($person),
                );
                $count++;
            });

        $this->info("Dispatched BirthdayReached {$count} time(s) for {$date->toDateString()} (skipped {$skipped} duplicate(s)).");

        return self::SUCCESS;
    }
}
