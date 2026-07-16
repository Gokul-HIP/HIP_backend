<?php

namespace App\Modules\MedicineReminder\Console;

use App\Modules\MedicineReminder\Services\MedicineReminderScheduler;
use Illuminate\Console\Command;

class DispatchMedicineRemindersCommand extends Command
{
    protected $signature = 'medicine-reminders:dispatch {--limit=100 : Max schedules to dispatch}';

    protected $description = 'Dispatch due medicine reminder schedules to the queue';

    public function handle(MedicineReminderScheduler $scheduler): int
    {
        $count = $scheduler->dispatchDueReminders((int) $this->option('limit'));

        $this->info("Dispatched {$count} medicine reminder job(s).");

        return self::SUCCESS;
    }
}
