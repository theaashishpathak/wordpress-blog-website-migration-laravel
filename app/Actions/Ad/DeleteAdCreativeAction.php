<?php

declare(strict_types=1);

namespace App\Actions\Ad;

use App\Models\AdCreative;

class DeleteAdCreativeAction
{
    public function handle(AdCreative $creative, bool $force = false): void
    {
        $force ? $creative->forceDelete() : $creative->delete();
    }
}
