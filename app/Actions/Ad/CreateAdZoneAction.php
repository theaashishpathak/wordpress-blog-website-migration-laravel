<?php

declare(strict_types=1);

namespace App\Actions\Ad;

use App\Models\AdZone;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateAdZoneAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): AdZone
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Zone name is required.']);
        }

        $key = trim((string) ($data['key'] ?? ''));
        $key = $key !== '' ? Str::slug($key, '_') : Str::slug($name, '_');

        if (AdZone::query()->where('key', $key)->exists()) {
            throw ValidationException::withMessages(['key' => "Zone key [{$key}] already exists."]);
        }

        return AdZone::query()->create([
            'key' => $key,
            'name' => $name,
            'description' => $data['description'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'position' => $data['position'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'max_creatives' => (int) ($data['max_creatives'] ?? 1),
        ]);
    }
}
