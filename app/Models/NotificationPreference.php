<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_PUSH = 'push';

    public const CHANNELS = [self::CHANNEL_EMAIL, self::CHANNEL_IN_APP, self::CHANNEL_PUSH];

    protected $fillable = ['user_id', 'key', 'channel', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catalogue of notification events the visitor portal exposes in the
     * preferences UI. Keys map to the `key` column. `defaults` describes
     * the out-of-the-box on/off state per channel — privacy-first: email
     * is opt-in for almost everything.
     *
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     channels: array<int, string>,
     *     defaults: array<string, bool>,
     *     icon: string
     * }>
     */
    public static function eventCatalog(): array
    {
        return [
            'comment_reply' => [
                'label' => 'Replies to my comments',
                'description' => 'When another reader replies to a comment you posted.',
                'channels' => ['in_app', 'email'],
                'defaults' => ['in_app' => true, 'email' => false],
                'icon' => 'message-square-reply',
            ],
            'comment_approved' => [
                'label' => 'My comment is approved',
                'description' => 'When a moderator publishes a comment you had pending.',
                'channels' => ['in_app', 'email'],
                'defaults' => ['in_app' => true, 'email' => false],
                'icon' => 'check-circle-2',
            ],
            'new_follower' => [
                'label' => 'New follower',
                'description' => 'When another reader starts following you.',
                'channels' => ['in_app', 'email'],
                'defaults' => ['in_app' => true, 'email' => false],
                'icon' => 'user-plus',
            ],
            'author_published' => [
                'label' => 'Authors I follow publish',
                'description' => 'When a writer you follow ships a new article.',
                'channels' => ['in_app', 'email'],
                'defaults' => ['in_app' => true, 'email' => false],
                'icon' => 'pen-tool',
            ],
            'daily_digest' => [
                'label' => 'Daily digest',
                'description' => 'A short morning email with the top stories of the day.',
                'channels' => ['email'],
                'defaults' => ['email' => false],
                'icon' => 'sunrise',
            ],
            'weekly_digest' => [
                'label' => 'Weekly digest',
                'description' => 'Sunday roundup of the week\'s best reads.',
                'channels' => ['email'],
                'defaults' => ['email' => false],
                'icon' => 'calendar-days',
            ],
        ];
    }

    /**
     * Resolve whether a (user, key, channel) tuple is enabled, falling back
     * to the catalog default when no explicit row exists.
     */
    public static function isEnabled(int $userId, string $key, string $channel, ?bool $default = null): bool
    {
        $row = static::query()
            ->where('user_id', $userId)
            ->where('key', $key)
            ->where('channel', $channel)
            ->first();

        if ($row !== null) {
            return (bool) $row->enabled;
        }

        if ($default !== null) {
            return $default;
        }

        $catalog = static::eventCatalog()[$key] ?? null;

        return (bool) ($catalog['defaults'][$channel] ?? false);
    }

    /**
     * Convenience used by notification classes' via() — returns the active
     * Laravel channel names for a given event. `database` is always
     * included if the user opted-in to in_app; `mail` if email is opted-in.
     *
     * @return array<int, string>
     */
    public static function resolveChannels(int $userId, string $key): array
    {
        $channels = [];

        if (static::isEnabled($userId, $key, 'in_app')) {
            $channels[] = 'database';
        }

        if (static::isEnabled($userId, $key, 'email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public static function setValue(int $userId, string $key, string $channel, bool $enabled): self
    {
        /** @var self $pref */
        $pref = static::query()->updateOrCreate(
            ['user_id' => $userId, 'key' => $key, 'channel' => $channel],
            ['enabled' => $enabled]
        );

        return $pref;
    }
}
