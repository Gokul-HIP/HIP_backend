<?php

namespace App\Modules\MedicineReminder\Enums;

enum WorkflowStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
