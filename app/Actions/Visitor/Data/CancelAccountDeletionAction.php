<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Data;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Cancel a pending account deletion. Idempotent — calling on an already
 * cancelled or processed row is a no-op.
 */
class CancelAccountDeletionAction
{
    public function handle(User $user, AccountDeletionRequest $request): AccountDeletionRequest
    {
        if ($request->user_id !== $user->id) {
            throw new AuthorizationException('You can only cancel your own deletion request.');
        }

        if ($request->cancelled_at === null && $request->processed_at === null) {
            $request->update(['cancelled_at' => now()]);

            $user->logProfileActivity(
                'account_deletion_cancelled',
                'Cancelled a scheduled account deletion.',
            );
        }

        return $request->fresh() ?? $request;
    }
}
