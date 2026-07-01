<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('frontend.layouts.app')]
class AuthorShow extends Component
{
    use WithPagination;

    public User $author;

    public function mount(?User $user = null): void
    {
        // /author/{user} route-binding hands us $user; Livewire::test()
        // hands us $author directly via the public-property array.
        if ($user !== null) {
            $this->author = $user;
        }
    }

    /**
     * @return LengthAwarePaginator<Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return Post::query()
            ->with(['translations', 'featuredImage:id,disk,path,mime_type,alt_text', 'category.translations'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->where('author_id', $this->author->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(perPage: 12);
    }

    public function render(): View
    {
        return view('livewire.frontend.author-show', [
            'metaTitle' => $this->author->name,
            'metaDescription' => 'Articles by '.$this->author->name,
        ]);
    }
}
