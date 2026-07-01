<?php

namespace App\Console\Commands;

use App\Models\LoginLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class PruneLogs extends Command
{
    /**
     * Example usage:
     *   php artisan logs:prune                    # uses defaults from env / config
     *   php artisan logs:prune --days=60          # uniform retention
     *   php artisan logs:prune --login-days=30 --activity-days=90
     *   php artisan logs:prune --dry-run
     *
     * NOTE: Spatie also ships `activitylog:clean` which honours
     * config('activitylog.delete_records_older_than_days'). This command
     * is the unified replacement that prunes both login_logs and the
     * Spatie activity_log table in one pass.
     */
    protected $signature = 'logs:prune
                            {--days= : Apply same retention window to both tables (overrides individual flags)}
                            {--login-days= : Days to retain login_logs (default: env LOG_LOGIN_RETENTION_DAYS or 90)}
                            {--activity-days= : Days to retain activity_log (default: env LOG_ACTIVITY_RETENTION_DAYS or 90)}
                            {--dry-run : Print row counts without deleting}';

    protected $description = 'Prune old login_logs and activity_log entries beyond the configured retention window.';

    public function handle(): int
    {
        $shared = $this->option('days');
        $loginDays = (int) ($shared ?? $this->option('login-days') ?? env('LOG_LOGIN_RETENTION_DAYS', 90));
        $activityDays = (int) ($shared ?? $this->option('activity-days') ?? env('LOG_ACTIVITY_RETENTION_DAYS', 90));

        if ($loginDays < 1 || $activityDays < 1) {
            $this->error('Retention days must be at least 1.');

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');

        $loginCutoff = Carbon::now()->subDays($loginDays);
        $activityCutoff = Carbon::now()->subDays($activityDays);

        $loginQuery = LoginLog::query()->where('created_at', '<', $loginCutoff);
        $activityQuery = Activity::query()->where('created_at', '<', $activityCutoff);

        $loginCount = (clone $loginQuery)->count();
        $activityCount = (clone $activityQuery)->count();

        $this->line(sprintf('Login logs older than %s (%d days): %d row(s)', $loginCutoff->toDateString(), $loginDays, $loginCount));
        $this->line(sprintf('Activity logs older than %s (%d days): %d row(s)', $activityCutoff->toDateString(), $activityDays, $activityCount));

        if ($dryRun) {
            $this->newLine();
            $this->info('[DRY RUN] No rows deleted.');

            return self::SUCCESS;
        }

        $deletedLogin = $loginQuery->delete();
        $deletedActivity = $activityQuery->delete();

        $this->newLine();
        $this->info(sprintf('Pruned %d login log(s), %d activity log(s).', $deletedLogin, $deletedActivity));

        return self::SUCCESS;
    }
}
