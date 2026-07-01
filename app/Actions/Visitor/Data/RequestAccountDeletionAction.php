<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Data;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Schedule a soft account deletion. By default the user has 30 days to
 * change their mind — see CancelAccountDeletionAction. The actual hard
 * delete is performed by the `accounts:process-deletions` console command
 * (registered in routes/console.php).
 *
 * Returns the freshly-created request row.
 */
class RequestAccountDeletionAction
{
    public function handle(
        User $user,
        ?string $reason = null,
        ?string $note = null,
        int $graceDays = 30,
    ): AccountDeletionRequest {
        $existing = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->pending()
            ->latest()
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'request' => 'An account deletion is already scheduled.',
            ]);
        }

        $request = AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'reason' => $reason,
            'note' => $note ? mb_substr(trim($note), 0, 1000) : null,
            'scheduled_for' => now()->addDays(max(1, $graceDays)),
        ]);

        $user->logProfileActivity(
            'account_deletion_requested',
            sprintf('Scheduled account deletion for %s.', $request->scheduled_for?->format('M j, Y')),
            ['reason' => $reason, 'grace_days' => $graceDays],
        );

        return $request;
    }
}
