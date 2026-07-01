<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Actions\Page\ArchivePageAction;
use App\Actions\Page\DeletePageAction;
use App\Actions\Page\PublishPageAction;
use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Pages')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'template')]
    public string $templateFilter = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('pages.view') ?? false,
            403,
            'You do not have access to manage pages.',
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

    public function updatingTemplateFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'templateFilter']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<Page>
     */
    #[Computed]
    public function pages(): LengthAwarePaginator
    {
        $query = Page::query()
            ->with(['translations.language', 'createdBy:id,name'])
            ->ordered()
            ->orderByDesc('id');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->whereHas('translations', fn ($q) => $q->where('title', 'like', $term)->orWhere('slug', 'like', $term));
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->templateFilter !== '') {
            $query->where('template', $this->templateFilter);
        }

        return $query->paginate(perPage: 25);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return array_map(
            static fn (PageStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
            PageStatus::cases(),
        );
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function templateOptions(): array
    {
        return Page::TEMPLATES;
    }

    // -- Quick actions per row -------------------------------------------------

    public function publish(int $pageId, PublishPageAction $publish): void
    {
        $this->authorize('pages.publish');

        $page = Page::query()->findOrFail($pageId);

        try {
            $publish->handle($page);
            $this->dispatchSuccessToast('Page published.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Publish failed: '.$exception->getMessage());
        }
    }

    public function archive(int $pageId, ArchivePageAction $archive): void
    {
        $this->authorize('pages.publish');

        $page = Page::query()->findOrFail($pageId);

        try {
            $archive->handle($page);
            $this->dispatchSuccessToast('Page archived.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Archive failed: '.$exception->getMessage());
        }
    }

    public function deletePage(int $pageId, DeletePageAction $delete): void
    {
        $this->authorize('pages.delete');

        $page = Page::query()->findOrFail($pageId);

        try {
            $delete->handle($page);
            $this->dispatchSuccessToast('Page deleted.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Delete failed: '.$exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.admin.pages.index');
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
