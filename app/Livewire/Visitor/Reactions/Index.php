<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Reactions;

use App\Actions\Visitor\Reaction\ToggleReactionAction;
use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('Likes & Dislikes')]
class Index extends Component
{
    use WithPagination;

    /** 'like' | 'dislike' */
    #[Url(as: 'type')]
    public string $filter = 'like';

    /**
     * @return LengthAwarePaginator<PostReaction>
     */
    #[Computed]
    public function reactions(): LengthAwarePaginator
    {
        return PostReaction::query()
            ->where('user_id', auth()->id())
            ->where('type', $this->filter)
            ->with([
                'post.translations',
                'post.author:id,name',
                'post.featuredImage:id,disk,path,mime_type,alt_text',
                'post.category.translations',
            ])
            ->latest()
            ->paginate(12);
    }

    public function switchFilter(string $filter): void
    {
        $this->filter = in_array($filter, PostReaction::TYPES, true) ? $filter : PostReaction::TYPE_LIKE;
        $this->resetPage();
    }

    /**
     * Remove a reaction from the list (toggle off).
     */
    public function remove(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);
        app(ToggleReactionAction::class)->handle(auth()->user(), $post, $this->filter);

        unset($this->reactions);
        $this->dispatch('toast', message: 'Reaction removed.');
    }

    public function render(): View
    {
        return view('livewire.visitor.reactions.index', [
            'likeCount' => auth()->user()->reactions()->where('type', PostReaction::TYPE_LIKE)->count(),
            'dislikeCount' => auth()->user()->reactions()->where('type', PostReaction::TYPE_DISLIKE)->count(),
        ]);
    }
}
