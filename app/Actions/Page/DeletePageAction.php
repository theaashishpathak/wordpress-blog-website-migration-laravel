<?php

declare(strict_types=1);

namespace App\Actions\Page;

use App\Models\Page;
use Illuminate\Support\Facades\DB;

/**
 * Soft-delete a Page. Translation rows cascade-deleted via FK only when
 * the parent row is hard-deleted; soft-delete leaves them intact, which
 * is the desired behaviour — restoring the Page later restores the
 * translations too.
 */
class DeletePageAction
{
    public function handle(Page $page): void
    {
        DB::transaction(function () use ($page): void {
            $page->delete();
        });
    }
}
