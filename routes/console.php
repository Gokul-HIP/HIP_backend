<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:pending-payments')->everyMinute();

Schedule::call(fn () => app(\App\Services\FamilyPackageService::class)->expireStaleSubscriptions())
    ->dailyAt('00:05')
    ->name('expire-subscriptions');

Artisan::command('subscriptions:expire', function () {
    $count = app(\App\Services\FamilyPackageService::class)->expireStaleSubscriptions();
    $this->info("Expired {$count} subscription(s).");
})->purpose('Mark past-due family subscriptions as expired');
