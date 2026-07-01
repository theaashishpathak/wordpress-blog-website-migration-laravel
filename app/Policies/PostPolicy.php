<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

/**
 * Authorization rules for the Post model.
 *
 * Permission strings match config/permissions.php keys. Note that
 * Gate::before in AuthServiceProvider already short-circuits to true
 * for Super Admin, so these methods never run for that role.
 *
 * Ownership scoping (posts.edit_own / posts.delete_own) is checked here
 * because Spatie Permission alone can't express "edit your OWN posts"
 * — it only knows "has permission X". The policy method combines the
 * permission check with the ownership predicate.
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['posts.view', 'posts.view_any']);
    }

    public function view(User $user, Post $post): bool
    {
        // Anyone with "view any" can see every post in admin.
        if ($user->can('posts.view_any')) {
            return true;
        }

        // Otherwise only the author can see their own draft / pending posts.
        return $user->can('posts.view') && $post->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('posts.create');
    }

    public function update(User $user, Post $post): bool
    {
        // Universal edit permission wins.
        if ($user->can('posts.edit')) {
            return true;
        }

        // Otherwise: authors edit only their OWN posts AND only while the
        // post is still in an editable state (not published / archived).
        if ($user->can('posts.edit_own') && $post->isOwnedBy($user)) {
            return in_array($post->status, [
                PostStatus::Draft,
                PostStatus::ChangesRequested,
                PostStatus::Rejected,
            ], true);
        }

        return false;
    }

    public function delete(User $user, Post $post): bool
    {
        // Never let anyone delete a published post via this policy —
        // require unpublish + archive flow instead.
        if ($post->status === PostStatus::Published) {
            return false;
        }

        if ($user->can('posts.delete')) {
            return true;
        }

        return $user->can('posts.delete_own') && $post->isOwnedBy($user);
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->can('posts.delete');
    }

    public function forceDelete(User $user, Post $post): bool
    {
        // Force delete is admin-only — never for self-service.
        return $user->can('posts.delete');
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->can('posts.publish');
    }

    public function schedule(User $user, Post $post): bool
    {
        return $user->can('posts.schedule');
    }

    public function feature(User $user, Post $post): bool
    {
        return $user->can('posts.feature');
    }

    public function markBreaking(User $user, Post $post): bool
    {
        return $user->can('posts.mark_breaking');
    }

    public function archive(User $user, Post $post): bool
    {
        return $user->can('posts.archive');
    }

    public function preview(User $user, Post $post): bool
    {
        if ($user->can('posts.preview')) {
            return true;
        }

        return $post->isOwnedBy($user) && $user->can('posts.view');
    }

    // ----- Editorial workflow -----

    public function submitForReview(User $user, Post $post): bool
    {
        return $post->isOwnedBy($user)
            && $user->can('posts.create')
            && $post->status->canTransitionTo(PostStatus::PendingReview);
    }

    public function approve(User $user, Post $post): bool
    {
        return $user->can('editorial.approve');
    }

    public function reject(User $user, Post $post): bool
    {
        return $user->can('editorial.reject');
    }

    public function requestChanges(User $user, Post $post): bool
    {
        return $user->can('editorial.request_changes');
    }
}
