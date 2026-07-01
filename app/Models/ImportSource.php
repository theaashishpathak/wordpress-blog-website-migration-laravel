<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ImportSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Saved RSS feed configuration. The `rss:import` console command
 * iterates active sources whose `last_fetched_at` is older than
 * `fetch_interval_minutes` and fans out one ImportFeedAction per row.
 */
class ImportSource extends Model
{
    /** @use HasFactory<ImportSourceFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ERROR = 'error';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_ERROR,
    ];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'feed_url',
        'category_id',
        'default_language_id',
        'status',
        'auto_publish',
        'default_post_type',
        'fetch_interval_minutes',
        'last_fetched_at',
        'last_error',
        'item_count',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_publish' => 'boolean',
            'fetch_interval_minutes' => 'integer',
            'item_count' => 'integer',
            'last_fetched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Category, ImportSource>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Language, ImportSource>
     */
    public function defaultLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'default_language_id');
    }

    /**
     * @return HasMany<ImportedItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ImportedItem::class, 'source_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Eligible-to-fetch: status=active AND last_fetched_at is either
     * null or older than the configured interval. Branches per-driver so
     * the same scope works under SQLite (tests) and MySQL (production).
     */
    public function scopeDueForFetch(Builder $query): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        return $query->active()->where(function ($q) use ($driver): void {
            $q->whereNull('last_fetched_at');

            if ($driver === 'sqlite') {
                $q->orWhereRaw(
                    "datetime(last_fetched_at, '+' || fetch_interval_minutes || ' minutes') <= datetime('now')"
                );
            } else {
                $q->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_fetched_at, NOW()) >= fetch_interval_minutes');
            }
        });
    }
}
