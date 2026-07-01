<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Models\AIUsageLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * AI Usage Overview — a dashboard-style summary of AI spend that
 * complements the deeper /admin/ai/usage-reports page. Focuses on
 * "what's happening right now" rather than the date-range analytics
 * already covered by UsageReports.
 */
#[Layout('layouts.app')]
#[Title('Dashboard · AI Usage')]
class AiOverview extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('ai.reports') ?? false,
            403,
            'You do not have access to AI usage data.',
        );
    }

    /**
     * Compare this month vs last month so the deltas are surface-level.
     *
     * @return array{
     *   month_calls:int, month_tokens:int, month_cost:float, month_success_rate:float,
     *   prev_calls:int, prev_tokens:int, prev_cost:float,
     *   today_calls:int, today_cost:float,
     * }
     */
    #[Computed]
    public function tiles(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $prevStart = now()->subMonthNoOverflow()->startOfMonth();
        $prevEnd = now()->subMonthNoOverflow()->endOfMonth();

        $month = AIUsageLog::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(total_tokens), 0) as tokens, COALESCE(SUM(estimated_cost_usd), 0) as cost, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ok', [AIUsageLog::STATUS_SUCCESS])
            ->first();

        $prev = AIUsageLog::query()
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(total_tokens), 0) as tokens, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->first();

        $today = AIUsageLog::query()
            ->whereDate('created_at', now()->toDateString())
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->first();

        $monthCalls = (int) ($month->calls ?? 0);

        return [
            'month_calls' => $monthCalls,
            'month_tokens' => (int) ($month->tokens ?? 0),
            'month_cost' => round((float) ($month->cost ?? 0), 4),
            'month_success_rate' => $monthCalls > 0 ? round(((int) ($month->ok ?? 0) / $monthCalls) * 100, 1) : 0.0,
            'prev_calls' => (int) ($prev->calls ?? 0),
            'prev_tokens' => (int) ($prev->tokens ?? 0),
            'prev_cost' => round((float) ($prev->cost ?? 0), 4),
            'today_calls' => (int) ($today->calls ?? 0),
            'today_cost' => round((float) ($today->cost ?? 0), 4),
        ];
    }

    /**
     * Day-by-day cost line for the last 30 days.
     *
     * @return array{labels:list<string>, cost:list<float>, calls:list<int>}
     */
    #[Computed]
    public function costChart(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $rows = AIUsageLog::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as c, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $cost = [];
        $calls = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $day = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $cost[] = isset($rows[$day]) ? round((float) $rows[$day]->cost, 4) : 0.0;
            $calls[] = isset($rows[$day]) ? (int) $rows[$day]->c : 0;
        }

        return ['labels' => $labels, 'cost' => $cost, 'calls' => $calls];
    }

    /**
     * @return Collection<int, object{provider:string, model:?string, calls:int, cost:float}>
     */
    #[Computed]
    public function byProvider(): Collection
    {
        return AIUsageLog::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('provider, COUNT(*) as calls, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->groupBy('provider')
            ->orderByDesc('cost')
            ->get();
    }

    /**
     * @return Collection<int, object{feature_key:string, calls:int, cost:float}>
     */
    #[Computed]
    public function byFeature(): Collection
    {
        return AIUsageLog::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereNotNull('feature_key')
            ->selectRaw('feature_key, COUNT(*) as calls, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->groupBy('feature_key')
            ->orderByDesc('cost')
            ->get();
    }

    /**
     * @return Collection<int, AIUsageLog>
     */
    #[Computed]
    public function recentErrors(): Collection
    {
        return AIUsageLog::query()
            ->where('status', '!=', AIUsageLog::STATUS_SUCCESS)
            ->with('user:id,name')
            ->latest()
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboards.ai-overview');
    }
}
