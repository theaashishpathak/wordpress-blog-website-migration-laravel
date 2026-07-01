<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasContextualActivityLog;
use App\Concerns\HasTranslations;
use App\Contracts\Translatable;
use App\Enums\PageStatus;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

/**
 * Page — static content (About, Contact, Privacy Policy, Terms, ...).
 *
 * Non-translatable: status, template, show_in_menu, sort_order, FK columns.
 * Per-locale: title, slug, content, meta, og_image, is_published — lives
 * on PageTranslation.
 *
 * Two-level publishing gate:
 *   pages.status                = published   (overall lifecycle)
 *   page_translations.is_published = true     (per-locale toggle)
 *
 * Both must be true for the frontend to serve a localized page.
 *
 * Authoritative spec: docs/Multilanguage Schema.txt Section 4.
 */
class Page extends Model implements Translatable
{
    /** @use HasFactory<PageFactory> */
    use HasContextualActivityLog, HasFactory, HasTranslations, SoftDeletes;

    public function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'template', 'show_in_menu', 'sort_order'])
            ->logOnlyDirty()
            ->useLogName('page')
            ->setDescriptionForEvent(fn (string $event): string => "Page {$event}")
            ->dontSubmitEmptyLogs();
    }

    public const TEMPLATE_DEFAULT = 'default';

    public const TEMPLATE_FULL_WIDTH = 'full-width';

    public const TEMPLATE_LANDING = 'landing';

    /** @var list<string> */
    public const TEMPLATES = [
        self::TEMPLATE_DEFAULT,
        self::TEMPLATE_FULL_WIDTH,
        self::TEMPLATE_LANDING,
    ];

    /** @var list<string> */
    protected $fillable = [
        'status',
        'template',
        'show_in_menu',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'show_in_menu' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function translationModel(): string
    {
        return PageTranslation::class;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<User, Page>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, Page>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published->value);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Draft->value);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Archived->value);
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Pages that are fully visible in a given locale — status=published
     * AND a translation exists for that locale with is_published=true.
     */
    public function scopeVisibleIn(Builder $query, int $languageId): Builder
    {
        return $query
            ->where('status', PageStatus::Published->value)
            ->whereHas('translations', function (Builder $q) use ($languageId): void {
                $q->where('language_id', $languageId)->where('is_published', true);
            });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === PageStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === PageStatus::Archived;
    }

    /**
     * Returns true only when both the page-level status is `published`
     * AND the locale's translation row is_published is true.
     */
    public function isPublishedIn(?string $locale = null): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        $translation = $this->translation($locale);

        return $translation !== null && (bool) $translation->getAttribute('is_published');
    }

    /**
     * Build the public URL for this page in a given locale.
     */
    public function urlFor(?string $locale = null): string
    {
        $slug = (string) ($this->translate('slug', $locale) ?? '');

        if ($slug === '') {
            return '/';
        }

        $localePrefix = $locale !== null ? "/{$locale}" : '';

        return "{$localePrefix}/page/{$slug}";
    }
}
