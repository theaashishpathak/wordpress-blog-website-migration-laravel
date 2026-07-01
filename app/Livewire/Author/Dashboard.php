<?php

declare(strict_types=1);

namespace App\Livewire\Author;

use App\Enums\PostStatus;
use App\Models\AIUsageLog;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AIUsageTracker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Author Portal dashboard — distinct from the platform-wide MyDashboard.
 *
 * Anyone with the `posts.create` permission lands here as their default
 * dashboard view; admins and editors see the wider CRM-style MyDashboard.
 *
 * Stats cover only the current user's own work:
 *   - Posts by status (draft / pending / published / total)
 *   - Lifetime views
 *   - AI usage this month (calls + cost)
 *   - 5 most recent posts
 *   - Last editorial notes targeted at them (request-changes / reject)
 */
#[Layout('layouts.app')]
#[Title('Author Dashboard')]
class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('posts.create') ?? false,
            403,
            'You do not have author privileges.',
        );
    }

    #[Computed]
    public function user(): User
    {
        /** @var User $u */
        $u = auth()->user();

        return $u;
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $baseQuery = Post::query()->where('author_id', $this->user->id);

        return [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', PostStatus::Draft->value)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', [
                PostStatus::PendingReview->value,
                PostStatus::InReview->value,
            ])->count(),
            'changes_requested' => (clone $baseQuery)->where('status', PostStatus::ChangesRequested->value)->count(),
            'published' => (clone $baseQuery)->where('status', PostStatus::Published->value)->count(),
            'scheduled' => (clone $baseQuery)->where('status', PostStatus::Scheduled->value)->count(),
        ];
    }

    #[Computed]
    public function totalViews(): int
    {
        return (int) Post::query()
            ->where('author_id', $this->user->id)
            ->sum('view_count');
    }

    /**
     * @return array{calls:int, tokens:int, cost:float}
     */
    #[Computed]
    public function aiUsageThisMonth(): array
    {
        $stats = AIUsageLog::query()
            ->forUser($this->user->id)
            ->thisMonth()
            ->successful()
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(total_tokens), 0) as tokens, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->first();

        return [
            'calls' => (int) ($stats?->calls ?? 0),
            'tokens' => (int) ($stats?->tokens ?? 0),
            'cost' => (float) ($stats?->cost ?? 0),
        ];
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function recentPosts(): Collection
    {
        return Post::query()
            ->with(['translations', 'category.translations'])
            ->where('author_id', $this->user->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();
    }

    /**
     * Editorial notes left on the author's posts requiring action.
     *
     * @return Collection<int, \App\Models\EditorialNote>
     */
    #[Computed]
    public function actionableNotes(): Collection
    {
        $postIds = Post::query()
            ->where('author_id', $this->user->id)
            ->whereIn('status', [
                PostStatus::ChangesRequested->value,
                PostStatus::Rejected->value,
            ])
            ->pluck('id');

        return \App\Models\EditorialNote::query()
            ->with('post.translations', 'author:id,name')
            ->whereIn('post_id', $postIds)
            ->whereIn('type', [
                \App\Models\EditorialNote::TYPE_REQUEST_CHANGES,
                \App\Models\EditorialNote::TYPE_REJECT,
            ])
            ->latest()
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.author.dashboard');
    }
}
