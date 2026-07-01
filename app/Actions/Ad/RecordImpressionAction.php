<?php

declare(strict_types=1);

namespace App\Actions\Ad;

use App\Models\AdCreative;
use Illuminate\Support\Facades\DB;

/**
 * Increment a creative's impression_count atomically.
 *
 * The counter is denormalised on the row so the read-path stays cheap
 * — a separate event log table (Phase 7 polish) can join in for
 * granular analytics.
 */
class RecordImpressionAction
{
    public function handle(AdCreative $creative): void
    {
        DB::table('ad_creatives')->where('id', $creative->id)->increment('impression_count');
    }
}
