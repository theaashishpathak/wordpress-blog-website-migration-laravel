<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Visitor\Data\ProcessAccountDeletionAction;
use App\Models\AccountDeletionRequest;
use Illuminate\Console\Command;

/**
 * Hard-deletes accounts whose 30-day grace window has elapsed.
 * Scheduled daily — see routes/console.php.
 */
class ProcessAccountDeletionsCommand extends Command
{
    protected $signature = 'accounts:process-deletions {--dry : Show what would be processed without deleting}';

    protected $description = 'Process account deletion requests whose grace window has expired';

    public function handle(ProcessAccountDeletionAction $action): int
    {
        $due = AccountDeletionRequest::query()->due()->with('user')->get();

        if ($due->isEmpty()) {
            $this->info('No accounts due for deletion.');

            return self::SUCCESS;
        }

        $this->info('Found '.$due->count().' due deletion request(s).');

        $dry = (bool) $this->option('dry');
        $processed = 0;

        foreach ($due as $request) {
            $email = $request->user?->email ?? '(deleted)';
            if ($dry) {
                $this->line("  would delete user_id={$request->user_id} ({$email}) scheduled_for={$request->scheduled_for}");

                continue;
            }

            $ok = $action->handle($request);
            $processed += $ok ? 1 : 0;
            $this->line('  '.($ok ? 'deleted' : 'skipped').' user_id='.$request->user_id.' ('.$email.')');
        }

        $this->info($dry ? 'Dry run — nothing deleted.' : "Done. Deleted {$processed} account(s).");

        return self::SUCCESS;
    }
}
