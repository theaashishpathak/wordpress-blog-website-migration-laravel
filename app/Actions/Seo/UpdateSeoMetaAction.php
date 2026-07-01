<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Upsert (or delete) the polymorphic seo_metas row for a given seoable
 * subject + language pair.
 *
 * Basic per-locale SEO fields (meta_title, meta_description,
 * focus_keyword, canonical_url, og_image, seo_score) live on
 * post_translations / page_translations — those are handled by their
 * respective Update*Action. This Action covers the *advanced* overrides:
 *   robots, schema_type, schema_data,
 *   og_title, og_description,
 *   twitter_title, twitter_description, twitter_image,
 *   meta_keywords.
 *
 * Passing an entirely empty payload removes the row to keep the table
 * lean — there's no point persisting all-null overrides.
 */
class UpdateSeoMetaAction
{
    /**
     * Fields this Action manages. Any other key in $data is ignored.
     *
     * @var list<string>
     */
    private const MANAGED_FIELDS = [
        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keyword',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_type',
        'schema_data',
        'seo_score',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Model $seoable, ?int $languageId, array $data): ?SeoMeta
    {
        $payload = $this->sanitize($data);

        $existing = SeoMeta::query()
            ->where('seoable_type', $seoable->getMorphClass())
            ->where('seoable_id', $seoable->getKey())
            ->forLocale($languageId)
            ->first();

        // No meaningful overrides + no existing row → no-op.
        if ($payload === [] && $existing === null) {
            return null;
        }

        // Caller cleared every advanced override → drop the row so we
        // don't keep all-null SEO rows around.
        if ($payload === [] && $existing !== null) {
            $existing->delete();

            return null;
        }

        $payload['seoable_type'] = $seoable->getMorphClass();
        $payload['seoable_id'] = $seoable->getKey();
        $payload['language_id'] = $languageId;

        if ($existing === null) {
            return SeoMeta::query()->create($payload);
        }

        $existing->fill($payload)->save();

        return $existing->fresh();
    }

    /**
     * Strip unknown keys and drop fields whose value is null / empty
     * string. Booleans and integers (e.g., seo_score 0) are preserved.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        $only = Arr::only($data, self::MANAGED_FIELDS);

        $clean = [];

        foreach ($only as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            if (is_array($value) && $value === []) {
                continue;
            }

            $clean[$key] = is_string($value) ? trim($value) : $value;
        }

        return $clean;
    }
}
