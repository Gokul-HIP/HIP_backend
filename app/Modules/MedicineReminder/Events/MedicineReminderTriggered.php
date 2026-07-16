<?php

namespace App\Modules\MedicineReminder\Events;

use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicineReminderTriggered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MedicineReminderSchedule $schedule
    ) {}
}
