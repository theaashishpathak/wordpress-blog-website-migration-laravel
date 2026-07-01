<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EditorialNoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit + collaboration record on a Post.
 *
 * Written by:
 *   - ApprovePostAction       (type=approve)
 *   - RejectPostAction        (type=reject — body required)
 *   - RequestChangesAction    (type=request_changes — body required)
 *   - AddEditorialNoteAction  (type=internal_comment, ad-hoc editor chatter)
 *   - AI suggestion features  (type=ai_suggestion)
 */
class EditorialNote extends Model
{
    /** @use HasFactory<EditorialNoteFactory> */
    use HasFactory;

    public const TYPE_APPROVE = 'approve';

    public const TYPE_REJECT = 'reject';

    public const TYPE_REQUEST_CHANGES = 'request_changes';

    public const TYPE_INTERNAL_COMMENT = 'internal_comment';

    public const TYPE_AI_SUGGESTION = 'ai_suggestion';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_APPROVE,
        self::TYPE_REJECT,
        self::TYPE_REQUEST_CHANGES,
        self::TYPE_INTERNAL_COMMENT,
        self::TYPE_AI_SUGGESTION,
    ];

    /** @var list<string> */
    protected $fillable = [
        'post_id',
        'author_id',
        'type',
        'body',
        'mention_user_ids',
        'is_internal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mention_user_ids' => 'array',
            'is_internal' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<Post, EditorialNote>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<User, EditorialNote>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublicNotes(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternalNotes(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForPost(Builder $query, int $postId): Builder
    {
        return $query->where('post_id', $postId);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
