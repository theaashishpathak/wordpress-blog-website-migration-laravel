<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Facades\DB;

/**
 * Hard-delete a Tag and its translations.
 *
 * The tag_translations rows cascade automatically via FK ON DELETE CASCADE.
 * The post_tag pivot rows will also cascade once that table arrives in
 * Phase 2E.
 */
class DeleteTagAction
{
    public function handle(Tag $tag): void
    {
        DB::transaction(function () use ($tag): void {
            $tag->delete();
        });
    }
}
