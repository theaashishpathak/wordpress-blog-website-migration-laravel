<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasContextualActivityLog;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

/**
 * Comment — reader engagement attached to a Post.
 *
 * Lifecycle:
 *   pending   → awaiting moderation (default for guests + suspicious patterns)
 *   approved  → visible on the public post page
 *   spam      → not visible, kept for training the spam filter
 *   trash     → soft-deleted, recoverable from admin
 *
 * Authorship is either a User (`user_id` set) or a guest pair
 * (`guest_name`, `guest_email`). When both are absent the comment is
 * rejected at the Action layer.
 *
 * Threading is single-level — `parent_id` points at another Comment
 * but replies to replies still attach to the top-level parent so the
 * UI stays flat-two-deep.
 */
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasContextualActivityLog, HasFactory, SoftDeletes;

    /**
     * Activity log for moderation actions — status transitions matter
     * most (pending → approved → spam → trash). Body edits are also
     * tracked so we can see when a reader edited their comment.
     */
    public function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'body', 'approved_at'])
            ->logOnlyDirty()
            ->useLogName('comment')
            ->setDescriptionForEvent(fn (string $event): string => "Comment {$event}")
            ->dontSubmitEmptyLogs();
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SPAM = 'spam';

    public const STATUS_TRASH = 'trash';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_SPAM,
        self::STATUS_TRASH,
    ];

    /** @var list<string> */
    protected $fillable = [
        'post_id',
        'parent_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_website',
        'body',
        'status',
        'approved_at',
        'moderated_by',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<Post, Comment>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<Comment, Comment>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSpam(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SPAM);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function authorName(): string
    {
        if ($this->author !== null) {
            return $this->author->name;
        }

        return (string) ($this->guest_name ?? 'Anonymous');
    }

    public function authorEmail(): ?string
    {
        return $this->author?->email ?? $this->guest_email;
    }

    /**
     * Avatar URL for the comment author. Falls back to a gravatar-style
     * stub since we don't store guest avatars.
     */
    public function avatarUrl(): ?string
    {
        if ($this->author?->avatar) {
            return $this->author->avatar;
        }

        $email = $this->authorEmail();

        if ($email !== null && $email !== '') {
            return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?d=mp&s=80';
        }

        return null;
    }
}
