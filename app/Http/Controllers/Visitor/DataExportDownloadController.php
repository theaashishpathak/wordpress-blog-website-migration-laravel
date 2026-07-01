<?php

declare(strict_types=1);

namespace App\Http\Controllers\Visitor;

use App\Models\DataExportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streams a finished GDPR archive to the requesting visitor. Auth-only,
 * own-rows-only, requires status=ready, refuses after expires_at passes.
 */
class DataExportDownloadController
{
    public function __invoke(Request $request, DataExportRequest $export): BinaryFileResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);
        abort_if($export->user_id !== $user->id, 403);
        abort_if(! $export->isReady(), 410);

        $disk = Storage::disk('local');
        abort_if($export->file_path === null || ! $disk->exists($export->file_path), 404);

        return response()->download(
            $disk->path($export->file_path),
            'newspilot-data-export-'.$export->id.'.zip',
            ['Content-Type' => 'application/zip']
        );
    }
}
