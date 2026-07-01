<?php

declare(strict_types=1);

namespace App\Actions\Ad;

use App\Models\AdCreative;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateAdCreativeAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AdCreative $creative, array $data): AdCreative
    {
        return DB::transaction(function () use ($creative, $data): AdCreative {
            $fields = Arr::only($data, [
                'zone_id', 'name', 'type', 'media_id', 'target_url', 'alt_text',
                'html_code', 'status', 'start_at', 'end_at', 'priority', 'updated_by',
            ]);

            if (isset($fields['name'])) {
                $fields['name'] = trim((string) $fields['name']);
            }

            $fields['updated_by'] ??= auth()->id();

            $creative->fill($fields)->save();

            return $creative->fresh();
        });
    }
}
