<?php

namespace App\Modules\Automation\Console\Commands;

use App\Models\Persons;
use App\Modules\Automation\Events\AnniversaryReached;
use App\Modules\Automation\Support\EventDispatchGuard;
use App\Modules\Automation\Support\PatientAutomationIdentity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchAnniversaryReachedCommand extends Command
{
    protected $signature = 'hospital-automation:dispatch-anniversaries
        {--date= : Calendar date (Y-m-d). Defaults to today.}
        {--type=womens_day : Anniversary type placed on the event payload.}';

    protected $description = 'Dispatch AnniversaryReached for calendar occasions (Women\'s Day = 8 March). Gender filtering stays on the workflow condition node.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $type = (string) $this->option('type');

        if ($type === 'womens_day' && ((int) $date->month !== 3 || (int) $date->day !== 8)) {
            $this->info("Skipping: {$date->toDateString()} is not Women's Day (8 March).");

            return self::SUCCESS;
        }

        $expires = $date->copy()->endOfDay()->addHour();
        $count = 0;
        $skipped = 0;

        Persons::query()
            ->with('hipUser')
            ->orderBy('id')
            ->cursor()
            ->each(function (Persons $person) use ($type, $date, $expires, &$count, &$skipped): void {
                $key = 'anniversary:'.$type.':'.$person->id.':'.$date->year;
                if (! EventDispatchGuard::claim($key, $expires)) {
                    $skipped++;

                    return;
                }

                AnniversaryReached::dispatch(
                    $person,
                    $type,
                    PatientAutomationIdentity::organizationId($person),
                    PatientAutomationIdentity::hospitalId($person),
                );
                $count++;
            });

        $this->info("Dispatched AnniversaryReached ({$type}) {$count} time(s) for {$date->toDateString()} (skipped {$skipped} duplicate(s)).");

        return self::SUCCESS;
    }
}
