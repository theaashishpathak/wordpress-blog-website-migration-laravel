<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Settings;

use App\Models\LoginLog;
use App\Models\ProfileActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Visitor "My Activity" — unified security timeline merging profile
 * activity events and login_logs for the authenticated user. Read-only;
 * for the actual settings UI (Sessions, Security) the user opens the
 * dedicated pages from the sidebar.
 *
 * Filter chips switch between "all", "logins" (login_logs), "account"
 * (profile_activity_logs). Pagination is window-based — load the latest
 * 50 events and offer a "Show older" link that bumps the window.
 */
#[Layout('layouts.visitor')]
#[Title('My Activity')]
class ActivityIndex extends Component
{
    #[Url(as: 'filter', except: 'all')]
    public string $filter = 'all';

    public int $limit = 50;

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'logins', 'account'], true) ? $filter : 'all';
    }

    public function showMore(): void
    {
        $this->limit += 50;
    }

    /**
     * Merged, sorted timeline of profile activity + login_logs.
     * Each entry shares the same shape so the view can iterate one list.
     *
     * @return Collection<int, object>
     */
    #[Computed]
    public function events(): Collection
    {
        $userId = auth()->id();
        if ($userId === null) {
            return collect();
        }

        $events = collect();

        if (in_array($this->filter, ['all', 'account'], true)) {
            ProfileActivityLog::query()
                ->where('user_id', $userId)
                ->latest()
                ->limit($this->limit)
                ->get()
                ->each(function (ProfileActivityLog $row) use ($events): void {
                    $events->push((object) [
                        'kind' => 'account',
                        'event' => $row->event,
                        'description' => $row->description,
                        'meta' => $row->meta,
                        'when' => $row->created_at,
                        'ip' => null,
                        'browser' => null,
                        'country' => null,
                        'status' => null,
                    ]);
                });
        }

        if (in_array($this->filter, ['all', 'logins'], true)) {
            LoginLog::query()
                ->where('user_id', $userId)
                ->latest('login_at')
                ->limit($this->limit)
                ->get()
                ->each(function (LoginLog $row) use ($events): void {
                    $label = match ($row->status) {
                        LoginLog::STATUS_FAILED => 'Failed sign-in attempt',
                        LoginLog::STATUS_LOGOUT => 'Signed out',
                        default                 => 'Signed in',
                    };
                    $events->push((object) [
                        'kind' => 'login',
                        'event' => 'login_'.$row->status,
                        'description' => $label,
                        'meta' => null,
                        'when' => $row->login_at,
                        'ip' => $row->ip_address,
                        'browser' => $row->browser,
                        'country' => $row->country,
                        'status' => $row->status,
                    ]);
                });
        }

        return $events
            ->filter(fn ($e) => $e->when !== null)
            ->sortByDesc(fn ($e) => $e->when->getTimestamp())
            ->values();
    }

    public function render(): View
    {
        return view('livewire.visitor.settings.activity-index');
    }
}
