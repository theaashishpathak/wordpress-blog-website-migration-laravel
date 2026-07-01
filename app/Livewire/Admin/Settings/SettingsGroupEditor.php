<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Settings Group')]
class SettingsGroupEditor extends Component
{
    use WithFileUploads;

    public string $group = '';

    /**
     * @var array<string, array<string, mixed>>
     */
    public array $groups = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $fields = [];

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * @var array<string, TemporaryUploadedFile>
     */
    public array $uploads = [];

    public function mount(string $group, SettingService $settingService): void
    {
        abort_unless(Gate::allows('settings.view'), 403);

        /** @var array<string, array<string, mixed>> $groups */
        $groups = config('settings.groups', []);

        abort_unless(array_key_exists($group, $groups), 404);

        $this->group = $group;
        $this->groups = collect($groups)
            ->mapWithKeys(function (array $groupConfig, string $groupSlug): array {
                return [$groupSlug => [
                    'slug' => $groupSlug,
                    'label' => Arr::get($groupConfig, 'label', str($groupSlug)->replace('-', ' ')->title()->toString()),
                    'description' => Arr::get($groupConfig, 'description', ''),
                    'icon' => Arr::get($groupConfig, 'icon', 'settings-2'),
                    'color' => Arr::get($groupConfig, 'color', 'slate'),
                    'field_count' => count(Arr::get($groupConfig, 'fields', [])),
                ]];
            })
            ->all();

        $groupConfig = $groups[$group];
        $configuredFields = Arr::get($groupConfig, 'fields', []);

        $this->fields = array_map(function (array $field) use ($settingService): array {
            $stateKey = $this->stateKey((string) $field['key']);
            $resolvedValue = $settingService->get((string) $field['key']);

            $this->values[$stateKey] = $this->normalizeValueForInput((string) Arr::get($field, 'type', Setting::TYPE_TEXT), $resolvedValue);

            return [
                ...$field,
                'state_key' => $stateKey,
                'input' => Arr::get($field, 'input', $this->resolveInput((string) Arr::get($field, 'type', Setting::TYPE_TEXT))),
            ];
        }, $configuredFields);
    }

    public function save(SettingService $settingService): void
    {
        abort_unless(Gate::allows('settings.update'), 403);

        $validated = $this->validate($this->rules());

        foreach ($this->fields as $field) {
            $settingKey = (string) $field['key'];
            $type = (string) Arr::get($field, 'type', Setting::TYPE_TEXT);
            $stateKey = (string) $field['state_key'];

            $value = Arr::get($validated, 'values.'.$stateKey);

            // Encrypted fields: empty input means "keep existing value".
            // Skip the save entirely so we don't wipe an existing API key.
            if ($type === Setting::TYPE_ENCRYPTED) {
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }
            }

            if ($type === Setting::TYPE_IMAGE) {
                $upload = Arr::get($validated, 'uploads.'.$stateKey);

                if ($upload instanceof TemporaryUploadedFile) {
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
                        'values.'.$stateKey => 'This field must contain valid JSON.',
                    ]);
                }
            }

            if (is_string($value) && trim($value) === '') {
                $value = null;
            }

            $settingService->set($settingKey, $value, $this->group, $type, false);
        }

        $settingService->reloadCache();

        $this->dispatch('toast.success', message: 'Settings saved successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $type = (string) Arr::get($field, 'type', Setting::TYPE_TEXT);
            $stateKey = (string) $field['state_key'];
            $valuePath = 'values.'.$stateKey;

            if ($type === Setting::TYPE_NUMBER) {
                $rules[$valuePath] = ['nullable', 'numeric'];
            } elseif ($type === Setting::TYPE_BOOLEAN) {
                $rules[$valuePath] = ['nullable', 'boolean'];
            } elseif ($type === Setting::TYPE_SELECT) {
                $options = array_keys((array) Arr::get($field, 'options', []));
                $rules[$valuePath] = ['nullable', Rule::in($options)];
            } elseif ($type === Setting::TYPE_JSON) {
                $rules[$valuePath] = ['nullable', 'string'];
            } else {
                $rules[$valuePath] = ['nullable', 'string'];
            }

            if ($type === Setting::TYPE_IMAGE) {
                $rules['uploads.'.$stateKey] = ['nullable', 'image', 'max:2048'];
            }
        }

        return $rules;
    }

    public function render(): View
    {
        $currentGroup = $this->groups[$this->group] ?? ['label' => str($this->group)->replace('-', ' ')->title()->toString(), 'description' => '', 'color' => 'slate', 'icon' => 'settings-2'];

        return view('admin.settings.group', [
            'currentGroup' => $currentGroup,
            'colorIconLg' => self::colorIconMap(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function colorIconMap(): array
    {
        return [
            'indigo'  => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
            'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
            'violet'  => 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
            'sky'     => 'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
            'amber'   => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
            'rose'    => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
            'cyan'    => 'bg-cyan-100 text-cyan-600 dark:bg-cyan-500/15 dark:text-cyan-300',
            'slate'   => 'bg-slate-100 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300',
            'orange'  => 'bg-orange-100 text-orange-600 dark:bg-orange-500/15 dark:text-orange-300',
            'teal'    => 'bg-teal-100 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300',
            'pink'    => 'bg-pink-100 text-pink-600 dark:bg-pink-500/15 dark:text-pink-300',
            'red'     => 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300',
        ];
    }

    private function stateKey(string $settingKey): string
    {
        return str_replace('.', '__', $settingKey);
    }

    private function resolveInput(string $type): string
    {
        return match ($type) {
            Setting::TYPE_BOOLEAN => 'toggle',
            Setting::TYPE_NUMBER => 'number',
            Setting::TYPE_SELECT => 'select',
            Setting::TYPE_IMAGE => 'image',
            Setting::TYPE_JSON => 'textarea',
            Setting::TYPE_ENCRYPTED => 'password',
            default => 'text',
        };
    }

    private function normalizeValueForInput(string $type, mixed $value): mixed
    {
        if ($type === Setting::TYPE_JSON) {
            if ($value === null || $value === '') {
                return '';
            }

            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        if ($type === Setting::TYPE_BOOLEAN) {
            return (bool) $value;
        }

        if ($type === Setting::TYPE_ENCRYPTED) {
            // Never pre-populate encrypted fields. Empty input on submit
            // means "keep existing value" (handled in save()).
            return '';
        }

        return $value;
    }
}
