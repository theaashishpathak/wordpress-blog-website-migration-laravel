<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenience to read a user setting with a fallback default.
     */
    public static function getValue(int $userId, string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('user_id', $userId)->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    /**
     * Convenience to set a user setting (upsert).
     */
    public static function setValue(int $userId, string $key, mixed $value): self
    {
        /** @var self $setting */
        $setting = static::query()->updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );

        return $setting;
    }
}
