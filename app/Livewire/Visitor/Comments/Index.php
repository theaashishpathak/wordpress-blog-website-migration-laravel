<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Comments;

use App\Actions\Visitor\Comment\DeleteOwnCommentAction;
use App\Actions\Visitor\Comment\UpdateOwnCommentAction;
use App\Models\Comment;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('My Comments')]
class Index extends Component
{
    use WithPagination;

    /** Filter: 'all' | 'approved' | 'pending' | 'spam' */
    #[Url(as: 'status')]
    public string $filter = 'all';

    public ?int $editingId = null;

    public string $editingBody = '';

    /**
     * @return LengthAwarePaginator<Comment>
     */
    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        $query = Comment::query()
            ->where('user_id', auth()->id())
            ->with([
                'post.translations',
                'post.category.translations',
            ])
            ->latest();

        if (in_array($this->filter, [Comment::STATUS_APPROVED, Comment::STATUS_PENDING, Comment::STATUS_SPAM], true)) {
            $query->where('status', $this->filter);
        }

        return $query->paginate(15);
    }

    public function switchFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', Comment::STATUS_APPROVED, Comment::STATUS_PENDING, Comment::STATUS_SPAM], true)
            ? $filter
            : 'all';
        $this->resetPage();
    }

    public function startEditing(int $id): void
    {
        $comment = $this->loadOwned($id);
        $this->editingId = $comment->id;
        $this->editingBody = $comment->body;
    }

    public function cancelEditing(): void
    {
        $this->editingId = null;
        $this->editingBody = '';
    }

    public function saveEdit(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $comment = $this->loadOwned($this->editingId);
        app(UpdateOwnCommentAction::class)->handle(auth()->user(), $comment, $this->editingBody);

        $this->cancelEditing();
        unset($this->comments);
        $this->dispatch('toast', message: 'Comment updated — pending re-review by moderators.');
    }

    public function delete(int $id): void
    {
        $comment = $this->loadOwned($id);
        app(DeleteOwnCommentAction::class)->handle(auth()->user(), $comment);

        unset($this->comments);
        $this->dispatch('toast', message: 'Comment deleted.');
    }

    private function loadOwned(int $id): Comment
    {
        return Comment::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.visitor.comments.index', [
            'counts' => [
                'all' => Comment::query()->where('user_id', auth()->id())->count(),
                'approved' => Comment::query()->where('user_id', auth()->id())->where('status', Comment::STATUS_APPROVED)->count(),
                'pending' => Comment::query()->where('user_id', auth()->id())->where('status', Comment::STATUS_PENDING)->count(),
                'spam' => Comment::query()->where('user_id', auth()->id())->where('status', Comment::STATUS_SPAM)->count(),
            ],
        ]);
    }
}
