<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Single-table media library record.
 *
 * Referenced by FK from:
 *   - categories.image_id
 *   - pages.* (Phase 2C did not add an image FK; can be added later)
 *   - posts.featured_image_id (Phase 2E)
 *
 * The `conversions` JSON column reserves space for Phase 3 image
 * processing (WebP variants, responsive sizes) without a schema change.
 *
 * Authoritative spec: docs/Architecture.txt Section 6 + Multilanguage Schema.
 */
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'disk',
        'path',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
        'caption',
        'credit',
        'source_url',
        'conversions',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'conversions' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<User, Media>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Categories that use this media row as their featured image.
     *
     * @return HasMany<Category>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'image_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('mime_type', 'like', 'application/%')
                ->orWhere('mime_type', 'like', 'text/%');
        });
    }

    public function scopeByUploader(Builder $query, int $userId): Builder
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isImage(): bool
    {
        return $this->mime_type !== null && str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return $this->mime_type !== null && str_starts_with((string) $this->mime_type, 'video/');
    }

    public function isDocument(): bool
    {
        if ($this->mime_type === null) {
            return false;
        }

        return str_starts_with((string) $this->mime_type, 'application/')
            || str_starts_with((string) $this->mime_type, 'text/');
    }

    /**
     * Public URL for the original file. Honors absolute URLs in path.
     */
    public function url(): string
    {
        if ($this->path === null || $this->path === '') {
            return '';
        }

        if (Str::startsWith($this->path, ['http://', 'https://', '//'])) {
            return $this->path;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * URL for a registered conversion (e.g., 'webp_800', 'thumb_300') —
     * falls back to the original when the conversion does not exist yet.
     */
    public function conversionUrl(string $key): string
    {
        $conversions = $this->conversions ?? [];

        if (! isset($conversions[$key])) {
            return $this->url();
        }

        $path = (string) $conversions[$key];

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Human-readable byte size — "2.34 MB", "456 KB", etc.
     */
    public function sizeFormatted(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units));

        $value = $bytes / (1024 ** $power);

        return number_format($value, 2).' '.$units[$power - 1];
    }
}
