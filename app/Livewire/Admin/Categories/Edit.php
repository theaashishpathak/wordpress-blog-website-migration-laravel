<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Categories;

use App\Actions\Category\UpdateCategoryAction;
use App\Models\Category;
use App\Models\CategoryTranslation;
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
 * Categories — Edit Livewire component.
 *
 * Loads an existing Category plus every translation row into a
 * language-keyed dictionary. The translation tabs let the editor flip
 * between locales without reloading. Saves go through UpdateCategoryAction
 * which performs upsert/delete per row inside a DB transaction.
 */
#[Layout('layouts.app')]
#[Title('Edit Category')]
class Edit extends Component
{
    public Category $category;

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

    /** Translation tab state. */
    public ?int $activeLanguageId = null;

    public ?int $defaultLanguageId = null;

    /**
     * Per-language translation dictionary. Keyed by language_id.
     * Each entry has the shape:
     *   name, slug, description, meta_title, meta_description,
     *   delete?: bool (set when the user marks the tab for removal).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $translations = [];

    public function mount(Category $category): void
    {
        $this->authorize('update', $category);

        $this->category = $category->load('translations');

        $this->parentId = $category->parent_id;
        $this->imageId = $category->image_id;
        $this->icon = (string) ($category->icon ?? '');
        $this->color = (string) ($category->color ?? '');
        $this->showInMenu = (bool) $category->show_in_menu;
        $this->showOnHomepage = (bool) $category->show_on_homepage;
        $this->isFeatured = (bool) $category->is_featured;
        $this->sortOrder = (int) $category->sort_order;
        $this->layout = (string) $category->layout;

        $default = Language::query()->default()->first();
        $this->defaultLanguageId = $default?->id !== null ? (int) $default->id : null;

        $this->loadTranslationsFromModel();

        $this->activeLanguageId = $this->defaultLanguageId !== null && isset($this->translations[$this->defaultLanguageId])
            ? $this->defaultLanguageId
            : array_key_first($this->translations);
    }

    private function loadTranslationsFromModel(): void
    {
        $this->translations = [];

        foreach ($this->category->translations as $translation) {
            $this->translations[(int) $translation->language_id] = $this->shapeTranslation($translation);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function shapeTranslation(CategoryTranslation $translation): array
    {
        return [
            'name' => (string) ($translation->name ?? ''),
            'slug' => (string) ($translation->slug ?? ''),
            'description' => (string) ($translation->description ?? ''),
            'meta_title' => (string) ($translation->meta_title ?? ''),
            'meta_description' => (string) ($translation->meta_description ?? ''),
        ];
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

    public function save(UpdateCategoryAction $updateCategory): void
    {
        $this->authorize('update', $this->category);

        try {
            $this->validate($this->rules());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatchDangerToast('Please fix the highlighted errors.');

            throw $e;
        }

        try {
            $updateCategory->handle($this->category, $this->buildPayload());

            $this->category = $this->category->fresh(['translations']);
            $this->loadTranslationsFromModel();

            $this->dispatchSuccessToast('Category updated.');
            $this->dispatch('category-saved', id: $this->category->id);
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Update failed: '.$exception->getMessage());
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
            $entry = [
                'language_id' => $languageId,
                'name' => trim((string) ($row['name'] ?? '')),
                'slug' => trim((string) ($row['slug'] ?? '')) !== ''
                    ? Str::slug((string) $row['slug'])
                    : Str::slug((string) ($row['name'] ?? '')),
                'description' => ((string) ($row['description'] ?? '')) !== '' ? $row['description'] : null,
                'meta_title' => ((string) ($row['meta_title'] ?? '')) !== '' ? $row['meta_title'] : null,
                'meta_description' => ((string) ($row['meta_description'] ?? '')) !== '' ? $row['meta_description'] : null,
            ];

            if (! empty($row['delete'])) {
                $entry['delete'] = true;
            }

            $rows[] = $entry;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $rules = [
            // Self-parenting is prevented inside UpdateCategoryAction so the
            // validation rule here only enforces existence/type — keeping it
            // simple avoids Livewire's nested-attribute quirks with the
            // `different:category.id` rule.
            'parentId' => ['nullable', 'integer', 'exists:categories,id'],
            'imageId' => ['nullable', 'integer', 'exists:media,id'],
            'icon' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:30'],
            'layout' => ['required', \Illuminate\Validation\Rule::in(Category::LAYOUTS)],
            'sortOrder' => ['integer', 'min:0'],
        ];

        if ($this->defaultLanguageId !== null) {
            $rules["translations.{$this->defaultLanguageId}.name"] = ['required', 'string', 'min:1', 'max:255'];
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

    /**
     * Mark a translation tab for removal — actual delete happens on
     * save via UpdateCategoryAction. Marking again toggles it back.
     */
    public function removeTranslation(int $languageId): void
    {
        if ($languageId === $this->defaultLanguageId) {
            $this->dispatchDangerToast('Cannot remove the default language.');

            return;
        }

        if (! isset($this->translations[$languageId])) {
            return;
        }

        $this->translations[$languageId]['delete'] = true;

        if ($this->activeLanguageId === $languageId) {
            $this->activeLanguageId = $this->defaultLanguageId;
        }

        $this->dispatchSuccessToast('Translation marked for removal — save to apply.');
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
        $present = array_keys(array_filter(
            $this->translations,
            fn (array $row): bool => empty($row['delete']),
        ));

        return $this->languages->reject(fn (Language $lang): bool => in_array((int) $lang->id, $present, true))->values();
    }

    /**
     * Parent options — exclude self (cannot be own parent).
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function parentOptions(): Collection
    {
        return Category::query()
            ->where('id', '!=', $this->category->id)
            ->with('translations')
            ->ordered()
            ->limit(500)
            ->get();
    }

    #[Computed]
    public function coverImage(): ?Media
    {
        return $this->imageId !== null ? Media::query()->find($this->imageId) : null;
    }

    public function render(): View
    {
        return view('livewire.admin.categories.edit');
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
