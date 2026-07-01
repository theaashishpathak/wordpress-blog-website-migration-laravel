<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Posts;

use App\Actions\Post\ArchivePostAction;
use App\Actions\Post\PublishPostAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Posts')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'category')]
    public string $categoryFilter = '';

    #[Url(as: 'author')]
    public string $authorFilter = '';

    #[Url(as: 'sort')]
    public string $sortField = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /**
     * @var list<int>
     */
    public array $selectedIds = [];

    public bool $selectAllOnPage = false;

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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAuthorFilter(): void
    {
        $this->resetPage();
    }

    public function toggleSort(string $field): void
    {
        $allowed = ['created_at', 'published_at', 'updated_at', 'view_count'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'desc';
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'statusFilter', 'categoryFilter', 'authorFilter']);
        $this->resetPage();
    }

    public function updatedSelectAllOnPage(bool $value): void
    {
        if (! $value) {
            $this->selectedIds = [];

            return;
        }

        $this->selectedIds = $this->postsQuery()
            ->forPage($this->getPage(), 20)
            ->pluck('id')
            ->all();
    }

    #[On('confirm-bulk-delete')]
    public function bulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        try {
            $count = 0;

            foreach (Post::query()->whereIn('id', $this->selectedIds)->get() as $post) {
                if (Gate::allows('delete', $post)) {
                    $post->delete();
                    $count++;
                }
            }

            $this->dispatchSuccessToast("Deleted {$count} post(s).");
            $this->selectedIds = [];
            $this->resetPage();
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Bulk delete failed.');
        }
    }

    public function requestBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            $this->dispatchDangerToast('Select at least one post first.');

            return;
        }

        $this->dispatch('confirm-bulk-delete-alert', count: count($this->selectedIds));
    }

    public function bulkPublish(PublishPostAction $publish): void
    {
        if ($this->selectedIds === []) {
            $this->dispatchDangerToast('Select at least one post first.');

            return;
        }

        $published = 0;

        foreach (Post::query()->whereIn('id', $this->selectedIds)->get() as $post) {
            if (! Gate::allows('publish', $post)) {
                continue;
            }

            try {
                $publish->handle(
                    $post,
                    cascadeTranslations: true,
                    allowDirectPublish: true,
                    publisher: auth()->user(),
                );
                $published++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->dispatchSuccessToast("Published {$published} post(s).");
        $this->selectedIds = [];
        $this->selectAllOnPage = false;
        $this->resetPage();
    }

    public function bulkArchive(ArchivePostAction $archive): void
    {
        if ($this->selectedIds === []) {
            $this->dispatchDangerToast('Select at least one post first.');

            return;
        }

        $archived = 0;

        foreach (Post::query()->whereIn('id', $this->selectedIds)->get() as $post) {
            if (! Gate::allows('archive', $post)) {
                continue;
            }

            try {
                $archive->handle($post);
                $archived++;
            } catch (Throwable) {
                // Skip ones already in terminal state.
            }
        }

        $this->dispatchSuccessToast("Archived {$archived} post(s).");
        $this->selectedIds = [];
        $this->selectAllOnPage = false;
        $this->resetPage();
    }

    public function render(): View
    {
        $posts = $this->postsQuery()->paginate(20);

        return view('livewire.admin.posts.index', [
            'posts' => $posts,
            'postTypes' => PostType::cases(),
            'postStatuses' => PostStatus::cases(),
            'categories' => Category::query()->orderBy('id')->limit(200)->get(),
            'authors' => User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Author', 'Editor', 'Admin', 'Contributor', 'Super Admin']))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Post>
     */
    protected function postsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();
        $canSeeAll = $user !== null && ($user->can('posts.view_any') || $user->hasRole('Super Admin'));

        // Eager-load EVERYTHING the index row touches, so the trait's
        // translate()/translation() short-circuits to the in-memory
        // collection instead of running a fresh SELECT per cell.
        //   - translations (all rows; `limit(1)` was a bug — it applies
        //     a single LIMIT across all posts, not 1-per-post)
        //   - category + category.translations (we render category name)
        //   - author (id, name)
        //   - featuredImage (id, path, disk, mime_type, alt_text)
        $query = Post::query()
            ->with([
                'category:id,icon',
                'category.translations',
                'author:id,name',
                'featuredImage:id,disk,path,mime_type,alt_text',
                'translations',
            ]);

        if (! $canSeeAll && $user !== null) {
            $query->where('author_id', $user->id);
        }

        return $query
            ->when($this->search !== '', function ($q): void {
                $term = '%'.$this->search.'%';
                $q->whereHas('translations', function ($t) use ($term): void {
                    $t->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term);
                });
            })
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category_id', (int) $this->categoryFilter))
            ->when($this->authorFilter !== '', fn ($q) => $q->where('author_id', (int) $this->authorFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->orderByDesc('id');
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
