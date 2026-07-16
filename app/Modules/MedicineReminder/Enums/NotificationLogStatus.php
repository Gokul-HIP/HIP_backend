<?php

namespace App\Modules\MedicineReminder\Enums;

enum NotificationLogStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
