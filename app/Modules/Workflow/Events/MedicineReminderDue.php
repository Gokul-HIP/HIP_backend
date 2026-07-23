<?php

namespace App\Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MedicineReminderDue
{
    use Dispatchable;

    public function __construct(
        public int $scheduleId,
    ) {}
}
