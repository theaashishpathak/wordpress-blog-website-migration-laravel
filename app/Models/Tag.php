<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasContextualActivityLog;
use App\Concerns\HasTranslations;
use App\Contracts\Translatable;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;

/**
 * Tag — folksonomy taxonomy attached polymorphically to posts.
 *
 * Schema split:
 *   tags table              — code, color, type, status, created_by, updated_by
 *   tags table (LEGACY)     — name, slug (kept for backward compat with the
 *                             existing TagFormModal UI; the canonical source
 *                             is now tag_translations)
 *   tag_translations table  — per-locale name, slug, description, meta
 *
 * The TagObserver auto-creates the default-language tag_translations row
 * whenever a Tag is saved via legacy code, so all reads via translate()
 * find data even for tags created through the existing single-language UI.
 *
 * Authoritative spec: docs/Multilanguage Schema.txt Section 6.
 */
class Tag extends Model implements Translatable
{
    /** @use HasFactory<TagFactory> */
    use HasContextualActivityLog, HasFactory, HasTranslations;

    public function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'code', 'type', 'status', 'color'])
            ->logOnlyDirty()
            ->useLogName('tag')
            ->setDescriptionForEvent(fn (string $event): string => "Tag {$event}")
            ->dontSubmitEmptyLogs();
    }

    public const TYPE_GENERAL = 'general';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_UNPUBLISHED = 'unpublished';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'slug',
        'color',
        'type',
        'status',
        'created_by',
        'updated_by',
    ];

    public static function translationModel(): string
    {
        return TagTranslation::class;
    }

    /**
     * @return BelongsTo<User, Tag>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, Tag>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Posts pivoted to this tag. The post_tag pivot has its own timestamps
     * (created_at only) so callers can sort by when the tag was attached.
     *
     * @return BelongsToMany<Post>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag')
            ->withPivot('created_at');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
