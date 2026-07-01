<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'crm.settings.key_value_map';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        try {
            /** @var array<string, mixed> $settings */
            $settings = Cache::rememberForever(self::CACHE_KEY, function (): array {
                return Setting::query()
                    ->orderBy('id')
                    ->get()
                    ->mapWithKeys(fn (Setting $setting): array => [
                        $setting->key => $setting->getValue(),
                    ])
                    ->all();
            });

            return $settings;
        } catch (\Throwable) {
            // Settings table may not exist yet (fresh install, tests booted before migrate).
            // Return empty array so views fall back to defaults via get($key, $default).
            return [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    public function set(string $key, mixed $value, ?string $group = null, ?string $type = null, bool $refreshCache = true): Setting
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null) {
            $setting = new Setting([
                'group' => $group ?? $this->groupFromKey($key),
                'key' => $key,
                'type' => $type ?? Setting::TYPE_JSON,
                'created_by' => auth()->id(),
            ]);
        } else {
            $setting->group = $group ?? $setting->group;
            $setting->type = $type ?? $setting->type;
        }

        $setting->setValue($value);
        $setting->updated_by = auth()->id();
        $setting->save();

        if ($refreshCache) {
            $this->reloadCache();
        }

        return $setting;
    }

    /**
     * @return Collection<string, mixed>
     */
    public function allInGroup(string $group): Collection
    {
        return Setting::query()
            ->group($group)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Setting $setting): array => [
                $setting->key => $setting->getValue(),
            ]);
    }

    public function reloadCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->all();
    }

    private function groupFromKey(string $key): string
    {
        $prefix = explode('.', $key)[0] ?? 'app';

        return match ($prefix) {
            'company' => 'company-settings',
            'financial', 'billing' => 'financial-settings',
            'smtp', 'email' => 'email-smtp-settings',
            'notification' => 'notification-settings',
            'branding' => 'branding-settings',
            'storage', 'files' => 'file-storage-settings',
            'app' => 'app-preferences',
            'security' => 'security-settings',
            default => 'app-preferences',
        };
    }
}
