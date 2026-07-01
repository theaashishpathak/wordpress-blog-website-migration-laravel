<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle status of a Page (overall — separate from per-translation
 * is_published toggle which gates individual locales).
 */
enum PageStatus: string
{
    case Draft = 'draft';

    case Published = 'published';

    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }
}
