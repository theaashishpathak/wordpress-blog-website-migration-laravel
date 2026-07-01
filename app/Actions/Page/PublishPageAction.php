<?php

declare(strict_types=1);

namespace App\Actions\Page;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

/**
 * Transition a Page to `published` status.
 *
 * Two modes:
 *   - cascadeTranslations=false (default): only flip pages.status; per-locale
 *     translations keep their individual is_published toggles. Use when an
 *     editor wants to publish the page but still review each translation
 *     before exposing it.
 *   - cascadeTranslations=true: also flip every existing translation's
 *     is_published=true. Use for the simple admin "publish now everywhere"
 *     button.
 */
class PublishPageAction
{
    public function handle(Page $page, bool $cascadeTranslations = false): Page
    {
        DB::transaction(function () use ($page, $cascadeTranslations): void {
            $page->forceFill(['status' => PageStatus::Published->value])->save();

            if ($cascadeTranslations) {
                $page->translations()->update(['is_published' => true]);
            }
        });

        return $page->fresh(['translations']);
    }
}
