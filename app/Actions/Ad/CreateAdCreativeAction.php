<?php

declare(strict_types=1);

namespace App\Actions\Ad;

use App\Models\AdCreative;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAdCreativeAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): AdCreative
    {
        $this->validate($data);

        return DB::transaction(function () use ($data): AdCreative {
            return AdCreative::query()->create([
                'zone_id' => (int) $data['zone_id'],
                'name' => trim((string) $data['name']),
                'type' => (string) ($data['type'] ?? AdCreative::TYPE_IMAGE),
                'media_id' => $data['media_id'] ?? null,
                'target_url' => $data['target_url'] ?? null,
                'alt_text' => $data['alt_text'] ?? null,
                'html_code' => $data['html_code'] ?? null,
                'status' => (string) ($data['status'] ?? AdCreative::STATUS_DRAFT),
                'start_at' => $data['start_at'] ?? null,
                'end_at' => $data['end_at'] ?? null,
                'priority' => (int) ($data['priority'] ?? 100),
                'created_by' => $data['created_by'] ?? auth()->id(),
                'updated_by' => $data['updated_by'] ?? $data['created_by'] ?? auth()->id(),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validate(array $data): void
    {
        if (empty($data['zone_id'])) {
            throw ValidationException::withMessages(['zone_id' => 'Zone is required.']);
        }
        if (empty(trim((string) ($data['name'] ?? '')))) {
            throw ValidationException::withMessages(['name' => 'Name is required.']);
        }

        $type = (string) ($data['type'] ?? AdCreative::TYPE_IMAGE);

        if (! in_array($type, AdCreative::TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'Unknown ad type.']);
        }

        if ($type === AdCreative::TYPE_IMAGE && empty($data['media_id'])) {
            throw ValidationException::withMessages(['media_id' => 'Image creatives require an attached media row.']);
        }

        if ($type === AdCreative::TYPE_HTML && empty(trim((string) ($data['html_code'] ?? '')))) {
            throw ValidationException::withMessages(['html_code' => 'HTML creatives require a code snippet.']);
        }
    }
}
