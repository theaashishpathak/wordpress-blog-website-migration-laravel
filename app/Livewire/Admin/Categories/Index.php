<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Categories;

use App\Actions\Category\DeleteCategoryAction;
use App\Actions\Category\ReorderCategoriesAction;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Categories Index — nested tree browser with native HTML5 drag-and-drop
 * reorder and re-parent.
 *
 * Conventions:
 *   - List rendered as a flat-but-indented tree (one DOM row per
 *     category, an `data-depth` attribute drives left-padding).
 *   - Drag-drop emits the `reorder` Livewire method with a flat list of
 *     `{ id, sort_order, parent_id }` items. ReorderCategoriesAction
 *     persists the new order in a single transaction.
 *   - Filters (search by translated name, language, featured-only,
 *     in-menu-only) are URL-bound for shareable views.
 *   - Bulk delete is policy-gated per row.
 *
 * Performance: the page loads up to 500 categories at once because the
 * tree must be coherent — pagination would chop branches. For sites with
 * more than 500 categories we'd switch to lazy children loading, but
 * that's outside Phase 4D.
 */
#[Layout('layouts.app')]
#[Title('Categories')]
class Index extends Component
{
    private const HARD_LIMIT = 500;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'lang')]
    public string $languageFilter = '';

    #[Url(as: 'featured')]
    public bool $featuredOnly = false;

    #[Url(as: 'menu')]
    public bool $inMenuOnly = false;

    /**
     * Category IDs ticked in the bulk-action column.
     *
     * @var list<int>
     */
    public array $selectedIds = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function updatingSearch(): void
    {
        $this->selectedIds = [];
    }

    public function updatingLanguageFilter(): void
    {
        $this->selectedIds = [];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'languageFilter', 'featuredOnly', 'inMenuOnly']);
        $this->selectedIds = [];
    }

    // -------------------------------------------------------------------------
    // Drag-and-drop entry point
    // -------------------------------------------------------------------------

    /**
     * Persist a new order for the visible tree.
     *
     * @param  list<array{id:int|string, sort_order?:int|string, parent_id?:int|string|null}>  $items
     */
    public function reorder(array $items, ReorderCategoriesAction $action): void
    {
        if (! Gate::allows('reorder', Category::class)) {
            $this->dispatchDangerToast('You cannot reorder categories.');

            return;
        }

        if ($items === []) {
            return;
        }

        try {
            $normalized = $this->normalizeReorderPayload($items);
            $action->handle($normalized);
            unset($this->tree);
            $this->dispatchSuccessToast('Category order updated.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Reorder failed: '.$exception->getMessage());
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{id:int, sort_order:int, parent_id:int|null}>
     */
    private function normalizeReorderPayload(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $parentRaw = $item['parent_id'] ?? null;
            $parentId = ($parentRaw === null || $parentRaw === '' || (int) $parentRaw === 0)
                ? null
                : (int) $parentRaw;

            $rows[] = [
                'id' => $id,
                'sort_order' => (int) ($item['sort_order'] ?? 0),
                'parent_id' => $parentId,
            ];
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Per-row + bulk actions
    // -------------------------------------------------------------------------

    public function deleteCategory(int $categoryId, DeleteCategoryAction $delete): void
    {
        $category = Category::query()->find($categoryId);

        if ($category === null) {
            $this->dispatchDangerToast('Category not found.');

            return;
        }

        if (! Gate::allows('delete', $category)) {
            $this->dispatchDangerToast('You cannot delete this category.');

            return;
        }

        try {
            $delete->handle($category);
            unset($this->tree);
            $this->selectedIds = array_values(array_filter(
                $this->selectedIds,
                fn ($id): bool => (int) $id !== $categoryId,
            ));
            $this->dispatchSuccessToast('Category deleted.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Delete failed: '.$exception->getMessage());
        }
    }

    public function bulkDelete(DeleteCategoryAction $delete): void
    {
        if ($this->selectedIds === []) {
            $this->dispatchDangerToast('Select at least one category.');

            return;
        }

        $deleted = 0;

        foreach (Category::query()->whereIn('id', $this->selectedIds)->get() as $category) {
            if (! Gate::allows('delete', $category)) {
                continue;
            }

            try {
                $delete->handle($category);
                $deleted++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        unset($this->tree);
        $this->selectedIds = [];
        $this->dispatchSuccessToast("Deleted {$deleted} categor".($deleted === 1 ? 'y' : 'ies').'.');
    }

    // -------------------------------------------------------------------------
    // Reactive query — full tree
    // -------------------------------------------------------------------------

    /**
     * Build the entire visible tree.
     *
     * Returns a flat list of rows annotated with depth so the blade can
     * indent each item without recursive includes. Children always
     * follow their parent in pre-order traversal so drag-drop can
     * reorder by neighbour index.
     *
     * @return list<array{category: Category, depth: int}>
     */
    #[Computed]
    public function tree(): array
    {
        $categories = $this->buildBaseQuery();

        $hasFilter = $this->hasActiveFilter();
        $visibleIds = null;

        if ($hasFilter) {
            $matchedIds = $this->matchingIds($categories);
            $visibleIds = $this->expandWithAncestors($categories, $matchedIds);
        }

        $byParent = $this->groupByParent($categories);

        return $this->flattenTree($byParent, parentId: null, depth: 0, visibleIds: $visibleIds);
    }

    private function hasActiveFilter(): bool
    {
        return trim($this->search) !== ''
            || $this->languageFilter !== ''
            || $this->featuredOnly
            || $this->inMenuOnly;
    }

    /**
     * @return Collection<int, Category>
     */
    private function buildBaseQuery(): Collection
    {
        return Category::query()
            ->with([
                'translations.language',
                'parent:id',
            ])
            ->ordered()
            ->limit(self::HARD_LIMIT)
            ->get();
    }

    /**
     * IDs of categories that pass the current filter set.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<int, true>
     */
    private function matchingIds(Collection $categories): array
    {
        $matches = [];
        $search = mb_strtolower(trim($this->search));
        $languageId = $this->languageFilter !== '' ? (int) $this->languageFilter : null;

        foreach ($categories as $category) {
            if ($this->featuredOnly && ! $category->is_featured) {
                continue;
            }

            if ($this->inMenuOnly && ! $category->show_in_menu) {
                continue;
            }

            if ($search !== '') {
                $hasMatch = $category->translations->contains(function ($t) use ($search, $languageId): bool {
                    if ($languageId !== null && (int) $t->language_id !== $languageId) {
                        return false;
                    }

                    $name = mb_strtolower((string) $t->name);
                    $slug = mb_strtolower((string) $t->slug);

                    return str_contains($name, $search) || str_contains($slug, $search);
                });

                if (! $hasMatch) {
                    continue;
                }
            } elseif ($languageId !== null) {
                $hasLanguage = $category->translations->contains(
                    fn ($t): bool => (int) $t->language_id === $languageId
                );

                if (! $hasLanguage) {
                    continue;
                }
            }

            $matches[(int) $category->id] = true;
        }

        return $matches;
    }

    /**
     * Expand the matched set with every ancestor so the tree stays
     * coherent — otherwise a deep match would render as an orphan.
     *
     * @param  Collection<int, Category>  $categories
     * @param  array<int, true>  $matchedIds
     * @return array<int, true>
     */
    private function expandWithAncestors(Collection $categories, array $matchedIds): array
    {
        $byId = $categories->keyBy('id');
        $visible = $matchedIds;

        foreach (array_keys($matchedIds) as $id) {
            $cursor = $byId->get($id);

            while ($cursor !== null && $cursor->parent_id !== null) {
                $parentId = (int) $cursor->parent_id;

                if (isset($visible[$parentId])) {
                    break;
                }

                $visible[$parentId] = true;
                $cursor = $byId->get($parentId);
            }
        }

        return $visible;
    }

    /**
     * Group children under their parent id (null for roots), preserving
     * the eager-loaded order.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<int|string, list<Category>>
     */
    private function groupByParent(Collection $categories): array
    {
        $grouped = [];

        foreach ($categories as $category) {
            $key = $category->parent_id === null ? 'root' : (string) $category->parent_id;
            $grouped[$key][] = $category;
        }

        return $grouped;
    }

    /**
     * @param  array<int|string, list<Category>>  $byParent
     * @param  array<int, true>|null  $visibleIds  null = no filter, show every row
     * @return list<array{category: Category, depth: int}>
     */
    private function flattenTree(array $byParent, ?int $parentId, int $depth, ?array $visibleIds): array
    {
        $key = $parentId === null ? 'root' : (string) $parentId;
        $children = $byParent[$key] ?? [];
        $rows = [];

        foreach ($children as $child) {
            $isVisible = $visibleIds === null || isset($visibleIds[(int) $child->id]);

            if ($isVisible) {
                $rows[] = ['category' => $child, 'depth' => $depth];
            }

            $rows = array_merge(
                $rows,
                $this->flattenTree($byParent, (int) $child->id, $depth + 1, $visibleIds),
            );
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Computed properties for the view
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Category::query()->count();
    }

    // -------------------------------------------------------------------------
    // Event listeners
    // -------------------------------------------------------------------------

    /**
     * Refresh tree after Create/Edit components dispatch a save event.
     */
    #[On('category-saved')]
    public function onCategorySaved(): void
    {
        unset($this->tree);
    }

    public function render(): View
    {
        return view('livewire.admin.categories.index');
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
