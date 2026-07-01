<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AI;

use App\Models\AIUsageLog;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * AI Usage Reports — read-only dashboard over `ai_usage_logs`.
 *
 * Surfaces total spend, per-provider / per-feature / per-user breakdowns
 * and the most recent failures. Date range defaults to "this month".
 */
#[Layout('layouts.app')]
#[Title('AI Usage Reports')]
class UsageReports extends Component
{
    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    #[Url(as: 'provider')]
    public string $providerFilter = '';

    #[Url(as: 'feature')]
    public string $featureFilter = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('ai.reports') ?? false,
            403,
            'You do not have access to usage reports.',
        );

        if ($this->from === '') {
            $this->from = now()->startOfMonth()->format('Y-m-d');
        }
        if ($this->to === '') {
            $this->to = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function setRange(string $preset): void
    {
        match ($preset) {
            'today' => [$this->from, $this->to] = [now()->format('Y-m-d'), now()->format('Y-m-d')],
            '7d' => [$this->from, $this->to] = [now()->subDays(6)->format('Y-m-d'), now()->format('Y-m-d')],
            '30d' => [$this->from, $this->to] = [now()->subDays(29)->format('Y-m-d'), now()->format('Y-m-d')],
            'this_month' => [$this->from, $this->to] = [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')],
            'last_month' => [$this->from, $this->to] = [now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')],
            'ytd' => [$this->from, $this->to] = [now()->startOfYear()->format('Y-m-d'), now()->format('Y-m-d')],
            default => null,
        };
    }

    public function clearFilters(): void
    {
        $this->providerFilter = '';
        $this->featureFilter = '';
        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->endOfMonth()->format('Y-m-d');
    }

    /**
     * Base query for the selected date range + filters.
     */
    protected function baseQuery(): Builder
    {
        $fromTs = Carbon::createFromFormat('Y-m-d', $this->from ?: now()->startOfMonth()->format('Y-m-d'))->startOfDay();
        $toTs = Carbon::createFromFormat('Y-m-d', $this->to ?: now()->endOfMonth()->format('Y-m-d'))->endOfDay();

        // Qualify `created_at` with the table name — byUser() left-joins
        // the `users` table, which also has a `created_at` column. Without
        // the table prefix MySQL throws an ambiguous-column error.
        return AIUsageLog::query()
            ->whereBetween('ai_usage_logs.created_at', [$fromTs, $toTs])
            ->when($this->providerFilter !== '', fn ($q) => $q->where('ai_usage_logs.provider', $this->providerFilter))
            ->when($this->featureFilter !== '', fn ($q) => $q->where('ai_usage_logs.feature_key', $this->featureFilter));
    }

    /**
     * Aggregates across the visible window.
     *
     * @return array{calls:int, successful:int, failed:int, tokens:int, cost:float, avg_latency_ms:int}
     */
    #[Computed]
    public function summary(): array
    {
        $row = $this->baseQuery()
            ->selectRaw('COUNT(*) as calls')
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful", [AIUsageLog::STATUS_SUCCESS])
            ->selectRaw("SUM(CASE WHEN status != ? THEN 1 ELSE 0 END) as failed", [AIUsageLog::STATUS_SUCCESS])
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as tokens')
            ->selectRaw('COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->selectRaw('COALESCE(AVG(duration_ms), 0) as avg_latency_ms')
            ->first();

        return [
            'calls' => (int) ($row->calls ?? 0),
            'successful' => (int) ($row->successful ?? 0),
            'failed' => (int) ($row->failed ?? 0),
            'tokens' => (int) ($row->tokens ?? 0),
            'cost' => round((float) ($row->cost ?? 0), 4),
            'avg_latency_ms' => (int) ($row->avg_latency_ms ?? 0),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{provider:string, calls:int, tokens:int, cost:float}>
     */
    #[Computed]
    public function byProvider(): \Illuminate\Support\Collection
    {
        return $this->baseQuery()
            ->selectRaw('provider, COUNT(*) as calls, COALESCE(SUM(total_tokens), 0) as tokens, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->groupBy('provider')
            ->orderByDesc(DB::raw('SUM(estimated_cost_usd)'))
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{feature_key:string, calls:int, tokens:int, cost:float}>
     */
    #[Computed]
    public function byFeature(): \Illuminate\Support\Collection
    {
        return $this->baseQuery()
            ->whereNotNull('feature_key')
            ->selectRaw('feature_key, COUNT(*) as calls, COALESCE(SUM(total_tokens), 0) as tokens, COALESCE(SUM(estimated_cost_usd), 0) as cost')
            ->groupBy('feature_key')
            ->orderByDesc(DB::raw('SUM(estimated_cost_usd)'))
            ->limit(10)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{name:?string, email:?string, calls:int, tokens:int, cost:float}>
     */
    #[Computed]
    public function byUser(): \Illuminate\Support\Collection
    {
        return $this->baseQuery()
            ->leftJoin('users', 'ai_usage_logs.user_id', '=', 'users.id')
            ->selectRaw('users.name as name, users.email as email, COUNT(ai_usage_logs.id) as calls, COALESCE(SUM(ai_usage_logs.total_tokens), 0) as tokens, COALESCE(SUM(ai_usage_logs.estimated_cost_usd), 0) as cost')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc(DB::raw('SUM(ai_usage_logs.estimated_cost_usd)'))
            ->limit(10)
            ->get();
    }

    /**
     * Most recent failures (any non-success status).
     *
     * @return \Illuminate\Support\Collection<int, AIUsageLog>
     */
    #[Computed]
    public function recentErrors(): \Illuminate\Support\Collection
    {
        return $this->baseQuery()
            ->where('status', '!=', AIUsageLog::STATUS_SUCCESS)
            ->with('user:id,name')
            ->latest()
            ->limit(15)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    #[Computed]
    public function providers(): \Illuminate\Support\Collection
    {
        return AIUsageLog::query()->distinct()->orderBy('provider')->pluck('provider');
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    #[Computed]
    public function features(): \Illuminate\Support\Collection
    {
        return AIUsageLog::query()->whereNotNull('feature_key')->distinct()->orderBy('feature_key')->pluck('feature_key');
    }

    public function render(): View
    {
        return view('livewire.admin.ai.usage-reports');
    }
}
