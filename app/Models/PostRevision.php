<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PostRevisionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable snapshot of a Post + its translations + tag IDs at a given
 * point in time.
 *
 * Written by CreatePostRevisionAction; never updated. Updated-at column
 * intentionally absent — revisions are append-only audit records.
 */
class PostRevision extends Model
{
    /** @use HasFactory<PostRevisionFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'post_id',
        'revision_number',
        'author_id',
        'snapshot',
        'summary',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<Post, PostRevision>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<User, PostRevision>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForPost(Builder $query, int $postId): Builder
    {
        return $query->where('post_id', $postId);
    }

    public function scopeLatestRevision(Builder $query): Builder
    {
        return $query->orderByDesc('revision_number');
    }

    public function scopeOldestRevision(Builder $query): Builder
    {
        return $query->orderBy('revision_number');
    }

    /**
     * Convenience accessor to read a single field from the JSON snapshot.
     */
    public function snapshotValue(string $key, mixed $default = null): mixed
    {
        $snapshot = $this->snapshot ?? [];

        return data_get($snapshot, $key, $default);
    }
}
