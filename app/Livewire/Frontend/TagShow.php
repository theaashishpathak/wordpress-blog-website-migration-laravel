<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('frontend.layouts.app')]
class TagShow extends Component
{
    public Tag $tag;

    /**
     * @return LengthAwarePaginator<Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return $this->tag->posts()
            ->with(['translations', 'author:id,name', 'featuredImage:id,disk,path,mime_type,alt_text', 'category:id,icon', 'category.translations'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(perPage: 12);
    }

    public function render(): View
    {
        $name = $this->tag->translate('name') ?? $this->tag->name;

        return view('livewire.frontend.tag-show', [
            'metaTitle' => '#'.$name,
            'metaDescription' => 'Posts tagged '.$name,
        ]);
    }
}
