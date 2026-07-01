<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Media;

use App\Actions\Media\DeleteMediaAction;
use App\Actions\Media\UploadMediaAction;
use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

/**
 * Standalone media library browser.
 *
 * Distinct from MediaPickerModal (which is the embedded picker used
 * inside post forms). This page is the full-featured CRUD: bulk
 * upload, bulk delete, inline alt-text editing, mime-type tab filter.
 */
#[Layout('layouts.app')]
#[Title('Media Library')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'tab')]
    public string $tab = 'images';     // images | videos | documents | all

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Multiple uploaded files staged for upload.
     *
     * Not strictly typed `array` because Livewire's WithFileUploads can
     * dehydrate a temporary-uploaded-file array to null between snapshots,
     * which then trips a TypeError on rehydration into a typed array
     * property. Keeping it untyped + defaulting to `[]` keeps the
     * Livewire flow happy; consumers below treat null as "no files".
     *
     * @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>|null
     */
    public $uploadFiles = [];

    /**
     * @var list<int>
     */
    public array $selectedIds = [];

    /**
     * The media row currently shown in the detail/edit drawer.
     */
    public ?int $editingId = null;

    public string $editAltText = '';

    public string $editCaption = '';

    public string $editCredit = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('media.view') ?? false,
            403,
            'You do not have access to the media library.',
        );
    }

    public function updatedTab(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['images', 'videos', 'documents', 'all'], true)) {
            return;
        }
        $this->tab = $tab;
        $this->resetPage();
        $this->selectedIds = [];
    }

    /**
     * @return LengthAwarePaginator<Media>
     */
    #[Computed]
    public function media(): LengthAwarePaginator
    {
        $query = Media::query()->orderByDesc('id');

        $query = match ($this->tab) {
            'images' => $query->where('mime_type', 'like', 'image/%'),
            'videos' => $query->where('mime_type', 'like', 'video/%'),
            'documents' => $query->where(function ($q): void {
                $q->where('mime_type', 'like', 'application/%')
                    ->orWhere('mime_type', 'like', 'text/%');
            }),
            default => $query,
        };

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('original_filename', 'like', $term)
                    ->orWhere('filename', 'like', $term)
                    ->orWhere('alt_text', 'like', $term)
                    ->orWhere('caption', 'like', $term);
            });
        }

        return $query->paginate(perPage: 36);
    }

    #[Computed]
    public function totalCount(): int
    {
        return Media::query()->count();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        return [
            'all' => Media::query()->count(),
            'images' => Media::query()->images()->count(),
            'videos' => Media::query()->videos()->count(),
            'documents' => Media::query()->documents()->count(),
        ];
    }

    // -- Upload ---------------------------------------------------------------

    public function uploadAll(UploadMediaAction $upload): void
    {
        $this->authorize('media.upload');

        if (empty($this->uploadFiles)) {
            $this->uploadFiles = [];

            return;
        }

        $count = 0;
        $failures = [];

        $files = is_array($this->uploadFiles) ? $this->uploadFiles : [];

        foreach ($files as $file) {
            try {
                $upload->handle($file, [], (int) auth()->id());
                $count++;
            } catch (Throwable $exception) {
                $failures[] = $file->getClientOriginalName().': '.$exception->getMessage();
            }
        }

        // Reset to an explicit empty array (NOT null) so Livewire snapshot
        // round-trips report `[]`, which tests assertSet('uploadFiles', [])
        // depend on.
        $this->uploadFiles = [];

        if ($count > 0) {
            $this->dispatchSuccessToast("Uploaded {$count} file(s).");
        }

        if ($failures !== []) {
            $this->dispatchDangerToast('Some uploads failed: '.implode(' | ', $failures));
        }
    }

    // -- Selection -----------------------------------------------------------

    public function toggleSelect(int $mediaId): void
    {
        $index = array_search($mediaId, $this->selectedIds, true);
        if ($index === false) {
            $this->selectedIds[] = $mediaId;
        } else {
            unset($this->selectedIds[$index]);
            $this->selectedIds = array_values($this->selectedIds);
        }
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function bulkDelete(DeleteMediaAction $delete): void
    {
        $this->authorize('media.delete');

        if ($this->selectedIds === []) {
            return;
        }

        $count = 0;
        $failures = [];

        foreach ($this->selectedIds as $id) {
            $media = Media::query()->find($id);
            if ($media === null) {
                continue;
            }

            try {
                $delete->handle($media);
                $count++;
            } catch (Throwable $exception) {
                $failures[] = (string) $media->id;
            }
        }

        $this->selectedIds = [];

        $this->dispatchSuccessToast("Deleted {$count} file(s).");

        if ($failures !== []) {
            $this->dispatchDangerToast('Some deletions failed for ids: '.implode(', ', $failures));
        }
    }

    // -- Detail drawer (edit alt/caption/credit) ------------------------------

    public function editMedia(int $mediaId): void
    {
        $this->authorize('media.edit');

        $media = Media::query()->findOrFail($mediaId);

        $this->editingId = $media->id;
        $this->editAltText = (string) ($media->alt_text ?? '');
        $this->editCaption = (string) ($media->caption ?? '');
        $this->editCredit = (string) ($media->credit ?? '');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editAltText = '';
        $this->editCaption = '';
        $this->editCredit = '';
    }

    public function saveMeta(): void
    {
        $this->authorize('media.edit');

        if ($this->editingId === null) {
            return;
        }

        $media = Media::query()->findOrFail($this->editingId);

        $media->fill([
            'alt_text' => trim($this->editAltText) !== '' ? trim($this->editAltText) : null,
            'caption' => trim($this->editCaption) !== '' ? trim($this->editCaption) : null,
            'credit' => trim($this->editCredit) !== '' ? trim($this->editCredit) : null,
        ])->save();

        $this->dispatchSuccessToast('Metadata saved.');
        $this->cancelEdit();
    }

    public function deleteOne(int $mediaId, DeleteMediaAction $delete): void
    {
        $this->authorize('media.delete');

        $media = Media::query()->findOrFail($mediaId);

        try {
            $delete->handle($media);
            $this->dispatchSuccessToast('Media deleted.');
            $this->cancelEdit();
        } catch (Throwable $exception) {
            $this->dispatchDangerToast('Delete failed: '.$exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.admin.media.index');
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
