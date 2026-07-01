<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AIPromptTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Versioned prompt template — every AI generation records the
 * (key, version) pair it consumed so the result is reproducible.
 *
 * Active template lookup is keyed on (key, locale) with is_active=true.
 * Edit history is preserved by inserting new rows (version bumped),
 * NEVER mutating an old one — historical generations must still be able
 * to point at the exact text they were rendered from.
 */
class AIPromptTemplate extends Model
{
    /** @use HasFactory<AIPromptTemplateFactory> */
    use HasFactory;

    /**
     * Laravel's default class→table converter would turn `AIPromptTemplate`
     * into `a_i_prompt_templates`. Pin the cleaner name we use in the
     * migration.
     */
    protected $table = 'ai_prompt_templates';

    /** @var list<string> */
    protected $fillable = [
        'key',
        'version',
        'locale',
        'system_prompt',
        'user_prompt_template',
        'variables',
        'model_hint',
        'temperature_hint',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'variables' => 'array',
            'temperature_hint' => 'float',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<User, AIPromptTemplate>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeLatestVersion(Builder $query): Builder
    {
        return $query->orderByDesc('version');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the active version for (key, locale). Returns null if none
     * exists — caller may fall back to a different locale or fail loudly.
     */
    public static function active(string $key, string $locale): ?self
    {
        return self::query()
            ->forKey($key)
            ->forLocale($locale)
            ->active()
            ->latestVersion()
            ->first();
    }

    /**
     * Create a new version of this template with updated content, mark
     * it active, and deactivate the previous active row in the same
     * (key, locale) bucket. Original rows are NEVER mutated — historical
     * generations stay reproducible.
     *
     * @param  array<string, mixed>  $updates  any fillable fields
     */
    public function bumpVersion(array $updates = []): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($updates): self {
            self::query()
                ->where('key', $this->key)
                ->where('locale', $this->locale)
                ->update(['is_active' => false]);

            $nextVersion = ((int) self::query()
                ->where('key', $this->key)
                ->where('locale', $this->locale)
                ->max('version')) + 1;

            $next = $this->replicate(['id']);
            $next->version = $nextVersion;
            $next->is_active = true;
            $next->fill($updates);
            $next->save();

            return $next;
        });
    }

    /**
     * Variables this template REQUIRES to render. PromptBuilder validates
     * against this list before interpolation.
     *
     * @return list<string>
     */
    public function requiredVariables(): array
    {
        $vars = $this->variables ?? [];

        if (! is_array($vars)) {
            return [];
        }

        return array_values(array_filter(
            $vars,
            fn (mixed $name): bool => is_string($name) && $name !== '',
        ));
    }
}
