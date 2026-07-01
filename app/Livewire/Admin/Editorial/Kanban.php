<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Editorial;

use App\Actions\Editorial\ApprovePostAction;
use App\Actions\Editorial\RejectPostAction;
use App\Actions\Editorial\RequestChangesAction;
use App\Actions\Editorial\SubmitForReviewAction;
use App\Actions\Post\PublishPostAction;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Editorial review queue kanban.
 *
 * Each visible status is rendered as a column. Cards summarise the
 * post (title, author, featured image, last note, time-in-status) and
 * support drag-and-drop between columns via Livewire 4 wire:sort.
 *
 * Status transitions are NOT performed inline — they always go through
 * the workflow Action layer so PostStatus::canTransitionTo() and the
 * EditorialNote audit trail stay authoritative. Invalid transitions
 * are rejected with a toast and the dropped card snaps back via
 * `reloadColumns()`.
 *
 * Performance: each column fetches up to 60 posts, ordered by
 * updated_at desc. The kanban is for active editorial work, not full
 * archives — use Posts Index for unlimited browsing.
 */
#[Layout('layouts.app')]
#[Title('Editorial Queue')]
class Kanban extends Component
{
    private const POSTS_PER_COLUMN = 60;

    /**
     * Statuses shown as kanban columns, in display order.
     *
     * @var list<PostStatus>
     */
    public const VISIBLE_STATUSES = [
        PostStatus::PendingReview,
        PostStatus::InReview,
        PostStatus::ChangesRequested,
        PostStatus::Approved,
        PostStatus::Scheduled,
    ];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public string $categoryFilter = '';

    #[Url(as: 'author')]
    public string $authorFilter = '';

    /**
     * When true the kanban only shows posts authored by — or recently
     * acted on by — the logged-in user.
     */
    #[Url(as: 'mine')]
    public bool $onlyMine = false;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('editorial.review_queue') ?? false,
            403,
            'You do not have access to the editorial review queue.',
        );
    }

    // -------------------------------------------------------------------------
    // Drag-and-drop entry point
    // -------------------------------------------------------------------------

    /**
     * Called by the wire:sort directive when a card moves between
     * columns. $payload comes from the front-end SortableJS-style
     * format: `{ targetStatus, postId, order: [postId, ...] }`.
     *
     * We deliberately discard the within-column order — kanban is
     * sorted by updated_at and we don't persist manual reordering.
     */
    public function move(string $targetStatus, int $postId): void
    {
        try {
            $target = PostStatus::from($targetStatus);
        } catch (\ValueError) {
            $this->dispatchDangerToast("Unknown status: {$targetStatus}.");

            return;
        }

        $post = Post::query()->find($postId);

        if ($post === null) {
            $this->dispatchDangerToast('Post not found.');

            return;
        }

        if (! $post->status->canTransitionTo($target)) {
            $this->dispatchDangerToast(
                "Cannot move from [{$post->status->label()}] to [{$target->label()}]."
            );

            return;
        }

        try {
            $this->applyTransition($post, $target);
            $this->dispatchSuccessToast("Moved to {$target->label()}.");
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Transition failed: '.$exception->getMessage());
        }
    }

    /**
     * Map (current status × target status) to the workflow Action that
     * implements that transition. Each branch is gated by the Post
     * policy so editors lacking permission get a hard 403.
     */
    private function applyTransition(Post $post, PostStatus $target): void
    {
        $user = auth()->user();

        match ($target) {
            PostStatus::PendingReview => $this->guard($post, 'submitForReview')
                && app(SubmitForReviewAction::class)->handle($post, $user),

            PostStatus::InReview => $this->markInReview($post),

            PostStatus::Approved => $this->guard($post, 'approve')
                && app(ApprovePostAction::class)->handle($post, $user),

            PostStatus::ChangesRequested => $this->guard($post, 'requestChanges')
                && app(RequestChangesAction::class)->handle(
                    $post,
                    $user,
                    feedback: 'Moved to Changes Requested from the kanban.',
                ),

            PostStatus::Scheduled => $this->moveToScheduled($post),

            PostStatus::Published => $this->guard($post, 'publish')
                && app(PublishPostAction::class)->handle($post, cascadeTranslations: true),

            PostStatus::Rejected => $this->guard($post, 'reject')
                && app(RejectPostAction::class)->handle(
                    $post,
                    $user,
                    reason: 'Rejected from the kanban.',
                ),

            default => throw new InvalidArgumentException(
                "No kanban handler for target status [{$target->value}]."
            ),
        };
    }

    private function guard(Post $post, string $ability): bool
    {
        if (! Gate::allows($ability, $post)) {
            abort(403, "Permission denied: {$ability}");
        }

        return true;
    }

    /**
     * "In Review" doesn't have a dedicated Action — it's a soft state
     * editors flip to when they pick up a post. We update the status
     * directly when the policy allows.
     */
    private function markInReview(Post $post): void
    {
        $this->guard($post, 'approve');     // same permission gate
        $post->forceFill(['status' => PostStatus::InReview->value])->save();
    }

    /**
     * Scheduling without a date is a flag flip — the queued publish
     * job picks up scheduled posts when `scheduled_at` is reached. If
     * the post lacks a future scheduled_at, we reject the move.
     */
    private function moveToScheduled(Post $post): void
    {
        $this->guard($post, 'publish');

        if ($post->scheduled_at === null || $post->scheduled_at->isPast()) {
            throw new InvalidArgumentException(
                'Set a future scheduled_at before moving the post to Scheduled.'
            );
        }

        $post->forceFill(['status' => PostStatus::Scheduled->value])->save();
    }

    // -------------------------------------------------------------------------
    // Reactive query
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{status: PostStatus, label: string, color: string, count: int, posts: \Illuminate\Support\Collection<int, Post>}>
     */
    #[Computed]
    public function columns(): array
    {
        $columns = [];

        foreach (self::VISIBLE_STATUSES as $status) {
            $posts = $this->buildBaseQuery()
                ->where('status', $status->value)
                ->orderByDesc('updated_at')
                ->limit(self::POSTS_PER_COLUMN)
                ->get();

            $columns[$status->value] = [
                'status' => $status,
                'label' => $status->label(),
                'color' => $this->columnColor($status),
                'count' => $posts->count(),
                'posts' => $posts,
            ];
        }

        return $columns;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Post>
     */
    private function buildBaseQuery()
    {
        $query = Post::query()
            ->with([
                'translations',
                'author:id,name',
                'featuredImage:id,disk,path,mime_type,alt_text',
                'category:id',
                'editorialNotes' => fn ($q) => $q->latest()->limit(1),
            ]);

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->whereHas('translations', fn ($q) => $q->where('title', 'like', $term));
        }

        if ($this->categoryFilter !== '') {
            $query->where('category_id', (int) $this->categoryFilter);
        }

        if ($this->authorFilter !== '') {
            $query->where('author_id', (int) $this->authorFilter);
        }

        if ($this->onlyMine) {
            $userId = (int) auth()->id();
            $query->where(function ($q) use ($userId): void {
                $q->where('author_id', $userId)
                    ->orWhereHas('editorialNotes', fn ($notes) => $notes->where('author_id', $userId));
            });
        }

        return $query;
    }

    private function columnColor(PostStatus $status): string
    {
        return match ($status) {
            PostStatus::PendingReview => 'amber',
            PostStatus::InReview => 'sky',
            PostStatus::ChangesRequested => 'orange',
            PostStatus::Approved => 'indigo',
            PostStatus::Scheduled => 'violet',
            default => 'slate',
        };
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('id')->limit(200)->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function authors(): Collection
    {
        $authorIds = Post::query()
            ->whereIn('status', array_map(fn (PostStatus $s) => $s->value, self::VISIBLE_STATUSES))
            ->distinct()
            ->pluck('author_id')
            ->filter()
            ->values();

        return User::query()->whereIn('id', $authorIds)->orderBy('name')->get(['id', 'name']);
    }

    // -------------------------------------------------------------------------
    // Filter handlers
    // -------------------------------------------------------------------------

    public function updatedSearch(): void
    {
        // Force recompute of columns by clearing the Livewire computed cache.
        unset($this->columns);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->authorFilter = '';
        $this->onlyMine = false;
    }

    public function render(): View
    {
        return view('livewire.admin.editorial.kanban');
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
