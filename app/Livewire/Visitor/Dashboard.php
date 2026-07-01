<?php

declare(strict_types=1);

namespace App\Livewire\Visitor;

use App\Actions\Visitor\Recommendation\BuildRecommendationsAction;
use App\Models\AccountDeletionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Visitor portal landing page.
 *
 * Surfaces:
 *   - Welcome banner (greeting, reader-since, jump back to site)
 *   - 8 live stat tiles (library + engagement counts)
 *   - "For You" preview — top 4 recommendations
 *   - Recent activity feed — last 5 bookmarks / history / reactions / highlights
 *   - Profile card with public link + sign-out
 *   - Pending-deletion alert when applicable
 */
#[Layout('layouts.visitor')]
#[Title('My Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        $picks = app(BuildRecommendationsAction::class)->handle($user, limit: 4);

        return view('livewire.visitor.dashboard', [
            'user' => $user,
            'picks' => $picks,
            'recentActivity' => $this->buildRecentActivity($user),
            'pendingDeletion' => AccountDeletionRequest::query()
                ->where('user_id', $user->id)
                ->pending()
                ->latest()
                ->first(),
        ]);
    }

    /**
     * Build a unified recent-activity feed by merging the latest rows
     * from bookmarks / reading_history / reactions / highlights / comments
     * and ordering by timestamp.
     *
     * @return Collection<int, array{type: string, icon: string, color: string, label: string, post: ?\App\Models\Post, occurred_at: ?\Carbon\CarbonInterface}>
     */
    private function buildRecentActivity(\App\Models\User $user): Collection
    {
        $activity = collect();

        $user->bookmarks()->with('post.translations')->latest()->limit(3)
            ->get()->each(function ($bookmark) use ($activity): void {
                $activity->push([
                    'type' => 'bookmark',
                    'icon' => 'bookmark',
                    'color' => 'amber',
                    'label' => 'Bookmarked',
                    'post' => $bookmark->post,
                    'occurred_at' => $bookmark->created_at,
                ]);
            });

        $user->readingHistory()->with('post.translations')->orderByDesc('last_read_at')->limit(3)
            ->get()->each(function ($h) use ($activity): void {
                $activity->push([
                    'type' => 'read',
                    'icon' => 'book-open',
                    'color' => 'violet',
                    'label' => $h->completed ? 'Finished reading' : 'Read',
                    'post' => $h->post,
                    'occurred_at' => $h->last_read_at,
                ]);
            });

        $user->reactions()->with('post.translations')->latest()->limit(3)
            ->get()->each(function ($r) use ($activity): void {
                $activity->push([
                    'type' => 'reaction',
                    'icon' => $r->type === 'like' ? 'thumbs-up' : 'thumbs-down',
                    'color' => $r->type === 'like' ? 'emerald' : 'rose',
                    'label' => $r->type === 'like' ? 'Liked' : 'Disliked',
                    'post' => $r->post,
                    'occurred_at' => $r->created_at,
                ]);
            });

        $user->highlights()->with('post.translations')->latest()->limit(2)
            ->get()->each(function ($h) use ($activity): void {
                $activity->push([
                    'type' => 'highlight',
                    'icon' => 'highlighter',
                    'color' => 'fuchsia',
                    'label' => 'Highlighted',
                    'post' => $h->post,
                    'occurred_at' => $h->created_at,
                ]);
            });

        return $activity
            ->filter(fn ($row) => $row['post'] !== null)
            ->sortByDesc('occurred_at')
            ->take(7)
            ->values();
    }
}
