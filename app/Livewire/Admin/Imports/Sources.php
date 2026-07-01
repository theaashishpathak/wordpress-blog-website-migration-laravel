<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Imports;

use App\Actions\Import\ImportFeedAction;
use App\Models\Category;
use App\Models\ImportSource;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('RSS Sources')]
class Sources extends Component
{
    use WithPagination;

    // -- Modal form -----------------------------------------------------------

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $feedUrl = '';

    public ?int $categoryId = null;

    public ?int $defaultLanguageId = null;

    public string $defaultPostType = 'news';

    public string $status = ImportSource::STATUS_ACTIVE;

    public bool $autoPublish = false;

    public int $fetchIntervalMinutes = 60;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('rss.view') ?? false,
            403,
            'You do not have access to manage RSS sources.',
        );

        $this->defaultLanguageId = Language::query()->default()->value('id');
    }

    /**
     * @return LengthAwarePaginator<ImportSource>
     */
    #[Computed]
    public function sources(): LengthAwarePaginator
    {
        return ImportSource::query()
            ->with(['category.translations', 'defaultLanguage:id,code,name,flag_emoji'])
            ->orderByDesc('id')
            ->paginate(perPage: 20);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryOptions(): Collection
    {
        return Category::query()->ordered()->limit(200)->get();
    }

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languageOptions(): Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public function newSource(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editSource(int $id): void
    {
        $s = ImportSource::query()->findOrFail($id);

        $this->editingId = $s->id;
        $this->name = (string) $s->name;
        $this->feedUrl = (string) $s->feed_url;
        $this->categoryId = $s->category_id;
        $this->defaultLanguageId = $s->default_language_id;
        $this->defaultPostType = (string) ($s->default_post_type ?? 'news');
        $this->status = (string) $s->status;
        $this->autoPublish = (bool) $s->auto_publish;
        $this->fetchIntervalMinutes = (int) $s->fetch_interval_minutes;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:200'],
            'feedUrl' => ['required', 'url', 'max:1000'],
            'defaultLanguageId' => ['required', 'integer', 'exists:languages,id'],
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'defaultPostType' => ['required', 'in:post,news'],
            'status' => ['required', \Illuminate\Validation\Rule::in(ImportSource::STATUSES)],
            'fetchIntervalMinutes' => ['integer', 'min:5', 'max:1440'],
        ]);

        $payload = [
            'name' => trim($this->name),
            'feed_url' => trim($this->feedUrl),
            'category_id' => $this->categoryId,
            'default_language_id' => $this->defaultLanguageId,
            'default_post_type' => $this->defaultPostType,
            'status' => $this->status,
            'auto_publish' => $this->autoPublish,
            'fetch_interval_minutes' => $this->fetchIntervalMinutes,
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId === null) {
            $payload['created_by'] = auth()->id();
            ImportSource::query()->create($payload);
            $this->dispatchSuccessToast('RSS source created.');
        } else {
            $source = ImportSource::query()->findOrFail($this->editingId);
            $source->fill($payload)->save();
            $this->dispatchSuccessToast('RSS source updated.');
        }

        $this->cancelForm();
    }

    public function deleteSource(int $id): void
    {
        ImportSource::query()->findOrFail($id)->delete();
        $this->dispatchSuccessToast('Source deleted.');
    }

    public function togglePause(int $id): void
    {
        $s = ImportSource::query()->findOrFail($id);
        $s->forceFill([
            'status' => $s->status === ImportSource::STATUS_ACTIVE
                ? ImportSource::STATUS_PAUSED
                : ImportSource::STATUS_ACTIVE,
        ])->save();
        $this->dispatchSuccessToast('Status toggled.');
    }

    /**
     * Synchronous "Fetch now" — bypasses fetch_interval gating.
     */
    public function fetchNow(int $id, ImportFeedAction $importer): void
    {
        $source = ImportSource::query()->findOrFail($id);

        try {
            $result = $importer->handle($source);
            $this->dispatchSuccessToast(
                "Fetched {$result['fetched']} item(s). Created {$result['created']}, skipped {$result['skipped']}."
            );
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast('Fetch failed: '.$e->getMessage());
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->feedUrl = '';
        $this->categoryId = null;
        $this->defaultLanguageId = Language::query()->default()->value('id');
        $this->defaultPostType = 'news';
        $this->status = ImportSource::STATUS_ACTIVE;
        $this->autoPublish = false;
        $this->fetchIntervalMinutes = 60;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.imports.sources');
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
