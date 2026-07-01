<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Posts;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Placeholder for the admin post detail view. Real implementation in
 * Phase 4B will include translation tabs, editorial notes thread,
 * revision history, and SEO snapshot.
 */
#[Layout('layouts.app')]
#[Title('Post Detail')]
class Show extends Component
{
    public Post $post;

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->authorize('view', $post);
    }

    public function render(): View
    {
        return view('livewire.admin.posts.show', [
            'post' => $this->post->load([
                'category',
                'author',
                'featuredImage',
                'translations.language',
                'tags',
                'editorialNotes.author',
                'revisions',
            ]),
        ]);
    }
}
