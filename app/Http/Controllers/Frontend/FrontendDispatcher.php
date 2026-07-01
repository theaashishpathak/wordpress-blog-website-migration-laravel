<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Shared helpers used by frontend Livewire components.
 *
 * Note: the public route table uses explicit per-type routes
 * (frontend.post.show, frontend.category, frontend.page, etc.) which
 * resolve their models directly via translation slug lookups in the
 * route service container. See routes/web.php for binding patterns.
 */
class FrontendDispatcher
{
    /**
     * Cheap visitor-debounced view counter. Stores a hash of the post
     * id in the session so reloads from the same visitor don't inflate
     * the count.
     */
    public static function bumpViewCount(Request $request, Post $post): void
    {
        $sessionKey = "post_view_{$post->id}";

        if ($request->session()?->has($sessionKey)) {
            return;
        }

        Post::query()->whereKey($post->id)->increment('view_count');
        $request->session()?->put($sessionKey, now()->timestamp);
    }
}
