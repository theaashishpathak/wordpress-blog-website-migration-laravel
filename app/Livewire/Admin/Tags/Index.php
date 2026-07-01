<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tags;

use App\Actions\Tag\CreateTagAction;
use App\Actions\Tag\DeleteTagAction;
use App\Actions\Tag\MergeTagsAction;
use App\Actions\Tag\UpdateTagAction;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Tags')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    // -- Modal form state ------------------------------------------------------

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $color = '#6366f1';

    public string $type = Tag::TYPE_GENERAL;

    public string $status = Tag::STATUS_PUBLISHED;

    /**
     * Status options that mirror the constants the Tag model exposes.
     * The model only defines published / unpublished; expand here if
     * the model adds more (e.g. draft, archived).
     *
     * @var list<string>
     */
    private const STATUS_VALUES = [Tag::STATUS_PUBLISHED, Tag::STATUS_UNPUBLISHED];

    private const TYPE_VALUES = [Tag::TYPE_GENERAL];

    /**
     * Translation rows keyed by language_id.
     *
     * @var array<int, array{name: string, slug: string, description: string}>
     */
    public array $rows = [];

    // -- Merge modal -----------------------------------------------------------

    public bool $showMerge = false;

    public ?int $mergeTargetId = null;

    /**
     * @var list<int>
     */
    public array $mergeSourceIds = [];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('tags.view') ?? false,
            403,
            'You do not have access to manage tags.',
        );
    }

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

    // -- List + meta -----------------------------------------------------------

    /**
     * @return LengthAwarePaginator<Tag>
     */
    #[Computed]
    public function tags(): LengthAwarePaginator
    {
        $query = Tag::query()
            ->with('translations')
            ->withCount('posts')
            ->orderByDesc('id');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhereHas('translations', fn ($t) => $t->where('name', 'like', $term));
            });
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return $query->paginate(perPage: 25);
    }

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return self::TYPE_VALUES;
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return self::STATUS_VALUES;
    }

    // -- Create / edit modal ---------------------------------------------------

    public function newTag(): void
    {
        $this->authorize('tags.create');
        $this->resetForm();

        // Pre-seed an empty row per active language.
        foreach ($this->languages as $lang) {
            $this->rows[$lang->id] = ['name' => '', 'slug' => '', 'description' => ''];
        }

        $this->showForm = true;
    }

    public function editTag(int $tagId): void
    {
        $this->authorize('tags.edit');
        $this->resetForm();

        $tag = Tag::query()->with('translations')->findOrFail($tagId);

        $this->editingId = $tag->id;
        $this->code = (string) $tag->code;
        $this->color = (string) ($tag->color ?? '#6366f1');
        $this->type = (string) ($tag->type ?? Tag::TYPE_GENERAL);
        $this->status = (string) ($tag->status ?? Tag::STATUS_PUBLISHED);

        foreach ($this->languages as $lang) {
            $existing = $tag->translations->firstWhere('language_id', $lang->id);
            $this->rows[$lang->id] = [
                'name' => (string) ($existing?->name ?? ''),
                'slug' => (string) ($existing?->slug ?? ''),
                'description' => (string) ($existing?->description ?? ''),
            ];
        }

        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(CreateTagAction $create, UpdateTagAction $update): void
    {
        $this->validate($this->rules());

        $translations = $this->buildTranslationsPayload();

        if ($translations === []) {
            $this->dispatchDangerToast('Provide a name in at least one language.');

            return;
        }

        try {
            $payload = [
                'code' => $this->code !== '' ? $this->code : null,
                'color' => $this->color,
                'type' => $this->type,
                'status' => $this->status,
                'translations' => $translations,
                'created_by' => (int) auth()->id(),
                'updated_by' => (int) auth()->id(),
            ];

            if ($this->editingId === null) {
                $this->authorize('tags.create');
                $tag = $create->handle($payload);
                $this->dispatchSuccessToast("Tag '{$tag->name}' created.");
            } else {
                $this->authorize('tags.edit');
                $tag = Tag::query()->findOrFail($this->editingId);
                $update->handle($tag, $payload);
                $this->dispatchSuccessToast("Tag '{$tag->name}' updated.");
            }

            $this->cancelForm();
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast($exception->getMessage());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTranslationsPayload(): array
    {
        $rows = [];

        foreach ($this->rows as $languageId => $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $rows[] = [
                'language_id' => (int) $languageId,
                'name' => trim((string) $row['name']),
                'slug' => trim((string) ($row['slug'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')) !== '' ? $row['description'] : null,
            ];
        }

        return $rows;
    }

    public function deleteTag(int $tagId, DeleteTagAction $delete): void
    {
        $this->authorize('tags.delete');

        $tag = Tag::query()->findOrFail($tagId);

        try {
            $delete->handle($tag);
            $this->dispatchSuccessToast('Tag deleted.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast($exception->getMessage());
        }
    }

    // -- Merge modal -----------------------------------------------------------

    public function openMerge(int $targetTagId): void
    {
        $this->authorize('tags.merge');

        $this->mergeTargetId = $targetTagId;
        $this->mergeSourceIds = [];
        $this->showMerge = true;
    }

    public function cancelMerge(): void
    {
        $this->showMerge = false;
        $this->mergeTargetId = null;
        $this->mergeSourceIds = [];
    }

    public function performMerge(MergeTagsAction $merge): void
    {
        $this->authorize('tags.merge');

        if ($this->mergeTargetId === null || $this->mergeSourceIds === []) {
            $this->dispatchDangerToast('Pick at least one source tag to merge.');

            return;
        }

        try {
            $target = Tag::query()->findOrFail($this->mergeTargetId);
            $merge->handle($target, $this->mergeSourceIds);
            $this->dispatchSuccessToast('Tags merged successfully.');
            $this->cancelMerge();
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Merge failed: '.$exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'color' => ['nullable', 'string', 'max:20'],
            'type' => ['required', \Illuminate\Validation\Rule::in(self::TYPE_VALUES)],
            'status' => ['required', \Illuminate\Validation\Rule::in(self::STATUS_VALUES)],
        ];
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->color = '#6366f1';
        $this->type = Tag::TYPE_GENERAL;
        $this->status = Tag::STATUS_PUBLISHED;
        $this->rows = [];
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.tags.index');
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
