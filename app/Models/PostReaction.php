<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    use HasFactory;

    public const TYPE_LIKE = 'like';

    public const TYPE_DISLIKE = 'dislike';

    public const TYPES = [self::TYPE_LIKE, self::TYPE_DISLIKE];

    protected $fillable = ['user_id', 'post_id', 'type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @param Builder<self> $query */
    public function scopeLikes(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_LIKE);
    }

    /** @param Builder<self> $query */
    public function scopeDislikes(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DISLIKE);
    }
}
