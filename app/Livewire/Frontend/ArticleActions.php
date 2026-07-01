<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Actions\Visitor\Bookmark\ToggleBookmarkAction;
use App\Actions\Visitor\Highlight\CreateHighlightAction;
use App\Actions\Visitor\Reaction\ToggleReactionAction;
use App\Actions\Visitor\ReadingList\ToggleReadingListAction;
use App\Models\Bookmark;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\ReadingListItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Inline action bar rendered on the single-post page for logged-in visitors.
 * Owns three controls:
 *   - Toggle bookmark
 *   - Toggle read-later
 *   - Capture highlight (called from Alpine when text is selected)
 *
 * Guests see a grayed-out "Sign in to save" prompt instead.
 */
class ArticleActions extends Component
{
    public Post $post;

    public bool $isBookmarked = false;

    public bool $isInReadingList = false;

    public int $highlightsCount = 0;

    /** Current user's reaction (null|'like'|'dislike') */
    public ?string $reactionType = null;

    public int $likesCount = 0;

    public int $dislikesCount = 0;

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->refreshState();
    }

    public function toggleBookmark(): void
    {
        $user = auth()->user();
        abort_if($user === null || $user->portal_type !== 'visitor', 403);

        app(ToggleBookmarkAction::class)->handle($user, $this->post);
        $this->refreshState();

        $this->dispatch('toast', message: $this->isBookmarked ? 'Bookmarked' : 'Removed from bookmarks');
    }

    public function toggleReadingList(): void
    {
        $user = auth()->user();
        abort_if($user === null || $user->portal_type !== 'visitor', 403);

        app(ToggleReadingListAction::class)->handle($user, $this->post);
        $this->refreshState();

        $this->dispatch('toast', message: $this->isInReadingList ? 'Added to Reading List' : 'Removed from Reading List');
    }

    public function react(string $type): void
    {
        $user = auth()->user();
        abort_if($user === null || $user->portal_type !== 'visitor', 403);

        $result = app(ToggleReactionAction::class)->handle($user, $this->post, $type);
        $this->refreshState();

        $message = match ($result['action']) {
            'created' => $type === 'like' ? 'Liked!' : 'Disliked',
            'switched' => $type === 'like' ? 'Changed to Like' : 'Changed to Dislike',
            'removed' => 'Reaction removed',
            default => '',
        };

        if ($message !== '') {
            $this->dispatch('toast', message: $message);
        }
    }

    /**
     * Called from Alpine after the user selects text + clicks Save Highlight.
     *
     * @param  array{ selected_text: string, note?: ?string, start_offset?: ?int, end_offset?: ?int }  $payload
     */
    public function saveHighlight(array $payload): void
    {
        $user = auth()->user();
        abort_if($user === null || $user->portal_type !== 'visitor', 403);

        app(CreateHighlightAction::class)->handle(
            user: $user,
            post: $this->post,
            data: $payload,
        );

        $this->refreshState();
        $this->dispatch('toast', message: 'Highlight saved');
    }

    private function refreshState(): void
    {
        // Reaction counts are public — show them even to guests so the
        // social proof works for first-time readers too.
        $this->likesCount = PostReaction::query()
            ->where('post_id', $this->post->id)
            ->where('type', PostReaction::TYPE_LIKE)
            ->count();

        $this->dislikesCount = PostReaction::query()
            ->where('post_id', $this->post->id)
            ->where('type', PostReaction::TYPE_DISLIKE)
            ->count();

        $user = auth()->user();
        if ($user === null) {
            $this->isBookmarked = false;
            $this->isInReadingList = false;
            $this->highlightsCount = 0;
            $this->reactionType = null;

            return;
        }

        $this->isBookmarked = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('post_id', $this->post->id)
            ->exists();

        $this->isInReadingList = ReadingListItem::query()
            ->where('user_id', $user->id)
            ->where('post_id', $this->post->id)
            ->whereNull('dismissed_at')
            ->exists();

        $this->highlightsCount = $user->highlights()->where('post_id', $this->post->id)->count();

        $reaction = PostReaction::query()
            ->where('user_id', $user->id)
            ->where('post_id', $this->post->id)
            ->first();
        $this->reactionType = $reaction?->type;
    }

    public function render(): View
    {
        return view('livewire.frontend.article-actions');
    }
}
