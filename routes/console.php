<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:pending-payments')->everyMinute();

Schedule::command('medicine-reminders:dispatch')->everyMinute()->withoutOverlapping();

Schedule::command('hospital-automation:dispatch-birthdays')->dailyAt('09:00')->withoutOverlapping();

Schedule::command('hospital-automation:dispatch-anniversaries')->dailyAt('09:00')->withoutOverlapping();

// Inactive 30 JSON uses 10:00; inactive 90 uses 10:30. One ScheduledEvent starts every
// published scheduledEvent workflow for the hospital, so a second 10:30 run would duplicate.
Schedule::command('hospital-automation:dispatch-scheduled-events')->dailyAt('10:00')->withoutOverlapping();

Schedule::command('hospital-automation:dispatch-missed-appointments')->everyMinute()->withoutOverlapping();

Schedule::call(fn () => app(\App\Services\FamilyPackageService::class)->expireStaleSubscriptions())
    ->dailyAt('00:05')
    ->name('expire-subscriptions');

Artisan::command('subscriptions:expire', function () {
    $count = app(\App\Services\FamilyPackageService::class)->expireStaleSubscriptions();
    $this->info("Expired {$count} subscription(s).");
})->purpose('Mark past-due family subscriptions as expired');
