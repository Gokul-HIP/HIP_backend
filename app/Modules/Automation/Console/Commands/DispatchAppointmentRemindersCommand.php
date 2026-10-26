<?php

namespace App\Modules\Automation\Console\Commands;

use App\Modules\Automation\Services\AppointmentReminderScheduler;
use Illuminate\Console\Command;

class DispatchAppointmentRemindersCommand extends Command
{
    protected $signature = 'appointment-reminders:dispatch';
    protected $description = 'Synchronize and dispatch due hospital appointment reminders';

    public function handle(AppointmentReminderScheduler $scheduler): int
    {
        $count = $scheduler->synchronizeAndDispatch();
        $this->info("Dispatched {$count} appointment reminder workflow(s).");
        return self::SUCCESS;
    }
}
