<?php

use App\Services\Alerts;
use App\Services\DatabaseBackup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sjpos:backup', function (DatabaseBackup $backups) {
    $this->info('Backup written: '.$backups->create());
})->purpose('Back up the database to storage/app/backups');

// Runs when `php artisan schedule:run` is called every minute (Task Scheduler / cron).
// With tenancy on, each business's database is backed up in turn.
Schedule::command(config('tenancy.enabled') ? 'tenants:run sjpos:backup' : 'sjpos:backup')->dailyAt('23:30')
    ->onFailure(fn () => app(Alerts::class)->send('The nightly backup failed', 'One or more database backups could not be written. Check storage/logs and that the storage folder is writable by the web server user.'));

// Background jobs (queued emails): a short-lived worker every minute, so no separate process manager is needed
if (config('queue.default') !== 'sync') {
    Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')->everyMinute()->withoutOverlapping();
}

// With tenancy on: trials and paid time that ran out become past due, then paused after the grace period
if (config('tenancy.enabled')) {
    Schedule::command('tenants:check-billing')->dailyAt('01:00')->withoutOverlapping()
        ->onFailure(fn () => app(Alerts::class)->send('The daily billing check failed', 'tenants:check-billing did not finish. Run it by hand to see the error.'));
}
