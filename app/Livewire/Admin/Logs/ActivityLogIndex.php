<?php

namespace App\Livewire\Admin\Logs;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
#[Title('Activity Logs')]
class ActivityLogIndex extends Component
{
    use WithPagination;

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    #[Url(as: 'user', except: '')]
    public string $userId = '';

    #[Url(as: 'model', except: '')]
    public string $model = '';

    #[Url(as: 'event', except: '')]
    public string $event = '';

    #[Url(as: 'channel', except: '')]
    public string $logName = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function clearFilters(): void
    {
        $this->reset(['from', 'to', 'userId', 'model', 'event', 'logName', 'search']);
        $this->resetPage();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    /** @return array<int, string> */
    public function modelChoices(): array
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->all();
    }

    /** @return array<int, string> */
    public function channelChoices(): array
    {
        return array_values(config('activitylog.log_names', ['default']));
    }

    public function render(): View
    {
        $logs = Activity::query()
            ->with(['causer', 'subject'])
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->when($this->userId !== '', fn ($q) => $q->where('causer_id', (int) $this->userId)
                ->where('causer_type', User::class))
            ->when($this->model !== '', fn ($q) => $q->where('subject_type', $this->model))
            ->when($this->event !== '', fn ($q) => $q->where('event', $this->event))
            ->when($this->logName !== '', fn ($q) => $q->where('log_name', $this->logName))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($qq): void {
                    $qq->where('description', 'like', '%'.$this->search.'%')
                        ->orWhere('properties', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('id')
            ->paginate(20);

        return view('admin.logs.activity-index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'models' => $this->modelChoices(),
            'events' => ['created', 'updated', 'deleted'],
            'channels' => $this->channelChoices(),
        ]);
    }
}
