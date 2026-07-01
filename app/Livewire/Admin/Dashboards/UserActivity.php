<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Models\AIUsageLog;
use App\Models\LoginLog;
use App\Models\Post;
use App\Models\ProfileActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * User Activity dashboard — who's signing in, who's shipping content,
 * who's using AI. Reads from:
 *
 *   - users                  — staff list + roles
 *   - login_logs             — login events
 *   - profile_activity_logs  — profile edits / 2FA toggles
 *   - posts                  — author productivity
 *   - ai_usage_logs          — per-user AI spend
 */
#[Layout('layouts.app')]
#[Title('Dashboard · User Activity')]
class UserActivity extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->canAny(['staff.view', 'logs.login.view', 'logs.activity.view']) ?? false,
            403,
            'You do not have access to user activity.',
        );
    }

    /**
     * @return array{total:int, active_7d:int, logins_24h:int, logins_30d:int}
     */
    #[Computed]
    public function tiles(): array
    {
        // Only count *successful* logins — failed attempts and logout
        // events also live in login_logs now but shouldn't inflate the
        // "active users" / "logins" tiles.
        $active7d = User::query()
            ->whereHas('loginLogs', fn ($q) => $q->where('login_at', '>=', now()->subDays(7))
                ->where('status', LoginLog::STATUS_SUCCESS))
            ->count();

        return [
            'total' => User::query()->count(),
            'active_7d' => $active7d,
            'logins_24h' => LoginLog::query()
                ->where('status', LoginLog::STATUS_SUCCESS)
                ->where('login_at', '>=', now()->subDay())
                ->count(),
            'logins_30d' => LoginLog::query()
                ->where('status', LoginLog::STATUS_SUCCESS)
                ->where('login_at', '>=', now()->subDays(30))
                ->count(),
            'failed_24h' => LoginLog::query()
                ->where('status', LoginLog::STATUS_FAILED)
                ->where('login_at', '>=', now()->subDay())
                ->count(),
        ];
    }

    /**
     * Daily login count, last 14 days.
     *
     * @return array{labels:list<string>, counts:list<int>}
     */
    #[Computed]
    public function loginsChart(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = LoginLog::query()
            ->where('status', LoginLog::STATUS_SUCCESS)
            ->where('login_at', '>=', $start)
            ->selectRaw('DATE(login_at) as day, COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day');

        $labels = [];
        $counts = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M j');
            $counts[] = (int) ($rows[$day] ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * @return Collection<int, LoginLog>
     */
    #[Computed]
    public function recentLogins(): Collection
    {
        return LoginLog::query()
            ->with('user:id,name,email,avatar')
            ->latest('login_at')
            ->limit(12)
            ->get();
    }

    /**
     * @return Collection<int, ProfileActivityLog>
     */
    #[Computed]
    public function recentActivity(): Collection
    {
        return ProfileActivityLog::query()
            ->with('user:id,name,email,avatar')
            ->latest()
            ->limit(15)
            ->get();
    }

    /**
     * Posts shipped per user in the last 30 days.
     *
     * @return Collection<int, object{name:?string, email:?string, avatar:?string, posts:int, last_post:?string}>
     */
    #[Computed]
    public function topAuthors(): Collection
    {
        return Post::query()
            ->join('users', 'posts.author_id', '=', 'users.id')
            ->where('posts.created_at', '>=', now()->subDays(30))
            ->selectRaw('users.id as user_id, users.name as name, users.email as email, users.avatar as avatar, COUNT(posts.id) as posts, MAX(posts.created_at) as last_post')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.avatar')
            ->orderByDesc('posts')
            ->limit(10)
            ->get();
    }

    /**
     * AI spend per user this month.
     *
     * @return Collection<int, object{name:?string, calls:int, tokens:int, cost:float}>
     */
    #[Computed]
    public function topAiUsers(): Collection
    {
        return AIUsageLog::query()
            ->leftJoin('users', 'ai_usage_logs.user_id', '=', 'users.id')
            ->whereBetween('ai_usage_logs.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('users.name as name, COUNT(ai_usage_logs.id) as calls, COALESCE(SUM(ai_usage_logs.total_tokens), 0) as tokens, COALESCE(SUM(ai_usage_logs.estimated_cost_usd), 0) as cost')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('cost')
            ->limit(10)
            ->get()
            ->map(fn ($row) => (object) [
                'name' => $row->name ?? 'Anonymous',
                'calls' => (int) $row->calls,
                'tokens' => (int) $row->tokens,
                'cost' => round((float) $row->cost, 4),
            ]);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboards.user-activity');
    }
}
