<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @var list<string>
     */
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_ARCHIVED];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'head_user_id',
        'status',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Department $department): void {
            if ($department->code === null || $department->code === '') {
                $department->code = self::generateCode();
            }

            if ($department->slug === null || $department->slug === '') {
                $department->slug = Str::slug($department->name);
            }
        });

        static::updating(function (Department $department): void {
            if ($department->isDirty('name') && ! $department->isDirty('slug')) {
                $department->slug = Str::slug($department->name);
            }
        });
    }

    public static function generateCode(): string
    {
        return DB::transaction(static function (): string {
            $latest = self::query()->lockForUpdate()->orderByDesc('id')->value('code');
            $nextNumber = 1;

            if (is_string($latest) && preg_match('/^DEPT-(\d+)$/', $latest, $matches) === 1) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return 'DEPT-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
