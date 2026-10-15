<?php

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
Schedule::command(config('tenancy.enabled') ? 'tenants:run sjpos:backup' : 'sjpos:backup')->dailyAt('23:30');

// With tenancy on: trials and paid time that ran out become past due, then paused after the grace period
if (config('tenancy.enabled')) {
    Schedule::command('tenants:check-billing')->dailyAt('01:00')->withoutOverlapping();
}
