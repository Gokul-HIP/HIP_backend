<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Medicine reminder clock mapping
    |--------------------------------------------------------------------------
    |
    | Moved from the former workflow-node default_times on MedicineReminderService.
    | These are product schedule clocks, not workflow message copy.
    |
    */

    'when_to_take_times' => [
        'morning' => env('MEDICINE_REMINDER_MORNING', '09:00'),
        'afternoon' => env('MEDICINE_REMINDER_AFTERNOON', '14:00'),
        'evening' => env('MEDICINE_REMINDER_EVENING', '18:00'),
        'night' => env('MEDICINE_REMINDER_NIGHT', '21:00'),
        'bedtime' => env('MEDICINE_REMINDER_BEDTIME', '21:00'),
    ],

    'frequency_times' => [
        'once_daily' => ['09:00'],
        'twice_daily' => ['09:00', '21:00'],
        'thrice_daily' => ['09:00', '14:00', '21:00'],
        'four_times_daily' => ['08:00', '12:00', '16:00', '20:00'],
        'every_6_hours' => ['06:00', '12:00', '18:00', '00:00'],
        'every_8_hours' => ['08:00', '16:00', '00:00'],
        'every_12_hours' => ['09:00', '21:00'],
    ],

];
