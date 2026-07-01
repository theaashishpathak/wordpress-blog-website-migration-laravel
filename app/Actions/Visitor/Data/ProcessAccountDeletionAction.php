<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Data;

use App\Models\AccountDeletionRequest;
use Illuminate\Support\Facades\DB;

/**
 * Hard-delete a user account when its grace window has elapsed. Cascading
 * FKs on bookmarks / reading_history / etc. handle their own cleanup; we
 * just remove the User row and stamp the request as processed.
 *
 * Returns true if a deletion happened, false otherwise (already cancelled
 * or processed).
 */
class ProcessAccountDeletionAction
{
    public function handle(AccountDeletionRequest $request): bool
    {
        $fresh = $request->fresh();

        if ($fresh === null
            || $fresh->cancelled_at !== null
            || $fresh->processed_at !== null
            || $fresh->scheduled_for === null
            || $fresh->scheduled_for->isFuture()
        ) {
            return false;
        }

        DB::transaction(function () use ($fresh): void {
            // Mark processed BEFORE deleting so the row survives the cascade.
            $fresh->update(['processed_at' => now()]);

            $user = $fresh->user;
            if ($user !== null) {
                $user->delete();
            }
        });

        return true;
    }
}
