<?php

namespace App\Livewire\Settings\Tags;

use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('layouts.app')]
#[Title('Manage Tags')]
class TagIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public ?int $editingTagId = null;

    public bool $showFormModal = false;

    public int $formKey = 0;

    /**
     * @var array<string, string>|null
     */
    public ?array $viewingTag = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->editingTagId = null;
        $this->showFormModal = true;
        $this->formKey++;
    }

    public function edit(int $id): void
    {
        $this->editingTagId = $id;
        $this->showFormModal = true;
        $this->formKey++;
    }

    public function delete(int $id): void
    {
        try {
            $deletedRows = Tag::query()->whereKey($id)->delete();

            if ($deletedRows === 0) {
                $message = 'Failed to delete tag. Tag not found.';
                session()->flash('danger', $message);
                $this->dispatch('toast', message: $message, type: 'danger');

                return;
            }

            $message = 'Tag deleted successfully.';
            session()->flash('success', $message);
            $this->dispatch('toast', message: $message, type: 'success');
            $this->resetPage();
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Failed to delete tag.';
            session()->flash('danger', $message);
            $this->dispatch('toast', message: $message, type: 'danger');
        }
    }

    #[On('confirm-tag-delete')]
    public function confirmDelete(int $id): void
    {
        $this->delete($id);
    }

    public function requestDelete(int $id): void
    {
        $this->dispatch('confirm-tag-delete-alert', id: $id);
    }

    public function view(int $id): void
    {
        $tag = Tag::query()
            ->with(['createdBy', 'updatedBy'])
            ->findOrFail($id);

        $this->viewingTag = [
            'code' => $tag->code,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'color' => $tag->color,
            'created_by' => $tag->createdBy?->name ?? '-',
            'created_at' => $tag->created_at?->format('M d, Y h:i A') ?? '-',
            'updated_by' => $tag->updatedBy?->name ?? '-',
            'updated_at' => $tag->updated_at?->format('M d, Y h:i A') ?? '-',
            'status' => $tag->status,
            'type' => $tag->type,
        ];
    }

    public function closeViewModal(): void
    {
        $this->viewingTag = null;
    }

    public function exportCsv(): StreamedResponse
    {
        $tags = $this->tagQuery()
            ->with('createdBy')
            ->latest()
            ->get();

        $fileName = 'tags-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($tags): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Code', 'Name', 'Slug', 'Type', 'Status', 'Created By', 'Updated At']);

            foreach ($tags as $tag) {
                fputcsv($handle, [
                    $tag->code,
                    $tag->name,
                    $tag->slug,
                    $tag->type,
                    $tag->status,
                    $tag->createdBy?->name ?? '-',
                    $tag->updated_at?->format('Y-m-d H:i:s') ?? '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    #[On('tag-saved')]
    #[On('tag-form-cancelled')]
    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingTagId = null;
        $this->formKey++;
        $this->resetPage();
    }

    public function render(): View
    {
        $tags = $this->tagQuery()
            ->with('createdBy')
            ->latest()
            ->paginate(10);

        return view('livewire.settings.tags.tag-index', [
            'tags' => $tags,
            'types' => [
                Tag::TYPE_GENERAL,
            ],
            'statuses' => [
                Tag::STATUS_PUBLISHED,
                Tag::STATUS_UNPUBLISHED,
            ],
        ]);
    }

    protected function tagQuery(): Builder
    {
        return Tag::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery
                        ->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->typeFilter !== '', function ($query): void {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('status', $this->statusFilter);
            });
    }
}
