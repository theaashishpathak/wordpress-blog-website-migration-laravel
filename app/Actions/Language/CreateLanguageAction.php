<?php

declare(strict_types=1);

namespace App\Actions\Language;

use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Support\Facades\DB;

/**
 * Create a new language. If `is_default` is set the previous default
 * is automatically demoted within the same transaction so the system
 * always has exactly one default locale.
 */
class CreateLanguageAction
{
    public function __construct(private LocaleResolver $localeResolver) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Language
    {
        return DB::transaction(function () use ($data): Language {
            if (! empty($data['is_default'])) {
                Language::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $language = Language::query()->create([
                'code' => strtolower(trim((string) $data['code'])),
                'name' => trim((string) $data['name']),
                'native_name' => trim((string) ($data['native_name'] ?? '')) ?: null,
                'flag_emoji' => trim((string) ($data['flag_emoji'] ?? '')) ?: null,
                'flag_icon' => trim((string) ($data['flag_icon'] ?? '')) ?: null,
                'direction' => $data['direction'] ?? Language::DIRECTION_LTR,
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_admin_locale' => (bool) ($data['is_admin_locale'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'locale_php' => trim((string) ($data['locale_php'] ?? '')) ?: null,
            ]);

            $this->localeResolver->flush();

            return $language;
        });
    }
}
