<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Actions\Visitor\Follow\ToggleAuthorFollowAction;
use App\Actions\Visitor\Follow\ToggleTopicFollowAction;
use App\Actions\Visitor\Follow\ToggleUserFollowAction;
use App\Models\AuthorFollow;
use App\Models\Category;
use App\Models\Tag;
use App\Models\TopicFollow;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Generic follow button. Embedded in frontend Category / Tag / Author pages
 * and (later) on visitor profile pages.
 *
 *   <livewire:frontend.follow-button :targetType="'tag'" :targetId="$tag->id" />
 *   <livewire:frontend.follow-button :targetType="'category'" :targetId="$category->id" />
 *   <livewire:frontend.follow-button :targetType="'author'" :targetId="$user->id" />
 *   <livewire:frontend.follow-button :targetType="'user'" :targetId="$user->id" />
 *
 * Guests render a "Sign in to follow" placeholder so social proof + the
 * follower count stay visible to anonymous readers.
 */
class FollowButton extends Component
{
    public string $targetType;

    public int $targetId;

    public bool $isFollowing = false;

    public int $followerCount = 0;

    public function mount(string $targetType, int $targetId): void
    {
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->refreshState();
    }

    public function toggle(): void
    {
        $user = auth()->user();

        if ($user === null || $user->portal_type !== 'visitor') {
            $this->dispatch('toast', message: 'Sign in as a reader to follow.');

            return;
        }

        match ($this->targetType) {
            'tag' => app(ToggleTopicFollowAction::class)->handle($user, Tag::query()->findOrFail($this->targetId)),
            'category' => app(ToggleTopicFollowAction::class)->handle($user, Category::query()->findOrFail($this->targetId)),
            'author' => app(ToggleAuthorFollowAction::class)->handle($user, User::query()->findOrFail($this->targetId)),
            'user' => app(ToggleUserFollowAction::class)->handle($user, User::query()->findOrFail($this->targetId)),
            default => abort(422, 'Unknown follow target.'),
        };

        $this->refreshState();
        $this->dispatch('toast', message: $this->isFollowing ? 'Following' : 'Unfollowed');
    }

    private function refreshState(): void
    {
        $user = auth()->user();

        $this->followerCount = match ($this->targetType) {
            'tag' => TopicFollow::query()
                ->where('followable_type', Tag::class)
                ->where('followable_id', $this->targetId)
                ->count(),
            'category' => TopicFollow::query()
                ->where('followable_type', Category::class)
                ->where('followable_id', $this->targetId)
                ->count(),
            'author' => AuthorFollow::query()->where('author_id', $this->targetId)->count(),
            'user' => UserFollow::query()->where('followed_id', $this->targetId)->count(),
            default => 0,
        };

        if ($user === null || $user->portal_type !== 'visitor') {
            $this->isFollowing = false;

            return;
        }

        $this->isFollowing = match ($this->targetType) {
            'tag' => TopicFollow::query()
                ->where('user_id', $user->id)
                ->where('followable_type', Tag::class)
                ->where('followable_id', $this->targetId)
                ->exists(),
            'category' => TopicFollow::query()
                ->where('user_id', $user->id)
                ->where('followable_type', Category::class)
                ->where('followable_id', $this->targetId)
                ->exists(),
            'author' => AuthorFollow::query()
                ->where('follower_id', $user->id)
                ->where('author_id', $this->targetId)
                ->exists(),
            'user' => UserFollow::query()
                ->where('follower_id', $user->id)
                ->where('followed_id', $this->targetId)
                ->exists(),
            default => false,
        };
    }

    public function render(): View
    {
        return view('livewire.frontend.follow-button');
    }
}
