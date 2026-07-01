<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Newsletter Subscribers')]
class Subscribers extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'source')]
    public string $sourceFilter = '';

    /**
     * @var list<int>
     */
    public array $selectedIds = [];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('newsletter.view') ?? false,
            403,
            'You do not have access to newsletter subscribers.',
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'sourceFilter']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<NewsletterSubscriber>
     */
    #[Computed]
    public function subscribers(): LengthAwarePaginator
    {
        $query = NewsletterSubscriber::query()
            ->with('language:id,code,name,flag_emoji')
            ->orderByDesc('id');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('email', 'like', $term)->orWhere('name', 'like', $term);
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->sourceFilter !== '') {
            $query->where('source', $this->sourceFilter);
        }

        return $query->paginate(perPage: 25);
    }

    /**
     * @return array{total:int, confirmed:int, pending:int, unsubscribed:int}
     */
    #[Computed]
    public function counts(): array
    {
        return [
            'total' => NewsletterSubscriber::query()->count(),
            'confirmed' => NewsletterSubscriber::query()->confirmed()->count(),
            'pending' => NewsletterSubscriber::query()->pending()->count(),
            'unsubscribed' => NewsletterSubscriber::query()->unsubscribed()->count(),
        ];
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function knownSources(): array
    {
        return NewsletterSubscriber::query()
            ->whereNotNull('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source')
            ->all();
    }

    public function bulkDelete(): void
    {
        $this->authorize('newsletter.view');

        if ($this->selectedIds === []) {
            $this->dispatchDangerToast('Select at least one row.');

            return;
        }

        $deleted = NewsletterSubscriber::query()->whereIn('id', $this->selectedIds)->delete();
        $this->selectedIds = [];
        $this->dispatchSuccessToast("{$deleted} subscriber(s) deleted.");
    }

    public function markUnsubscribed(int $id): void
    {
        $this->authorize('newsletter.view');

        $subscriber = NewsletterSubscriber::query()->findOrFail($id);

        if (! $subscriber->isUnsubscribed()) {
            $subscriber->fill([
                'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ])->save();
        }

        $this->dispatchSuccessToast('Subscriber marked as unsubscribed.');
    }

    /**
     * Stream a CSV of the *currently filtered* subscriber list — used
     * by admins to import into MailChimp / SendGrid / etc.
     */
    public function exportCsv(): StreamedResponse
    {
        $this->authorize('newsletter.view');

        $query = NewsletterSubscriber::query()->with('language:id,code')->orderByDesc('id');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('email', 'like', $term)->orWhere('name', 'like', $term);
            });
        }
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }
        if ($this->sourceFilter !== '') {
            $query->where('source', $this->sourceFilter);
        }

        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'name', 'status', 'source', 'locale', 'confirmed_at', 'created_at']);

            $query->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->email,
                        $row->name ?? '',
                        $row->status,
                        $row->source ?? '',
                        $row->language?->code ?? '',
                        $row->confirmed_at?->toIso8601String() ?? '',
                        $row->created_at?->toIso8601String() ?? '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        return view('livewire.admin.newsletter.subscribers');
    }

    protected function dispatchSuccessToast(string $message): void
    {
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        $this->dispatch('toast.danger', message: $message);
    }
}
