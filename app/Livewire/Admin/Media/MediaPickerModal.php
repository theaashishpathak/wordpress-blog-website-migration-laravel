<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Media;

use App\Actions\Media\UploadMediaAction;
use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

/**
 * Reusable media-library picker modal.
 *
 * Lifecycle:
 *   1. Parent component dispatches `media-picker.open` with a payload
 *      `{ target: 'featured_image' }` — `target` is an arbitrary string
 *      the parent uses to route the selection event when several
 *      pickers might share the same page.
 *   2. User searches / uploads / clicks a tile.
 *   3. Modal dispatches `media.selected` with `{ target, mediaId }` and
 *      closes.
 *
 * The parent listens with #[On('media.selected')] and filters by
 * `target` so it only reacts to its own picker invocation.
 *
 * The modal NEVER instantiates Storage directly — every upload flows
 * through UploadMediaAction so the settings-driven size + mime caps
 * stay enforced.
 */
class MediaPickerModal extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $open = false;

    /**
     * Arbitrary tag set by the opener so the parent can ignore
     * selection events from other pickers on the same page.
     */
    public string $target = '';

    /**
     * Mime-type filter — '' = all images, restrictive callers can
     * pass 'image/*' or 'video/*'. We default to images since the
     * primary use case is post featured images.
     */
    public string $mimeFilter = 'image/';

    public string $search = '';

    /**
     * @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null
     */
    public $uploadFile = null;

    public string $uploadAltText = '';

    public bool $isUploading = false;

    #[On('media-picker.open')]
    public function openPicker(array $payload = []): void
    {
        $this->target = (string) ($payload['target'] ?? '');
        $this->mimeFilter = (string) ($payload['mime'] ?? 'image/');
        $this->search = '';
        $this->resetPage();
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->target = '';
        $this->search = '';
        $this->uploadFile = null;
        $this->uploadAltText = '';
        $this->resetValidation();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function select(int $mediaId): void
    {
        $media = Media::query()->find($mediaId);

        if ($media === null) {
            $this->dispatch('toast.danger', message: 'Media not found.');

            return;
        }

        $this->dispatch('media.selected', payload: [
            'target' => $this->target,
            'mediaId' => $media->id,
            'url' => $media->url(),
            'altText' => (string) ($media->alt_text ?? ''),
        ]);

        $this->close();
    }

    public function uploadAndSelect(UploadMediaAction $uploadMedia): void
    {
        if ($this->uploadFile === null) {
            $this->addError('uploadFile', 'Choose a file to upload first.');

            return;
        }

        $this->isUploading = true;

        try {
            $media = $uploadMedia->handle(
                file: $this->uploadFile,
                meta: ['alt_text' => $this->uploadAltText !== '' ? $this->uploadAltText : null],
                uploaderId: (int) auth()->id(),
            );

            $this->dispatch('media.selected', payload: [
                'target' => $this->target,
                'mediaId' => $media->id,
                'url' => $media->url(),
                'altText' => (string) ($media->alt_text ?? ''),
            ]);

            $this->dispatch('toast.success', message: 'Uploaded and selected.');
            $this->close();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->errors());
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('toast.danger', message: 'Upload failed: '.$exception->getMessage());
        } finally {
            $this->isUploading = false;
        }
    }

    /**
     * @return LengthAwarePaginator<Media>
     */
    #[Computed]
    public function results(): LengthAwarePaginator
    {
        $query = Media::query()
            ->orderByDesc('id');

        if ($this->mimeFilter !== '') {
            $query->where('mime_type', 'like', $this->mimeFilter.'%');
        }

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('original_filename', 'like', $term)
                    ->orWhere('filename', 'like', $term)
                    ->orWhere('alt_text', 'like', $term)
                    ->orWhere('caption', 'like', $term);
            });
        }

        return $query->paginate(perPage: 24);
    }

    public function render(): View
    {
        return view('livewire.admin.media.media-picker-modal');
    }
}
