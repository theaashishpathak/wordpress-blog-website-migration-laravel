<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Highlight;

use App\Models\Highlight;

/**
 * Update only the personal note attached to a highlight. The selected text
 * itself is immutable once captured — to change it the user should delete +
 * re-highlight. This keeps the context_hash stable.
 */
class UpdateHighlightNoteAction
{
    public function handle(Highlight $highlight, ?string $note): Highlight
    {
        $highlight->update([
            'note' => $note !== null ? trim($note) : null,
        ]);

        return $highlight->fresh() ?? $highlight;
    }
}
