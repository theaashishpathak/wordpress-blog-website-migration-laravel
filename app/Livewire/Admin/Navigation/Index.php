<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Navigation;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Header Navigation Management')]
class Index extends Component
{
    public bool $modalOpen = false;
    public ?int $editingId = null;

    // Form fields
    public string $title = '';
    public string $url = '';
    public string $type = 'custom'; // 'custom', 'page', 'category', 'route'
    public ?int $parent_id = null;
    public string $target = '_self'; // '_self', '_blank'
    public ?string $icon = null;
    public int $order = 0;
    public bool $is_active = true;
    public string $location = 'header';

    // Quick pickers
    public ?int $selected_page_id = null;
    public ?int $selected_category_id = null;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'type' => 'required|string|in:custom,page,category,route',
            'parent_id' => 'nullable|exists:navigation_items,id',
            'target' => 'required|string|in:_self,_blank',
            'icon' => 'nullable|string|max:50',
            'order' => 'required|integer',
            'is_active' => 'required|boolean',
            'location' => 'required|string|in:header,footer',
        ];
    }

    public function updatedType(string $value): void
    {
        $this->selected_page_id = null;
        $this->selected_category_id = null;
    }

    public function updatedSelectedPageId(?int $pageId): void
    {
        if (! $pageId) {
            return;
        }

        $page = Page::find($pageId);
        if ($page) {
            $trans = $page->translation();
            if ($trans) {
                $this->title = $trans->title;
                $this->url = '/page/' . $trans->slug;
            }
        }
    }

    public function updatedSelectedCategoryId(?int $catId): void
    {
        if (! $catId) {
            return;
        }

        $cat = Category::find($catId);
        if ($cat) {
            $slug = $cat->translate('slug');
            $this->title = html_entity_decode((string) ($cat->translate('name') ?? ('#' . $cat->id)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->url = '/category/' . $slug;
            if ($cat->icon) {
                $this->icon = $cat->icon;
            }
        }
    }

    public function openCreateModal(?int $parentId = null): void
    {
        $this->reset(['title', 'url', 'type', 'target', 'icon', 'editingId', 'selected_page_id', 'selected_category_id']);
        $this->parent_id = $parentId;
        $this->target = '_self';
        $this->is_active = true;
        $this->location = 'header';

        // Auto-assign next order
        $maxOrder = NavigationItem::where('location', 'header')
            ->where('parent_id', $parentId)
            ->max('order') ?? 0;
        $this->order = $maxOrder + 1;

        $this->modalOpen = true;
    }

    public function edit(int $id): void
    {
        $item = NavigationItem::findOrFail($id);

        $this->editingId = $item->id;
        $this->title = $item->title;
        $this->url = $item->url;
        $this->type = $item->type;
        $this->parent_id = $item->parent_id;
        $this->target = $item->target;
        $this->icon = $item->icon;
        $this->order = $item->order;
        $this->is_active = $item->is_active;
        $this->location = $item->location;

        $this->modalOpen = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $item = NavigationItem::findOrFail($this->editingId);
            $item->update($data);
            $this->dispatch('toast.success', message: 'Menu item updated successfully.');
        } else {
            NavigationItem::create($data);
            $this->dispatch('toast.success', message: 'Menu item created successfully.');
        }

        $this->modalOpen = false;
        $this->reset(['title', 'url', 'type', 'parent_id', 'target', 'icon', 'editingId']);
    }

    public function toggleActive(int $id): void
    {
        $item = NavigationItem::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();

        $this->dispatch('toast.success', message: 'Status updated.');
    }

    public function moveUp(int $id): void
    {
        $item = NavigationItem::findOrFail($id);

        $previous = NavigationItem::where('location', $item->location)
            ->where('parent_id', $item->parent_id)
            ->where('order', '<', $item->order)
            ->orderByDesc('order')
            ->first();

        if ($previous) {
            $currentOrder = $item->order;
            $item->order = $previous->order;
            $previous->order = $currentOrder;

            $item->save();
            $previous->save();
        }
    }

    public function moveDown(int $id): void
    {
        $item = NavigationItem::findOrFail($id);

        $next = NavigationItem::where('location', $item->location)
            ->where('parent_id', $item->parent_id)
            ->where('order', '>', $item->order)
            ->orderBy('order')
            ->first();

        if ($next) {
            $currentOrder = $item->order;
            $item->order = $next->order;
            $next->order = $currentOrder;

            $item->save();
            $next->save();
        }
    }

    public function delete(int $id): void
    {
        $item = NavigationItem::findOrFail($id);
        $item->delete();

        $this->dispatch('toast.success', message: 'Menu item deleted successfully.');
    }

    public function resetToDefaults(): void
    {
        NavigationItem::where('location', 'header')->delete();
        NavigationItem::seedDefaults();

        $this->dispatch('toast.success', message: 'Navigation menu reset to default items.');
    }

    public function render(): View
    {
        $headerItems = NavigationItem::query()
            ->forLocation('header')
            ->topLevel()
            ->with(['children'])
            ->ordered()
            ->get();

        $topLevelParents = NavigationItem::query()
            ->forLocation('header')
            ->topLevel()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->ordered()
            ->get();

        $availablePages = Page::query()->ordered()->get();
        $availableCategories = Category::query()->orderBy('id')->get();

        return view('livewire.admin.navigation.index', [
            'headerItems' => $headerItems,
            'topLevelParents' => $topLevelParents,
            'availablePages' => $availablePages,
            'availableCategories' => $availableCategories,
        ]);
    }
}
