<?php

namespace App\Modules\MedicineReminder\Enums;

enum NotificationChannel: string
{
    case Push = 'push';
    case WhatsApp = 'whatsapp';
    case Sms = 'sms';
    case Email = 'email';
}
