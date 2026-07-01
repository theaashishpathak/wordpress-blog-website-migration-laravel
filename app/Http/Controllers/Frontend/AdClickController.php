<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Actions\Ad\RecordClickAction;
use App\Models\AdCreative;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Click-through redirector for ad creatives.
 *
 * `target_url` lives on the row + click_count is incremented atomically
 * before redirecting. Visitors hit `/ads/click/{creative}` from the
 * rendered <a> wrapper rather than the raw URL, so we can measure CTR
 * server-side without relying on JS beacons.
 */
class AdClickController
{
    public function __invoke(int $creative, RecordClickAction $record): RedirectResponse
    {
        $row = AdCreative::query()->find($creative);

        if ($row === null || $row->target_url === null || $row->target_url === '') {
            throw new NotFoundHttpException('Unknown ad creative.');
        }

        $record->handle($row);

        return redirect()->away($row->target_url, 302);
    }
}
