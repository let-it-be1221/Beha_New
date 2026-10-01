<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/**
 * Beha — console commands + scheduler.
 *
 * Spec §7 (nightly rank recalculation), §24 (audit retention).
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('beha:performance:recalculate')
    ->cron(config('evaluation.rank_recalculation.batch_cron', '0 2 * * *'))
    ->name('beha.performance.recalculate')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('beha:audit:prune')
    ->dailyAt('03:30')
    ->name('beha.audit.prune')
    ->runInBackground();
