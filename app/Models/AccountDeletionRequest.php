<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'reason', 'note', 'scheduled_for',
        'cancelled_at', 'processed_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'cancelled_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pending = scheduled but neither cancelled nor processed.
     *
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at')->whereNull('processed_at');
    }

    /**
     * Due = pending and the grace window has elapsed.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->pending()->where('scheduled_for', '<=', now());
    }
}
