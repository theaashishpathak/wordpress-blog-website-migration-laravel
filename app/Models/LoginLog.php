<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_LOGOUT = 'logout';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_LOGOUT,
    ];

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'ip_address', 'user_agent',
        'device', 'platform', 'browser', 'device_type',
        'country', 'country_code', 'city',
        'status', 'attempted_email',
        'login_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['login_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
