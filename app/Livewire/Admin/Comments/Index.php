<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Comments;

use App\Actions\Comment\ApproveCommentAction;
use App\Actions\Comment\DeleteCommentAction;
use App\Actions\Comment\MarkSpamAction;
use App\Models\Comment;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Admin moderation queue — pending comments at the top, filterable by
 * status (pending / approved / spam / trash). Bulk + per-row actions
 * route through the existing Comment Actions so the same flow is used
 * whether moderation happens via the UI or programmatically.
 */
#[Layout('layouts.app')]
#[Title('Comment Moderation')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = Comment::STATUS_PENDING;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * @var list<int>
     */
    public array $selectedIds = [];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('comments.moderate') ?? false,
            403,
            'You do not have access to moderate comments.',
        );
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'search']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<Comment>
     */
    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        $query = Comment::query()
            ->with([
                'post.translations',
                'author:id,name',
                'parent:id,body,user_id,guest_name',
                'parent.author:id,name',
            ])
            ->orderByDesc('created_at');

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('body', 'like', $term)
                    ->orWhere('guest_email', 'like', $term)
                    ->orWhere('guest_name', 'like', $term);
            });
        }

        return $query->paginate(perPage: 25);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return [
            'pending' => Comment::query()->pending()->count(),
            'approved' => Comment::query()->approved()->count(),
            'spam' => Comment::query()->spam()->count(),
            'trash' => Comment::onlyTrashed()->count(),
        ];
    }

    // -- Per-row actions ------------------------------------------------------

    public function approve(int $commentId, ApproveCommentAction $approve): void
    {
        $this->authorize('comments.approve');

        $comment = Comment::query()->findOrFail($commentId);
        $approve->handle($comment, auth()->user());
        $this->dispatchSuccessToast('Comment approved.');
    }

    public function markSpam(int $commentId, MarkSpamAction $mark): void
    {
        $this->authorize('comments.spam');

        $comment = Comment::query()->findOrFail($commentId);
        $mark->handle($comment, auth()->user());
        $this->dispatchSuccessToast('Comment marked as spam.');
    }

    public function deleteComment(int $commentId, DeleteCommentAction $delete): void
    {
        $this->authorize('comments.delete');

        try {
            $comment = Comment::query()->findOrFail($commentId);
            $delete->handle($comment, auth()->user());
            $this->dispatchSuccessToast('Comment deleted.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Delete failed: '.$exception->getMessage());
        }
    }

    // -- Bulk actions ---------------------------------------------------------

    public function bulkApprove(ApproveCommentAction $approve): void
    {
        $this->authorize('comments.approve');

        $count = 0;
        foreach (Comment::query()->whereIn('id', $this->selectedIds)->get() as $comment) {
            $approve->handle($comment, auth()->user());
            $count++;
        }

        $this->selectedIds = [];
        $this->dispatchSuccessToast("Approved {$count} comment(s).");
    }

    public function bulkSpam(MarkSpamAction $mark): void
    {
        $this->authorize('comments.spam');

        $count = 0;
        foreach (Comment::query()->whereIn('id', $this->selectedIds)->get() as $comment) {
            $mark->handle($comment, auth()->user());
            $count++;
        }

        $this->selectedIds = [];
        $this->dispatchSuccessToast("Marked {$count} comment(s) as spam.");
    }

    public function bulkDelete(DeleteCommentAction $delete): void
    {
        $this->authorize('comments.delete');

        $count = 0;
        foreach (Comment::query()->whereIn('id', $this->selectedIds)->get() as $comment) {
            $delete->handle($comment, auth()->user());
            $count++;
        }

        $this->selectedIds = [];
        $this->dispatchSuccessToast("Deleted {$count} comment(s).");
    }

    public function render(): View
    {
        return view('livewire.admin.comments.index');
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
