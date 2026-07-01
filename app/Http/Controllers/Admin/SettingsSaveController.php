<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsSaveController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SettingService $settingService): RedirectResponse
    {
        abort_unless(Gate::allows('settings.update'), 403);

        $groups = config('settings.groups', []);

        $validated = $request->validate([
            'group' => ['required', 'string', Rule::in(array_keys($groups))],
            'values' => ['required', 'array'],
        ]);

        $group = (string) $validated['group'];
        $fields = Arr::get($groups, $group.'.fields', []);
        $values = (array) $validated['values'];

        foreach ($fields as $field) {
            $settingKey = (string) $field['key'];
            $type = (string) Arr::get($field, 'type', Setting::TYPE_TEXT);
            $stateKey = $this->stateKey($settingKey);
            $value = Arr::get($values, $settingKey, Arr::get($values, $stateKey));

            // Encrypted fields: empty input preserves the existing value.
            if ($type === Setting::TYPE_ENCRYPTED) {
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }
            }

            if ($type === Setting::TYPE_IMAGE) {
                $upload = $request->file('uploads.'.$settingKey) ?? $request->file('uploads.'.$stateKey);

                if ($upload !== null) {
                    $value = $upload->store('settings', 'public');
                }
            }

            if ($type === Setting::TYPE_BOOLEAN) {
                $value = (bool) $value;
            }

            if ($type === Setting::TYPE_NUMBER) {
                $value = $value === null || $value === '' ? null : ((float) $value);
            }

            if ($type === Setting::TYPE_JSON && is_string($value) && $value !== '') {
                try {
                    $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    throw ValidationException::withMessages([
                        'values.'.$settingKey => 'This field must contain valid JSON.',
                    ]);
                }
            }

            if (is_string($value) && trim($value) === '') {
                $value = null;
            }

            $settingService->set($settingKey, $value, $group, $type, false);
        }

        $settingService->reloadCache();

        return redirect()
            ->route('admin.settings.group', ['group' => $group])
            ->with('success', 'Settings saved successfully.');
    }

    private function stateKey(string $settingKey): string
    {
        return str_replace('.', '__', $settingKey);
    }
}
