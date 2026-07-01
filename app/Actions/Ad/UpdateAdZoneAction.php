<?php

declare(strict_types=1);

namespace App\Actions\Ad;

use App\Models\AdZone;
use Illuminate\Support\Arr;

class UpdateAdZoneAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AdZone $zone, array $data): AdZone
    {
        // Key changes are explicitly NOT supported via update — once a
        // zone key is in the blade templates, renaming would silently
        // break all placements. Admins should clone + retire instead.
        $fields = Arr::only($data, [
            'name', 'description', 'width', 'height', 'position', 'is_active', 'max_creatives',
        ]);

        if (isset($fields['name'])) {
            $fields['name'] = trim((string) $fields['name']);
        }

        $zone->fill($fields)->save();

        return $zone->fresh();
    }
}
