<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TopicFollow extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'followable_type', 'followable_id', 'notify_on_post'];

    protected $casts = [
        'notify_on_post' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Followable can be a Tag or Category.
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }
}
