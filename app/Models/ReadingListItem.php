<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingListItem extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'post_id', 'added_at', 'dismissed_at'];

    protected $casts = [
        'added_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('dismissed_at');
    }

    /** @param Builder<self> $query */
    public function scopeDismissed(Builder $query): Builder
    {
        return $query->whereNotNull('dismissed_at');
    }
}
