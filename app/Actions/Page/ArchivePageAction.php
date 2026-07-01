<?php

declare(strict_types=1);

namespace App\Actions\Page;

use App\Enums\PageStatus;
use App\Models\Page;

/**
 * Move a Page to `archived` status without deleting it.
 *
 * Archived pages are hidden from frontend listings but their content
 * remains intact, can be restored via UpdatePageAction (set status back
 * to draft / published).
 */
class ArchivePageAction
{
    public function handle(Page $page): Page
    {
        $page->forceFill(['status' => PageStatus::Archived->value])->save();

        return $page->fresh();
    }
}
