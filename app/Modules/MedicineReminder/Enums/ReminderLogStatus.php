<?php

namespace App\Modules\MedicineReminder\Enums;

enum ReminderLogStatus: string
{
    case Started = 'started';
    case Completed = 'completed';
    case Failed = 'failed';
}
