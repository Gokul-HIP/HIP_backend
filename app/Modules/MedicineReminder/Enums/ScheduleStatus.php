<?php

namespace App\Modules\MedicineReminder\Enums;

enum ScheduleStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
