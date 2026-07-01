<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Comment;
use App\Models\Media;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Admin topbar global search.
 *
 * Searches across every primary entity in the CMS — posts, pages,
 * categories, tags, media, comments, newsletter subscribers, and
 * staff users — and merges the matches into a single keyboard-driven
 * dropdown. Each result group is permission-gated; users only see
 * entities they have at least read access to.
 *
 * The search is debounced (live:300ms via wire:model in the blade)
 * and capped at ~6 results per entity so the dropdown stays one
 * scroll-tap. No URL persistence — the popover isn't a routable page,
 * see GlobalSearch's snapshot-loss notes in the blade comment.
 */
class GlobalSearch extends Component
{
    /**
     * Per-entity result cap. Keeps the dropdown short and the queries
     * cheap. The "See all" link in each group jumps to that module's
     * index page with the query prefilled.
     */
    private const PER_GROUP_LIMIT = 6;

    public string $query = '';

    public bool $open = false;

    /**
     * Index of the highlighted item across the flat result list.
     * Maintained by Alpine on the client; Livewire only reads it back
     * when the user presses Enter to navigate.
     */
    public int $cursor = 0;

    public function updatedQuery(): void
    {
        $this->open = trim($this->query) !== '';
        $this->cursor = 0;
    }

    public function close(): void
    {
        $this->open = false;
        $this->query = '';
        $this->cursor = 0;
    }

    /**
     * Used by the Alpine keyboard handler to jump to a result by index.
     *
     * @return string|null  the destination URL, or null when the cursor
     *                      points at a non-link group header.
     */
    public function urlForCursor(int $index): ?string
    {
        $flat = $this->flatResults();

        return $flat[$index]['url'] ?? null;
    }

    // -------------------------------------------------------------------------
    // Result builders
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{label:string, icon:string, color:string, items:Collection, see_all_url:?string}>
     */
    public function groups(): array
    {
        $term = trim($this->query);

        if ($term === '') {
            return [];
        }

        $like = '%'.$term.'%';

        return array_filter([
            'posts' => $this->postsGroup($like, $term),
            'pages' => $this->pagesGroup($like),
            'categories' => $this->categoriesGroup($like),
            'tags' => $this->tagsGroup($like),
            'media' => $this->mediaGroup($like),
            'comments' => $this->commentsGroup($like),
            'subscribers' => $this->subscribersGroup($like),
            'staff' => $this->staffGroup($like),
        ], fn (?array $group): bool => $group !== null && $group['items']->isNotEmpty());
    }

    /**
     * Flatten every group's items into a single index for keyboard navigation.
     *
     * @return list<array{group:string, label:string, sublabel:?string, url:?string, icon:string}>
     */
    public function flatResults(): array
    {
        $out = [];
        foreach ($this->groups() as $key => $group) {
            foreach ($group['items'] as $item) {
                $out[] = [
                    'group' => $key,
                    'label' => $item['label'],
                    'sublabel' => $item['sublabel'] ?? null,
                    'url' => $item['url'] ?? null,
                    'icon' => $item['icon'] ?? $group['icon'],
                ];
            }
        }

        return $out;
    }

    public function totalResults(): int
    {
        return count($this->flatResults());
    }

    // -------------------------------------------------------------------------
    // Individual entity search methods. Each returns the standardised group
    // shape, or null when the current user lacks the permission to see it.
    // -------------------------------------------------------------------------

    /**
     * @return array{label:string, icon:string, color:string, items:Collection, see_all_url:?string}|null
     */
    private function postsGroup(string $like, string $term): ?array
    {
        if (! Gate::any(['posts.view', 'posts.view_any'])) {
            return null;
        }

        $postIds = PostTranslation::query()
            ->where(function ($q) use ($like): void {
                $q->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->orderByDesc('updated_at')
            ->limit(self::PER_GROUP_LIMIT * 3)   // de-dupe headroom
            ->pluck('post_id')
            ->unique()
            ->take(self::PER_GROUP_LIMIT);

        $posts = Post::query()
            ->with(['translations', 'category.translations'])
            ->whereIn('id', $postIds)
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $items = $posts->map(function (Post $p): array {
            $title = $p->translation()?->title ?? '#'.$p->id;
            $cat = $p->category?->translation()?->name;
            $status = $p->status?->value;

            return [
                'label' => $title,
                'sublabel' => trim(
                    ($cat ? $cat.' · ' : '')
                    .ucfirst(str_replace('_', ' ', (string) $status))
                    .' · '.($p->published_at?->diffForHumans() ?? 'unpublished'),
                    ' ·',
                ),
                'url' => Gate::allows('posts.edit') || Gate::allows('posts.edit_own')
                    ? route('admin.posts.edit', $p)
                    : route('admin.posts.show', $p),
                'icon' => 'newspaper',
            ];
        });

        return [
            'label' => 'Posts',
            'icon' => 'newspaper',
            'color' => 'from-indigo-500 to-violet-500',
            'items' => $items,
            'see_all_url' => route('admin.posts.index'),
        ];
    }

    private function pagesGroup(string $like): ?array
    {
        if (! Gate::allows('pages.view')) {
            return null;
        }

        $pageIds = PageTranslation::query()
            ->where(function ($q) use ($like): void {
                $q->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->limit(self::PER_GROUP_LIMIT * 3)
            ->pluck('page_id')
            ->unique()
            ->take(self::PER_GROUP_LIMIT);

        $pages = Page::query()->with('translations')->whereIn('id', $pageIds)->get();

        if ($pages->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Pages',
            'icon' => 'file-text',
            'color' => 'from-emerald-500 to-teal-500',
            'items' => $pages->map(fn (Page $p): array => [
                'label' => $p->translation()?->title ?? '#'.$p->id,
                'sublabel' => '/'.($p->translation()?->slug ?? '').' · '.$p->status->value,
                'url' => Gate::allows('pages.edit')
                    ? route('admin.pages.edit', $p)
                    : route('admin.pages.index'),
                'icon' => 'file-text',
            ]),
            'see_all_url' => route('admin.pages.index'),
        ];
    }

    private function categoriesGroup(string $like): ?array
    {
        if (! Gate::allows('categories.view')) {
            return null;
        }

        $catIds = CategoryTranslation::query()
            ->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->limit(self::PER_GROUP_LIMIT * 2)
            ->pluck('category_id')
            ->unique()
            ->take(self::PER_GROUP_LIMIT);

        $cats = Category::query()->with('translations')->whereIn('id', $catIds)->get();

        if ($cats->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Categories',
            'icon' => 'folder-tree',
            'color' => 'from-amber-500 to-orange-500',
            'items' => $cats->map(fn (Category $c): array => [
                'label' => $c->translation()?->name ?? '#'.$c->id,
                'sublabel' => '/'.($c->translation()?->slug ?? '—'),
                'url' => Gate::allows('categories.edit')
                    ? route('admin.categories.edit', $c)
                    : route('admin.categories.index'),
                'icon' => 'folder',
            ]),
            'see_all_url' => route('admin.categories.index'),
        ];
    }

    private function tagsGroup(string $like): ?array
    {
        if (! Gate::allows('tags.view')) {
            return null;
        }

        $tagIds = TagTranslation::query()
            ->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->pluck('tag_id');

        // Fall back to legacy single-locale columns on tags table.
        $legacyIds = Tag::query()
            ->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->pluck('id');

        $allIds = $tagIds->merge($legacyIds)->unique()->take(self::PER_GROUP_LIMIT);
        $tags = Tag::query()->whereIn('id', $allIds)->get();

        if ($tags->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Tags',
            'icon' => 'tags',
            'color' => 'from-fuchsia-500 to-pink-500',
            'items' => $tags->map(fn (Tag $t): array => [
                'label' => $t->translate('name', 'en') ?? $t->name ?? '#'.$t->id,
                'sublabel' => $t->slug,
                'url' => route('admin.tags.index'),
                'icon' => 'tag',
            ]),
            'see_all_url' => route('admin.tags.index'),
        ];
    }

    private function mediaGroup(string $like): ?array
    {
        if (! Gate::allows('media.view')) {
            return null;
        }

        $items = Media::query()
            ->where(function ($q) use ($like): void {
                $q->where('original_filename', 'like', $like)
                    ->orWhere('alt_text', 'like', $like)
                    ->orWhere('caption', 'like', $like);
            })
            ->latest()
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Media Library',
            'icon' => 'image',
            'color' => 'from-sky-500 to-cyan-500',
            'items' => $items->map(fn (Media $m): array => [
                'label' => $m->original_filename,
                'sublabel' => $m->mime_type.($m->width ? ' · '.$m->width.'×'.$m->height : '').' · '.($m->size ? number_format((int) ($m->size / 1024)).' KB' : ''),
                'url' => route('admin.media.index'),
                'icon' => Str::startsWith($m->mime_type, 'image/') ? 'image' : (Str::startsWith($m->mime_type, 'video/') ? 'video' : 'file'),
            ]),
            'see_all_url' => route('admin.media.index'),
        ];
    }

    private function commentsGroup(string $like): ?array
    {
        if (! Gate::allows('comments.moderate')) {
            return null;
        }

        $items = Comment::query()
            ->with('post.translations')
            ->where(function ($q) use ($like): void {
                $q->where('body', 'like', $like)
                    ->orWhere('guest_name', 'like', $like)
                    ->orWhere('guest_email', 'like', $like);
            })
            ->latest()
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Comments',
            'icon' => 'message-square',
            'color' => 'from-rose-500 to-pink-500',
            'items' => $items->map(fn (Comment $c): array => [
                'label' => Str::limit(strip_tags((string) $c->body), 60),
                'sublabel' => 'on "'.Str::limit((string) ($c->post?->translation()?->title ?? '#'.$c->post_id), 40).'" · '.$c->status,
                'url' => route('admin.comments.index'),
                'icon' => 'message-square',
            ]),
            'see_all_url' => route('admin.comments.index'),
        ];
    }

    private function subscribersGroup(string $like): ?array
    {
        if (! Gate::allows('newsletter.view')) {
            return null;
        }

        $items = NewsletterSubscriber::query()
            ->where(function ($q) use ($like): void {
                $q->where('email', 'like', $like)->orWhere('name', 'like', $like);
            })
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Newsletter Subscribers',
            'icon' => 'send',
            'color' => 'from-pink-500 to-rose-500',
            'items' => $items->map(fn (NewsletterSubscriber $s): array => [
                'label' => $s->email,
                'sublabel' => ($s->name ?? '—').' · '.$s->status,
                'url' => route('admin.newsletter.subscribers'),
                'icon' => 'mail',
            ]),
            'see_all_url' => route('admin.newsletter.subscribers'),
        ];
    }

    private function staffGroup(string $like): ?array
    {
        if (! Gate::allows('staff.view')) {
            return null;
        }

        $users = User::query()
            ->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('employee_id', 'like', $like)
                    ->orWhere('job_title', 'like', $like);
            })
            ->with('department:id,name')
            ->orderBy('name')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        if ($users->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Staff',
            'icon' => 'users-round',
            'color' => 'from-slate-500 to-slate-700',
            'items' => $users->map(fn (User $u): array => [
                'label' => $u->name,
                'sublabel' => trim(($u->employee_id ? $u->employee_id.' · ' : '').($u->job_title ?? $u->email).($u->department ? ' · '.$u->department->name : ''), ' ·'),
                'url' => route('admin.staff.show', $u),
                'icon' => 'user-round',
            ]),
            'see_all_url' => route('admin.staff.index'),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.global-search', [
            'groups' => $this->groups(),
            'total' => $this->totalResults(),
        ]);
    }
}
