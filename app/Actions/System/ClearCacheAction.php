<?php

declare(strict_types=1);

namespace App\Actions\System;

use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Clear one or more Laravel cache layers from the admin UI.
 *
 * Supported targets:
 *   - "app"      : application cache (cache:clear)
 *   - "config"   : compiled config (config:clear)
 *   - "view"     : compiled blade views (view:clear)
 *   - "route"    : route cache (route:clear)
 *   - "settings" : NewsPilot's SettingService memo (rememberForever)
 *   - "all"      : everything above in sequence
 *
 * Each call records a structured log line so admins can audit who
 * cleared what and when. Failures on one target do NOT abort the
 * others — we collect every result and return them so the UI can
 * show partial-success states ("4 cleared, 1 failed").
 */
final class ClearCacheAction
{
    public function __construct(private SettingService $settings) {}

    /**
     * @param  list<string>  $targets  Names from the supported list above.
     * @return array<string, array{ok: bool, message: string}>
     *                              Keyed by target name.
     */
    public function handle(array $targets = ['all']): array
    {
        if (in_array('all', $targets, true)) {
            $targets = ['app', 'config', 'view', 'route', 'settings'];
        }

        $results = [];

        foreach ($targets as $target) {
            $results[$target] = $this->clear($target);
        }

        Log::channel(config('logging.default', 'stack'))
            ->info('admin.cache.cleared', [
                'targets' => $targets,
                'results' => array_map(static fn ($r): bool => $r['ok'], $results),
                'user_id' => auth()->id(),
            ]);

        return $results;
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function clear(string $target): array
    {
        try {
            return match ($target) {
                'app' => $this->runArtisan('cache:clear', 'Application cache cleared.'),
                'config' => $this->runArtisan('config:clear', 'Configuration cache cleared.'),
                'view' => $this->runArtisan('view:clear', 'Compiled views cleared.'),
                'route' => $this->runArtisan('route:clear', 'Route cache cleared.'),
                'all' => $this->runArtisan('optimize:clear', 'All cache cleared.'),
                // --force lets the command idempotently recreate the symlink
                // (without it, a second click throws when public/storage exists).
                'storage' => $this->runArtisan('storage:link', 'Storage symlink created.', ['--force' => true]),
                'settings' => $this->clearSettings(),
                default => [
                    'ok' => false,
                    'message' => "Unknown cache target: {$target}",
                ],
            };
        } catch (\Throwable $e) {
            report($e);

            return [
                'ok' => false,
                'message' => 'Failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $parameters  Optional Artisan parameters
     *                                            (e.g. ['--force' => true]).
     * @return array{ok: bool, message: string}
     */
    private function runArtisan(string $command, string $successMessage, array $parameters = []): array
    {
        $exitCode = Artisan::call($command, $parameters);

        return [
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0
                ? $successMessage
                : "Command {$command} exited with code {$exitCode}.",
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function clearSettings(): array
    {
        // SettingService caches the key/value map with rememberForever;
        // reloadCache() forgets and warms it.
        $this->settings->reloadCache();

        // Also nuke any keys other code may have used for related caches.
        Cache::forget('crm.settings.key_value_map');

        return [
            'ok' => true,
            'message' => 'Settings cache reloaded.',
        ];
    }
}
