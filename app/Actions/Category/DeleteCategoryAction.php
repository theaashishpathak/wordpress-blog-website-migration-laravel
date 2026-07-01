<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Soft-delete a Category. Direct children are re-parented:
 *
 *  - Default behaviour: move children to the deleted category's parent
 *    (so a 3-level tree collapses to 2 levels rather than orphaning content).
 *  - Set $orphanChildren = true to instead make children root-level.
 */
class DeleteCategoryAction
{
    public function handle(Category $category, bool $orphanChildren = false): void
    {
        DB::transaction(function () use ($category, $orphanChildren): void {
            $newParentId = $orphanChildren ? null : $category->parent_id;

            $category->children()->update(['parent_id' => $newParentId]);

            $category->delete();
        });
    }
}
