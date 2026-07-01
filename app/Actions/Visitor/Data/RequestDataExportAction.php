<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Data;

use App\Jobs\GenerateUserDataExport;
use App\Models\DataExportRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Create a new GDPR data-export request + dispatch the job that assembles
 * the archive. Throttled to one pending/processing request at a time
 * per user — repeated clicks just return the existing in-flight row.
 */
class RequestDataExportAction
{
    public function handle(User $user): DataExportRequest
    {
        $inFlight = DataExportRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                DataExportRequest::STATUS_PENDING,
                DataExportRequest::STATUS_PROCESSING,
            ])
            ->latest()
            ->first();

        if ($inFlight !== null) {
            throw ValidationException::withMessages([
                'export' => 'You already have an export in progress.',
            ]);
        }

        $request = DataExportRequest::query()->create([
            'user_id' => $user->id,
            'status' => DataExportRequest::STATUS_PENDING,
        ]);

        GenerateUserDataExport::dispatch($request);

        $user->logProfileActivity(
            'data_export_requested',
            'Requested a personal data export (GDPR portability).',
        );

        return $request;
    }
}
