<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Highlight;

use App\Models\Highlight;

class DeleteHighlightAction
{
    public function handle(Highlight $highlight): bool
    {
        return (bool) $highlight->delete();
    }
}
