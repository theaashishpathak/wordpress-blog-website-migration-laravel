<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Authorization rules for the Category model.
 *
 * Permission keys come from config/permissions.php (`categories.*`).
 * Super Admin short-circuits via Gate::before in AuthServiceProvider so
 * these methods never run for that role.
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.edit');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete');
    }

    /**
     * Reordering is a structural edit — gated by the same permission as
     * a normal update so contributors / authors cannot rearrange the
     * site's taxonomy.
     */
    public function reorder(User $user): bool
    {
        return $user->can('categories.edit');
    }
}
