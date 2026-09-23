<?php

namespace App\Modules\Automation\Console\Commands;

use App\Models\DoctorBooking;
use App\Models\Persons;
use App\Modules\Automation\Events\ScheduledEvent;
use App\Modules\Automation\Support\EventDispatchGuard;
use App\Modules\Automation\Support\PatientAutomationIdentity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchScheduledEventsCommand extends Command
{
    protected $signature = 'hospital-automation:dispatch-scheduled-events
        {--date= : Calendar date (Y-m-d). Defaults to today.}
        {--min-inactive-days=30 : Minimum whole days since last DoctorBooking.booking_date.}';

    protected $description = 'Dispatch one daily ScheduledEvent per inactive patient. Both 30- and 90-day graphs share this trigger; per-workflow executionTime (10:00 vs 10:30) is Step 3.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $minDays = max(0, (int) $this->option('min-inactive-days'));
        $expires = $date->copy()->endOfDay()->addHour();
        $count = 0;
        $skipped = 0;

        Persons::query()
            ->with('hipUser')
            ->orderBy('id')
            ->cursor()
            ->each(function (Persons $person) use ($date, $minDays, $expires, &$count, &$skipped): void {
                $latest = DoctorBooking::query()
                    ->where('patient_id', $person->id)
                    ->whereNotNull('booking_date')
                    ->orderByDesc('booking_date')
                    ->orderByDesc('id')
                    ->first();

                if (! $latest?->booking_date) {
                    return;
                }

                $lastVisitDays = $latest->booking_date->copy()->startOfDay()->diffInDays($date);

                if ($lastVisitDays < $minDays) {
                    return;
                }

                $hospitalId = $latest->hospital_id ?? PatientAutomationIdentity::hospitalId($person);
                $key = 'scheduledEvent:'.$person->id.':'.(string) $hospitalId.':'.$date->toDateString();
                if (! EventDispatchGuard::claim($key, $expires)) {
                    $skipped++;

                    return;
                }

                // Do not attach the booking model as `appointment`: AutomationEngine::alreadyStarted
                // would then skip later calendar days for the same last booking.
                ScheduledEvent::dispatch($person, [
                    'hospital_id' => $hospitalId,
                    'organization_id' => PatientAutomationIdentity::organizationId($person),
                    'last_visit' => $lastVisitDays,
                    'last_visit_days' => $lastVisitDays,
                ]);

                $count++;
            });

        $this->info("Dispatched ScheduledEvent {$count} time(s) (min inactive days {$minDays}) for {$date->toDateString()} (skipped {$skipped} duplicate(s)).");

        return self::SUCCESS;
    }
}
