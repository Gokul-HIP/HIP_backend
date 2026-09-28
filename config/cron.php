<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default timezone for application cron jobs
    |--------------------------------------------------------------------------
    */
    'timezone' => env('CRON_TIMEZONE', 'Asia/Kolkata'),

    /*
    |--------------------------------------------------------------------------
    | Overlap lock (seconds)
    |--------------------------------------------------------------------------
    */
    'lock_seconds' => (int) env('CRON_LOCK_SECONDS', 900),

    /*
    |--------------------------------------------------------------------------
    | Truncate stored command output
    |--------------------------------------------------------------------------
    */
    'max_output_chars' => 20000,

    /*
    |--------------------------------------------------------------------------
    | Approved Artisan commands (allowlist)
    |--------------------------------------------------------------------------
    |
    | Keys are exact Artisan command names. Admins may only select these.
    | Do not add commands that accept untrusted arguments from the UI.
    | Do not add automation:test (it is a dedicated test harness).
    |
    */
    'commands' => [
        'hospital-automation:dispatch-missed-appointments' => [
            'name' => 'Missed Appointment Detector',
            'description' => 'Mark overdue confirmed appointments as missed and dispatch AppointmentMissed workflows.',
        ],
        'hospital-automation:dispatch-birthdays' => [
            'name' => 'Birthday Dispatcher',
            'description' => 'Dispatch BirthdayReached events for patients whose birthday is today.',
        ],
        'hospital-automation:dispatch-anniversaries' => [
            'name' => 'Anniversary Dispatcher',
            'description' => 'Dispatch AnniversaryReached events for patients whose anniversary is today.',
        ],
        'hospital-automation:dispatch-scheduled-events' => [
            'name' => 'Scheduled Event Dispatcher',
            'description' => 'Start published scheduledEvent hospital automation workflows.',
        ],
        'medicine-reminders:dispatch' => [
            'name' => 'Medicine Reminder Dispatcher',
            'description' => 'Dispatch due medicine reminder notifications.',
        ],
        'hospital-automation:dispatch-pending-payments' => [
            'name' => 'Payment Pending Detector',
            'description' => 'Dispatch PaymentPending for invoices still pending after the 30-minute reminder window.',
        ],
        'subscriptions:expire' => [
            'name' => 'Expire Family Subscriptions',
            'description' => 'Mark past-due family package subscriptions as expired.',
        ],
    ],

    'schedule_presets' => [
        'every_minute' => [
            'label' => 'Every Minute',
            'expression' => '* * * * *',
        ],
        'every_5_minutes' => [
            'label' => 'Every 5 Minutes',
            'expression' => '*/5 * * * *',
        ],
        'every_15_minutes' => [
            'label' => 'Every 15 Minutes',
            'expression' => '*/15 * * * *',
        ],
        'every_30_minutes' => [
            'label' => 'Every 30 Minutes',
            'expression' => '*/30 * * * *',
        ],
        'hourly' => [
            'label' => 'Hourly',
            'expression' => '0 * * * *',
        ],
        'daily' => [
            'label' => 'Daily',
            'expression' => '0 0 * * *',
        ],
        'weekly' => [
            'label' => 'Weekly',
            'expression' => '0 0 * * 0',
        ],
        'custom' => [
            'label' => 'Custom Expression',
            'expression' => null,
        ],
    ],

];
