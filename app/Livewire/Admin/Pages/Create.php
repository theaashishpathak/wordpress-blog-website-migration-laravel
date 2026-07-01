<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Actions\Page\CreatePageAction;
use App\Enums\PageStatus;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Create Page')]
class Create extends Component
{
    public string $status = 'draft';

    public string $template = Page::TEMPLATE_DEFAULT;

    public bool $showInMenu = false;

    public int $sortOrder = 0;

    /** Active editing tab. */
    public ?int $activeLanguageId = null;

    public ?int $defaultLanguageId = null;

    /**
     * Per-language translation state, keyed by language_id.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $translations = [];

    /** Scalar editors bound to the active tab. */
    public string $title = '';

    public string $slug = '';

    public string $content = '';

    public string $metaTitle = '';

    public string $metaDescription = '';

    public bool $isPublished = false;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('pages.create') ?? false,
            403,
            'You do not have permission to create pages.',
        );

        $default = Language::query()->default()->first()
            ?? Language::query()->active()->ordered()->first();

        if ($default !== null) {
            $this->defaultLanguageId = (int) $default->id;
            $this->activeLanguageId = (int) $default->id;
            $this->translations[(int) $default->id] = $this->blankRow();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function blankRow(): array
    {
        return [
            'title' => '',
            'slug' => '',
            'content' => '',
            'meta_title' => '',
            'meta_description' => '',
            'is_published' => false,
        ];
    }

    // -- Translation tabs ------------------------------------------------------

    public function switchLanguage(int $languageId): void
    {
        $this->flushScalarsIntoActive();

        if (! isset($this->translations[$languageId])) {
            $this->translations[$languageId] = $this->blankRow();
        }

        $this->activeLanguageId = $languageId;
        $this->loadActiveIntoScalars();
        $this->broadcastContentRefresh();
    }

    public function addTranslation(int $languageId): void
    {
        if (isset($this->translations[$languageId])) {
            $this->switchLanguage($languageId);

            return;
        }

        $this->flushScalarsIntoActive();
        $this->translations[$languageId] = $this->blankRow();
        $this->activeLanguageId = $languageId;
        $this->loadActiveIntoScalars();
        $this->broadcastContentRefresh();
    }

    public function removeTranslation(int $languageId): void
    {
        if ($languageId === $this->defaultLanguageId) {
            $this->dispatchDangerToast('Cannot remove the default language.');

            return;
        }

        unset($this->translations[$languageId]);

        if ($this->activeLanguageId === $languageId) {
            $this->activeLanguageId = $this->defaultLanguageId;
            $this->loadActiveIntoScalars();
            $this->broadcastContentRefresh();
        }
    }

    private function loadActiveIntoScalars(): void
    {
        $row = $this->translations[$this->activeLanguageId] ?? $this->blankRow();

        $this->title = (string) ($row['title'] ?? '');
        $this->slug = (string) ($row['slug'] ?? '');
        $this->content = (string) ($row['content'] ?? '');
        $this->metaTitle = (string) ($row['meta_title'] ?? '');
        $this->metaDescription = (string) ($row['meta_description'] ?? '');
        $this->isPublished = (bool) ($row['is_published'] ?? false);
    }

    private function flushScalarsIntoActive(): void
    {
        if ($this->activeLanguageId === null) {
            return;
        }

        $this->translations[$this->activeLanguageId] = [
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'is_published' => $this->isPublished,
        ];
    }

    private function broadcastContentRefresh(): void
    {
        $this->dispatch('page-content-refreshed', content: $this->content);
    }

    // -- Save ------------------------------------------------------------------

    public function save(CreatePageAction $create): void
    {
        $this->authorize('pages.create');

        $this->flushScalarsIntoActive();
        $this->validate($this->rules());

        try {
            $page = $create->handle($this->buildPayload());

            $this->dispatchSuccessToast('Page created.');
            $this->redirect(route('admin.pages.edit', $page), navigate: true);
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Create failed: '.$exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        return [
            'status' => $this->status,
            'template' => $this->template,
            'show_in_menu' => $this->showInMenu,
            'sort_order' => $this->sortOrder,
            'created_by' => (int) auth()->id(),
            'updated_by' => (int) auth()->id(),
            'translations' => $this->buildTranslationRows(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTranslationRows(): array
    {
        $rows = [];

        foreach ($this->translations as $languageId => $row) {
            $title = trim((string) ($row['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $rows[] = [
                'language_id' => $languageId,
                'title' => $title,
                'slug' => trim((string) ($row['slug'] ?? '')) !== ''
                    ? Str::slug((string) $row['slug'])
                    : Str::slug($title),
                'content' => $row['content'] ?? null,
                'meta_title' => ((string) ($row['meta_title'] ?? '')) !== '' ? $row['meta_title'] : null,
                'meta_description' => ((string) ($row['meta_description'] ?? '')) !== '' ? $row['meta_description'] : null,
                'is_published' => (bool) ($row['is_published'] ?? false),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'status' => ['required', \Illuminate\Validation\Rule::in(PageStatus::values())],
            'template' => ['required', \Illuminate\Validation\Rule::in(Page::TEMPLATES)],
            'sortOrder' => ['integer', 'min:0'],
            'defaultLanguageId' => ['required', 'integer', 'exists:languages,id'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    // -- Computed --------------------------------------------------------------

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languagesAvailableToAdd(): Collection
    {
        $present = array_keys($this->translations);

        return $this->languages
            ->reject(fn (Language $l): bool => in_array((int) $l->id, $present, true))
            ->values();
    }

    /**
     * @return list<array{id:int, code:string, name:string, flag:?string, active:bool, percent:int, is_default:bool}>
     */
    #[Computed]
    public function translationTabs(): array
    {
        $byId = $this->languages->keyBy('id');
        $out = [];

        foreach ($this->translations as $languageId => $row) {
            $lang = $byId->get($languageId);
            if (! $lang) {
                continue;
            }

            $isActive = $languageId === $this->activeLanguageId;

            $data = $isActive ? array_merge($row, [
                'title' => $this->title,
                'content' => $this->content,
                'meta_title' => $this->metaTitle,
            ]) : $row;

            $out[] = [
                'id' => (int) $languageId,
                'code' => $lang->code,
                'name' => $lang->name,
                'flag' => $lang->flag_emoji ?? null,
                'active' => $isActive,
                'percent' => $this->completionPercent($data),
                'is_default' => $languageId === $this->defaultLanguageId,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function completionPercent(array $row): int
    {
        $weights = ['title' => 30, 'content' => 50, 'meta_title' => 10, 'meta_description' => 10];
        $total = 0;

        foreach ($weights as $key => $weight) {
            if (trim((string) ($row[$key] ?? '')) !== '') {
                $total += $weight;
            }
        }

        return $total;
    }

    public function render(): View
    {
        return view('livewire.admin.pages.create');
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
