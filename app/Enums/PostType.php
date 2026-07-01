<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Discriminator on the single `posts` table. Each value renders with a
 * different frontend layout and admin sidebar group.
 *
 *   Post         — Long-form blog article (default).
 *   News         — Newsroom item, may be marked Breaking.
 *   PageArticle  — Premium long-form feature / cover story.
 *   Video        — Embedded video (YouTube / Vimeo) with description.
 *   Gallery      — Image gallery with captions.
 *   Short        — Google Web Stories-style short-form card.
 */
enum PostType: string
{
    case Post = 'post';

    case News = 'news';

    case PageArticle = 'page_article';

    case Video = 'video';

    case Gallery = 'gallery';

    case Short = 'short';

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
            self::Post => 'Blog Post',
            self::News => 'News Article',
            self::PageArticle => 'Feature Article',
            self::Video => 'Video',
            self::Gallery => 'Gallery',
            self::Short => 'Short',
        };
    }

    /**
     * Lucide icon name for sidebar/admin badges.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Post => 'file-text',
            self::News => 'newspaper',
            self::PageArticle => 'book-open',
            self::Video => 'video',
            self::Gallery => 'images',
            self::Short => 'film',
        };
    }

    public function isNewsroomItem(): bool
    {
        return in_array($this, [self::News, self::Short], true);
    }

    public function supportsBreakingFlag(): bool
    {
        return $this === self::News;
    }
}
