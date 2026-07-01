<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Merge one or more source tags into a target tag.
 *
 * Re-points every post_tag pivot row from each source tag to the target,
 * deduplicates so a post never carries the same tag twice, then deletes
 * the source tags. Idempotent — passing the target's own id as a source
 * is a no-op.
 *
 * The post_tag pivot table doesn't exist yet (lands in Phase 2E). When
 * absent this Action gracefully skips pivot remapping and only handles
 * tag deletion, so the API contract is stable for future use.
 */
class MergeTagsAction
{
    /**
     * @param  list<int>  $sourceTagIds
     */
    public function handle(Tag $target, array $sourceTagIds): Tag
    {
        if ($sourceTagIds === []) {
            return $target;
        }

        $sourceTagIds = array_values(array_filter(
            $sourceTagIds,
            fn (int $id): bool => $id !== (int) $target->id,
        ));

        if ($sourceTagIds === []) {
            return $target;
        }

        DB::transaction(function () use ($target, $sourceTagIds): void {
            if (Schema::hasTable('post_tag')) {
                $this->remapPivotRows($target, $sourceTagIds);
            }

            Tag::query()->whereIn('id', $sourceTagIds)->delete();
        });

        return $target->fresh(['translations']);
    }

    /**
     * @param  list<int>  $sourceTagIds
     */
    private function remapPivotRows(Tag $target, array $sourceTagIds): void
    {
        // Two-step: re-point rows that don't yet exist on the target,
        // then delete any leftover duplicates pointing at the sources.
        $existingPostIds = DB::table('post_tag')
            ->where('tag_id', $target->id)
            ->pluck('post_id')
            ->all();

        DB::table('post_tag')
            ->whereIn('tag_id', $sourceTagIds)
            ->whereNotIn('post_id', $existingPostIds)
            ->update(['tag_id' => $target->id]);

        DB::table('post_tag')
            ->whereIn('tag_id', $sourceTagIds)
            ->delete();
    }
}
