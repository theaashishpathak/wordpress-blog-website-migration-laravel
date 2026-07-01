<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class SettingsSeeder extends Seeder
{
    /**
     * Seed every setting key defined in config('settings.groups').
     *
     * Idempotent and value-preserving:
     *
     * - First seed: creates the row using `default` from config (if present)
     *   and runs the value through Setting::setValue() so it is correctly
     *   JSON-encoded (and encrypted for TYPE_ENCRYPTED).
     * - Re-seed: only updates the `group` and `type` columns; never touches
     *   the user-edited value. This means re-running migrations + seeders
     *   does NOT clobber admin-configured API keys, branding colors, etc.
     */
    public function run(): void
    {
        /** @var array<string, array<string, mixed>> $groups */
        $groups = config('settings.groups', []);

        foreach ($groups as $groupSlug => $group) {
            foreach (Arr::get($group, 'fields', []) as $field) {
                $this->seedField((string) $groupSlug, $field);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function seedField(string $groupSlug, array $field): void
    {
        $key = (string) $field['key'];
        $type = (string) Arr::get($field, 'type', Setting::TYPE_TEXT);

        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null) {
            $setting = new Setting([
                'key' => $key,
                'group' => $groupSlug,
                'type' => $type,
            ]);

            if (Arr::has($field, 'default')) {
                $setting->setValue(Arr::get($field, 'default'));
            }

            $setting->save();

            return;
        }

        // Re-seed: refresh group/type metadata only, leave value intact.
        $dirty = false;

        if ($setting->group !== $groupSlug) {
            $setting->group = $groupSlug;
            $dirty = true;
        }

        if ($setting->type !== $type) {
            $setting->type = $type;
            $dirty = true;
        }

        if ($dirty) {
            $setting->save();
        }
    }
}
