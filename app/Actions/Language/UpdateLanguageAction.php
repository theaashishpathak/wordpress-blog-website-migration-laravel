<?php

declare(strict_types=1);

namespace App\Actions\Language;

use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Update a language. Switching the default flag is handled atomically:
 * the previous default is demoted within the same transaction.
 *
 * Refuses to demote the *current* default to non-default without
 * promoting another row in the same call — the system always needs
 * exactly one default.
 */
class UpdateLanguageAction
{
    public function __construct(private LocaleResolver $localeResolver) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Language $language, array $data): Language
    {
        return DB::transaction(function () use ($language, $data): Language {
            $shouldBeDefault = array_key_exists('is_default', $data)
                ? (bool) $data['is_default']
                : (bool) $language->is_default;

            // Guard: cannot demote the current default unless another
            // row will be promoted in the same Action call (handled by
            // promoting first, then demoting via the cascade below).
            if ($language->is_default && ! $shouldBeDefault) {
                throw new InvalidArgumentException(
                    'Cannot demote the default language directly. Promote another language to default first.'
                );
            }

            if ($shouldBeDefault && ! $language->is_default) {
                Language::query()
                    ->where('is_default', true)
                    ->where('id', '!=', $language->id)
                    ->update(['is_default' => false]);
            }

            $fillable = array_intersect_key($data, array_flip([
                'code', 'name', 'native_name', 'flag_emoji', 'flag_icon',
                'direction', 'is_default', 'is_active', 'is_admin_locale',
                'sort_order', 'locale_php',
            ]));

            if (isset($fillable['code'])) {
                $fillable['code'] = strtolower(trim((string) $fillable['code']));
            }
            if (isset($fillable['name'])) {
                $fillable['name'] = trim((string) $fillable['name']);
            }
            foreach (['native_name', 'flag_emoji', 'flag_icon', 'locale_php'] as $nullable) {
                if (isset($fillable[$nullable])) {
                    $fillable[$nullable] = trim((string) $fillable[$nullable]) ?: null;
                }
            }

            $language->fill($fillable)->save();
            $this->localeResolver->flush();

            return $language->fresh();
        });
    }
}
