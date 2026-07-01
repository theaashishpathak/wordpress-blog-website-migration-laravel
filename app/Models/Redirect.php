<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Admin-managed URL redirect rule. Looked up by `from_path` on every
 * frontend request via the HandleRedirects middleware.
 */
class Redirect extends Model
{
    use SoftDeletes;

    /** @var list<int> */
    public const STATUS_CODES = [301, 302, 307, 308];

    /** @var list<string> */
    protected $fillable = [
        'from_path',
        'to_url',
        'status_code',
        'is_active',
        'preserve_query',
        'notes',
        'hit_count',
        'last_hit_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'preserve_query' => 'boolean',
            'status_code' => 'integer',
            'hit_count' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
