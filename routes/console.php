<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| These run automatically when the server cron entry is configured:
|     * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
|
| Manually invoke for testing:
|     php artisan schedule:list
|     php artisan schedule:run
|     php artisan estimates:notify-expiring --dry-run
*/

// Notify customers about estimates expiring within the next 3 days.
Schedule::command('estimates:notify-expiring')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

// Mark sent/partial invoices past their due_date as overdue and notify customers.
Schedule::command('invoices:mark-overdue')
    ->dailyAt('06:30')
    ->withoutOverlapping()
    ->onOneServer();

// Send all admins a digest of yesterday's KPIs.
Schedule::command('admin:daily-digest')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

// Prune old login_logs + activity_logs beyond the configured retention window.
Schedule::command('logs:prune')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

// Promote Scheduled posts to Published when their scheduled_at arrives.
// Runs every minute so newsroom timing is tight.
Schedule::command('posts:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Fetch RSS sources every 5 minutes. Each source has its own
// `fetch_interval_minutes` and gets skipped when its last fetch is
// recent enough, so this is just the outer pulse.
Schedule::command('rss:import')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// GDPR — finalise account deletions whose 30-day grace window has elapsed.
// Runs once a day in the early hours so any user logging in mid-grace can
// still hit "Cancel deletion" before the row gets processed.
Schedule::command('accounts:process-deletions')
    ->dailyAt('04:30')
    ->withoutOverlapping()
    ->onOneServer();
