<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Categories;

use App\Actions\Category\CreateCategoryAction;
use App\Models\Category;
use App\Models\Language;
use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Categories — Create Livewire component.
 *
 * Mirrors the Edit component's translation-dictionary pattern so the
 * form supports multiple languages on the same screen. The structural
 * fields (parent, icon, color, flags, layout) live alongside a tab list
 * driving which translation row is currently being edited.
 */
#[Layout('layouts.app')]
#[Title('Create Category')]
class Create extends Component
{
    /** Structural fields. */
    public ?int $parentId = null;

    public ?int $imageId = null;

    public string $icon = '';

    public string $color = '';

    public bool $showInMenu = true;

    public bool $showOnHomepage = false;

    public bool $isFeatured = false;

    public int $sortOrder = 0;

    public string $layout = Category::LAYOUT_GRID;

    /** Which language tab is currently being edited. */
    public ?int $activeLanguageId = null;

    public ?int $defaultLanguageId = null;

    /**
     * Per-language translation state, keyed by language_id.
     * Each entry: name, slug, description, meta_title, meta_description.
     *
     * @var array<int, array<string, string>>
     */
    public array $translations = [];

    public function mount(): void
    {
        $this->authorize('create', Category::class);

        $default = Language::query()->default()->first()
            ?? Language::query()->active()->ordered()->first();

        if ($default !== null) {
            $this->defaultLanguageId = (int) $default->id;
            $this->activeLanguageId = (int) $default->id;
            $this->translations[(int) $default->id] = $this->blankTranslation();
        }
    }

    /**
     * @return array<string, string>
     */
    private function blankTranslation(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'description' => '',
            'meta_title' => '',
            'meta_description' => '',
        ];
    }

    public function save(CreateCategoryAction $createCategory): void
    {
        $this->authorize('create', Category::class);

        $payload = $this->buildPayload();

        try {
            $this->validate($this->rules());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatchDangerToast('Please fix the highlighted errors.');

            throw $e;
        }

        try {
            $category = $createCategory->handle($payload);

            $this->dispatchSuccessToast('Category created.');
            $this->dispatch('category-saved', id: $category->id);
            $this->redirect(route('admin.categories.edit', $category), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw so Livewire shows the field-level errors.
            throw $e;
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
            'parent_id' => $this->parentId,
            'image_id' => $this->imageId,
            'icon' => $this->icon !== '' ? $this->icon : null,
            'color' => $this->color !== '' ? $this->color : null,
            'show_in_menu' => $this->showInMenu,
            'show_on_homepage' => $this->showOnHomepage,
            'is_featured' => $this->isFeatured,
            'sort_order' => $this->sortOrder,
            'layout' => $this->layout,
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
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $rows[] = [
                'language_id' => $languageId,
                'name' => $name,
                'slug' => trim((string) ($row['slug'] ?? '')) !== ''
                    ? Str::slug((string) $row['slug'])
                    : Str::slug($name),
                'description' => ((string) ($row['description'] ?? '')) !== '' ? $row['description'] : null,
                'meta_title' => ((string) ($row['meta_title'] ?? '')) !== '' ? $row['meta_title'] : null,
                'meta_description' => ((string) ($row['meta_description'] ?? '')) !== '' ? $row['meta_description'] : null,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $defaultId = $this->defaultLanguageId;
        $rules = [
            'parentId' => ['nullable', 'integer', 'exists:categories,id'],
            'imageId' => ['nullable', 'integer', 'exists:media,id'],
            'icon' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:30'],
            'layout' => ['required', \Illuminate\Validation\Rule::in(Category::LAYOUTS)],
            'sortOrder' => ['integer', 'min:0'],
            'defaultLanguageId' => ['required', 'integer', 'exists:languages,id'],
        ];

        if ($defaultId !== null) {
            $rules["translations.{$defaultId}.name"] = ['required', 'string', 'min:1', 'max:255'];
        }

        return $rules;
    }

    // -------------------------------------------------------------------------
    // Translation tabs
    // -------------------------------------------------------------------------

    public function switchLanguage(int $languageId): void
    {
        if (! isset($this->translations[$languageId])) {
            $this->translations[$languageId] = $this->blankTranslation();
        }

        $this->activeLanguageId = $languageId;
    }

    public function addTranslation(int $languageId): void
    {
        if (isset($this->translations[$languageId])) {
            $this->activeLanguageId = $languageId;

            return;
        }

        $this->translations[$languageId] = $this->blankTranslation();
        $this->activeLanguageId = $languageId;
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
        }
    }

    // -------------------------------------------------------------------------
    // Media picker integration
    // -------------------------------------------------------------------------

    public function openCoverImagePicker(): void
    {
        $this->dispatch('media-picker.open', payload: [
            'target' => 'category_cover',
            'mime' => 'image/',
        ]);
    }

    public function clearCoverImage(): void
    {
        $this->imageId = null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[On('media.selected')]
    public function onMediaSelected(array $payload): void
    {
        if (($payload['target'] ?? null) !== 'category_cover') {
            return;
        }

        $this->imageId = isset($payload['mediaId']) ? (int) $payload['mediaId'] : null;
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

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

        return $this->languages->reject(fn (Language $lang): bool => in_array((int) $lang->id, $present, true))->values();
    }

    /**
     * Top-level + child categories available as a parent option.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function parentOptions(): Collection
    {
        return Category::query()->with('translations')->ordered()->limit(500)->get();
    }

    #[Computed]
    public function coverImage(): ?Media
    {
        return $this->imageId !== null ? Media::query()->find($this->imageId) : null;
    }

    public function render(): View
    {
        return view('livewire.admin.categories.create');
    }

    protected function dispatchSuccessToast(string $message): void
    {
        session()->flash('success', $message);
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        session()->flash('danger', $message);
        $this->dispatch('toast.danger', message: $message);
    }
}
