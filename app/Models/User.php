<?php

namespace App\Models;

use App\Concerns\HasContextualActivityLog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'phone', 'mobile', 'avatar', 'gender', 'date_of_birth',
    'portal_type', 'status', 'timezone', 'locale',
    'employee_id', 'job_title', 'department_id', 'manager_id', 'hire_date',
    'employment_type', 'working_hours',
    // Author-portal fields (Phase 5B)
    'bio', 'social_links', 'public_slug', 'show_in_team',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasContextualActivityLog, HasFactory, HasRoles, Notifiable;

    /**
     * Activity log configuration — captures staff-level changes (role,
     * portal type, status, employment metadata) in the admin audit
     * trail. Profile-level edits (avatar, password, social) continue to
     * write to profile_activity_logs via User::logProfileActivity().
     */
    public function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'email', 'portal_type', 'status', 'locale',
                'employee_id', 'job_title', 'department_id', 'manager_id',
                'employment_type', 'show_in_team',
            ])
            ->logOnlyDirty()
            ->useLogName('user')
            ->setDescriptionForEvent(fn (string $event): string => "User {$event}")
            ->dontSubmitEmptyLogs();
    }

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_SUSPENDED = 'suspended';

    public const EMPLOYMENT_FULL_TIME = 'full_time';

    public const EMPLOYMENT_PART_TIME = 'part_time';

    public const EMPLOYMENT_CONTRACTOR = 'contractor';

    public const EMPLOYMENT_INTERN = 'intern';

    public const EMPLOYMENT_CONSULTANT = 'consultant';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_SUSPENDED,
    ];

    /**
     * @var list<string>
     */
    public const EMPLOYMENT_TYPES = [
        self::EMPLOYMENT_FULL_TIME,
        self::EMPLOYMENT_PART_TIME,
        self::EMPLOYMENT_CONTRACTOR,
        self::EMPLOYMENT_INTERN,
        self::EMPLOYMENT_CONSULTANT,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'password' => 'hashed',
            'working_hours' => 'array',
            'social_links' => 'array',
            'show_in_team' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Visitor portal relationships (Phase V1)
    |--------------------------------------------------------------------------
    | All of these are for the reader-side portal: bookmarks, reading list,
    | reading history, reactions, highlights, follows, settings, prefs.
    | Each one is fk'd cascadeOnDelete so user removal cleans up properly.
    */

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function readingListItems(): HasMany
    {
        return $this->hasMany(ReadingListItem::class);
    }

    public function readingHistory(): HasMany
    {
        return $this->hasMany(ReadingHistory::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(Highlight::class);
    }

    public function topicFollows(): HasMany
    {
        return $this->hasMany(TopicFollow::class);
    }

    /** Authors this user follows (visitor → author). */
    public function authorFollows(): HasMany
    {
        return $this->hasMany(AuthorFollow::class, 'follower_id');
    }

    /** Followers of this user as an author (author ← visitors). */
    public function authorFollowers(): HasMany
    {
        return $this->hasMany(AuthorFollow::class, 'author_id');
    }

    /** Other users this visitor follows (social). */
    public function following(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'follower_id');
    }

    /** Users following this visitor. */
    public function followers(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'followed_id');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function userSettings(): HasMany
    {
        return $this->hasMany(UserSetting::class);
    }

    public function dataExportRequests(): HasMany
    {
        return $this->hasMany(DataExportRequest::class);
    }

    public function accountDeletionRequests(): HasMany
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    public function isStaff(): bool
    {
        return $this->portal_type !== 'visitor';
    }

    public function isVisitor(): bool
    {
        return $this->portal_type === 'visitor';
    }

    public function isAuthor(): bool
    {
        return $this->portal_type === 'author';
    }

    public function isAdminPortal(): bool
    {
        return $this->portal_type === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function avatarUrl(): string
    {
        if ($this->avatar === null || $this->avatar === '') {
            return 'https://ui-avatars.com/api/?name='.urlencode($this->name ?: 'User');
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://', '//'])) {
            return $this->avatar;
        }

        if (Str::startsWith($this->avatar, '/')) {
            return $this->avatar;
        }

        if (Str::startsWith($this->avatar, 'storage/')) {
            return asset($this->avatar);
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function profileActivityLogs(): HasMany
    {
        return $this->hasMany(ProfileActivityLog::class);
    }

    /**
     * Login events recorded by RecordLoginLog listener. Exposes the
     * relationship so UserActivity dashboard can `whereHas('loginLogs')`
     * filters and `->loginLogs()->latest()->limit(5)` lookups work
     * without dropping back to a raw query.
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function createdTags(): HasMany
    {
        return $this->hasMany(Tag::class, 'created_by');
    }

    public function updatedTags(): HasMany
    {
        return $this->hasMany(Tag::class, 'updated_by');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function logProfileActivity(string $event, string $description, array $meta = []): ProfileActivityLog
    {
        /** @var ProfileActivityLog $profileActivityLog */
        $profileActivityLog = $this->profileActivityLogs()->create([
            'event' => $event,
            'description' => $description,
            'meta' => $meta === [] ? null : $meta,
        ]);

        return $profileActivityLog;
    }
}
