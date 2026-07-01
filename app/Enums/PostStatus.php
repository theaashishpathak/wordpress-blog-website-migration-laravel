<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Editorial lifecycle status for a Post.
 *
 * State machine (allowed transitions enforced by ::canTransitionTo):
 *
 *   Draft               → PendingReview, Published (if author has direct-publish rights)
 *   PendingReview       → InReview, Approved, ChangesRequested, Rejected
 *   InReview            → Approved, ChangesRequested, Rejected
 *   ChangesRequested    → PendingReview (after author update)
 *   Approved            → Scheduled, Published
 *   Scheduled           → Published (when scheduled_at reached) | Approved (cancel)
 *   Published           → Unpublished, Archived
 *   Unpublished         → Published, Archived
 *   Rejected            → Draft (author may revise)
 *   Archived            → (terminal)
 */
enum PostStatus: string
{
    case Draft = 'draft';

    case PendingReview = 'pending_review';

    case InReview = 'in_review';

    case ChangesRequested = 'changes_requested';

    case Approved = 'approved';

    case Scheduled = 'scheduled';

    case Published = 'published';

    case Unpublished = 'unpublished';

    case Rejected = 'rejected';

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
            self::PendingReview => 'Pending Review',
            self::InReview => 'In Review',
            self::ChangesRequested => 'Changes Requested',
            self::Approved => 'Approved',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Unpublished => 'Unpublished',
            self::Rejected => 'Rejected',
            self::Archived => 'Archived',
        };
    }

    /**
     * Visible to frontend visitors only when truly published.
     */
    public function isVisibleToPublic(): bool
    {
        return $this === self::Published;
    }

    /**
     * True while the post is moving through editorial workflow.
     */
    public function isInEditorialFlow(): bool
    {
        return in_array($this, [
            self::PendingReview,
            self::InReview,
            self::ChangesRequested,
            self::Approved,
        ], true);
    }

    /**
     * True for terminal states that cannot move forward without admin action.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Archived, self::Rejected], true);
    }

    /**
     * @return list<self>
     */
    public function allowedNextStates(): array
    {
        return match ($this) {
            self::Draft => [self::PendingReview, self::Published],
            self::PendingReview => [self::InReview, self::Approved, self::ChangesRequested, self::Rejected],
            self::InReview => [self::Approved, self::ChangesRequested, self::Rejected],
            self::ChangesRequested => [self::PendingReview, self::Draft],
            self::Approved => [self::Scheduled, self::Published, self::ChangesRequested],
            self::Scheduled => [self::Published, self::Approved],
            self::Published => [self::Unpublished, self::Archived],
            self::Unpublished => [self::Published, self::Archived],
            self::Rejected => [self::Draft],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return false;
        }

        return in_array($target, $this->allowedNextStates(), true);
    }
}
