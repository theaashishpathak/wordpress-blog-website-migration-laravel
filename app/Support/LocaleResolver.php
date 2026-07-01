<?php

namespace App\Support;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for locale resolution.
 *
 * Consumed by the SetLocale middleware on every HTTP request and by jobs /
 * console commands that need to render localized content without a request
 * context. Active language list is cached to avoid hitting the DB on every
 * page load; cache is invalidated when LanguageSeeder runs or admin updates
 * a language (Observer will handle invalidation when the admin UI lands).
 */
class LocaleResolver
{
    private const CACHE_KEY_ACTIVE = 'newspilot.languages.active';

    private const CACHE_KEY_DEFAULT = 'newspilot.languages.default';

    private const CACHE_TTL_SECONDS = 3600;

    private ?Language $current = null;

    /**
     * The locale chosen for the current request lifecycle (set by middleware).
     */
    public function current(): ?Language
    {
        return $this->current;
    }

    public function setCurrent(Language $language): void
    {
        $this->current = $language;
    }

    /**
     * Resolve a Language model by code, falling back to the configured default.
     *
     * Returns null if no language exists at all (fresh install before seeding).
     */
    public function resolve(?string $code): ?Language
    {
        if ($code !== null && $code !== '') {
            $byCode = $this->byCode($code);

            if ($byCode !== null && $byCode->is_active) {
                return $byCode;
            }
        }

        return $this->default();
    }

    public function byCode(string $code): ?Language
    {
        return $this->activeMap()[$code] ?? null;
    }

    public function default(): ?Language
    {
        try {
            $cached = Cache::get(self::CACHE_KEY_DEFAULT);

            if ($cached !== null && ! $cached instanceof Language) {
                // Stale serialization (class wasn't autoloaded when cached).
                Cache::forget(self::CACHE_KEY_DEFAULT);
                $cached = null;
            }

            if ($cached === null) {
                $cached = Language::query()->default()->active()->first();

                if ($cached !== null) {
                    Cache::put(self::CACHE_KEY_DEFAULT, $cached, self::CACHE_TTL_SECONDS);
                }
            }
        } catch (\Throwable) {
            // languages table may not exist yet (fresh install or test boot).
            return null;
        }

        return $cached;
    }

    /**
     * @return array<string, Language>
     */
    public function activeMap(): array
    {
        try {
            $cached = Cache::get(self::CACHE_KEY_ACTIVE);

            // Defensive: a previously-cached value may have been serialized
            // before the Language class was autoloaded (e.g., cache created
            // by an earlier request that did not yet have multi-language
            // wired up). PHP returns __PHP_Incomplete_Class in that case —
            // detect, drop the cache, and re-query.
            if ($cached !== null && ! $this->isValidActiveMap($cached)) {
                Cache::forget(self::CACHE_KEY_ACTIVE);
                $cached = null;
            }

            if ($cached === null) {
                $cached = Language::query()
                    ->active()
                    ->ordered()
                    ->get()
                    ->keyBy('code')
                    ->all();

                Cache::put(self::CACHE_KEY_ACTIVE, $cached, self::CACHE_TTL_SECONDS);
            }
        } catch (\Throwable) {
            return [];
        }

        return $cached;
    }

    /**
     * @param  mixed  $value
     */
    private function isValidActiveMap(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! $item instanceof Language) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<Language>
     */
    public function activeLanguages(): array
    {
        return array_values($this->activeMap());
    }

    public function isValidCode(string $code): bool
    {
        return isset($this->activeMap()[$code]);
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY_ACTIVE);
        Cache::forget(self::CACHE_KEY_DEFAULT);
        $this->current = null;
    }
}
