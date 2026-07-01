<?php

declare(strict_types=1);

namespace App\Actions\Ad;

use App\Models\AdCreative;
use Illuminate\Support\Facades\DB;

class RecordClickAction
{
    public function handle(AdCreative $creative): void
    {
        DB::table('ad_creatives')->where('id', $creative->id)->increment('click_count');
    }
}
