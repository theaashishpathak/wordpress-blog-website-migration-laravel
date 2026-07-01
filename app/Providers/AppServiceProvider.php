<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\Tag;
use App\Observers\PostObserver;
use App\Observers\TagObserver;
use App\Services\SettingService;
use App\Support\LocaleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

// NOTE: Activity logging has moved to spatie/laravel-activitylog.
// Each model that needs auditing opts in via App\Concerns\HasContextualActivityLog.
// No global Eloquent event listener — see config/activitylog.php for retention.

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // LocaleResolver is a per-request singleton so SetLocale middleware
        // and downstream Livewire / controller code share the same instance
        // (and the cached active-language map).
        $this->app->singleton(LocaleResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Keep legacy single-language Tag columns in sync with the
        // translation table. See App\Observers\TagObserver.
        Tag::observe(TagObserver::class);

        // Backfill published_at + stamp updated_by + invalidate caches
        // around Post lifecycle changes. See App\Observers\PostObserver.
        Post::observe(PostObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        // RecordLoginLog is auto-discovered by Laravel based on its handle(Login $event) signature,
        // so an explicit Event::listen() here would register the listener twice and create
        // duplicate LoginLog rows on each login.

        $this->bootDemoModeGuard();
        $this->shareSettingService();
    }

    /**
     * Make the SettingService available in every view as `$settings`.
     *
     * Always share the service instance so views always have `$settings` in scope.
     * The service itself handles the missing-table case (returns empty array)
     * — important for fresh installs and tests that boot the app before migrating.
     */
    protected function shareSettingService(): void
    {
        View::share('settings', $this->app->make(SettingService::class));
    }

    /**
     * Block all data mutations when DEMO_MODE is on.
     *
     * Hooks into Eloquent's saving/deleting events so it works for both
     * traditional HTTP form submits AND Livewire actions. Console (artisan,
     * seeders, migrations, queue workers) are exempt so demo data can still
     * be seeded and scheduled jobs can run.
     *
     * Auth-only User updates (remember_token, 2FA columns) are allowed so
     * the login flow keeps working without compromising demo data.
     */
    protected function bootDemoModeGuard(): void
    {
        if (! (bool) config('app.demo_mode')) {
            return;
        }

        if (app()->runningInConsole()) {
            return;
        }

        // Columns on User that may legitimately change during login / 2FA flow.
        $authOnlyUserColumns = [
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'two_factor_confirmed_at',
            'current_team_id',
        ];

        $blocker = function (string $event, array $payload) use ($authOnlyUserColumns): void {
            $model = $payload[0] ?? null;

            if (! $model) {
                return;
            }

            // Allow User saves where ONLY auth-related columns are dirty.
            if ($model instanceof \App\Models\User) {
                $dirty = array_keys($model->getDirty());

                if ($dirty !== [] && array_diff($dirty, $authOnlyUserColumns) === []) {
                    return;
                }
            }

            abort(403, 'Demo mode: data changes are disabled.');
        };

        Event::listen('eloquent.saving: *', $blocker);
        Event::listen('eloquent.deleting: *', $blocker);
    }

}
