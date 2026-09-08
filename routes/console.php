<?php

use App\Jobs\AssignScheduledOrders;
use App\Jobs\SendDocumentExpiryAlerts;
use App\Jobs\SendScheduledNotification;
use App\Models\ScheduledNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Assign drivers to scheduled orders as their start time approaches.
Schedule::job(new AssignScheduledOrders)->everyMinute()->withoutOverlapping();

// Daily driver document expiry warnings (09:00).
Schedule::job(new SendDocumentExpiryAlerts)->dailyAt('09:00');

// Dispatch any due scheduled broadcast notifications.
Schedule::call(function () {
    ScheduledNotification::where('status', 'pending')
        ->where('scheduled_at', '<=', now())
        ->each(fn ($n) => dispatch(new SendScheduledNotification($n->id)));
})->everyMinute()->name('dispatch-scheduled-notifications')->withoutOverlapping();
