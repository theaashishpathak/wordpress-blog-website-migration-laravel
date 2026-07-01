<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Persist a new sort_order for a batch of categories — typically driven
 * by a drag-and-drop UI in the admin Taxonomy page.
 *
 * Optionally re-parents each category at the same time so a single
 * drop-onto-another-row gesture can both reorder and move under a new
 * parent in one transaction.
 */
class ReorderCategoriesAction
{
    /**
     * @param  list<array{id:int, sort_order:int, parent_id?:int|null}>  $items
     */
    public function handle(array $items): void
    {
        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                $id = (int) ($item['id'] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                $payload = [
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                ];

                if (array_key_exists('parent_id', $item)) {
                    $parentId = $item['parent_id'];

                    // Defensive: prevent self-parenting.
                    if ($parentId !== null && (int) $parentId === $id) {
                        $parentId = null;
                    }

                    $payload['parent_id'] = $parentId;
                }

                Category::query()->whereKey($id)->update($payload);
            }
        });
    }
}
