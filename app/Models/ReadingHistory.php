<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    use HasFactory;

    protected $table = 'reading_history';

    protected $fillable = [
        'user_id', 'post_id', 'first_read_at', 'last_read_at',
        'read_count', 'read_duration_seconds', 'completed',
    ];

    protected $casts = [
        'first_read_at' => 'datetime',
        'last_read_at' => 'datetime',
        'completed' => 'boolean',
        'read_count' => 'integer',
        'read_duration_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
