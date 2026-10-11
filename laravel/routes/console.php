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

// Runs when `php artisan schedule:run` is called every minute (Task Scheduler / cron)
Schedule::command('sjpos:backup')->dailyAt('23:30');
