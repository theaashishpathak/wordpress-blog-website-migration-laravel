<?php

namespace App\Livewire\Admin\Settings;

use App\Actions\System\ClearCacheAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('General Settings')]
class SettingsIndex extends Component
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public array $groups = [];

    public bool $isClearingCache = false;

    public function mount(): void
    {
        abort_unless(Gate::allows('settings.view'), 403);

        /** @var array<string, array<string, mixed>> $groups */
        $groups = config('settings.groups', []);

        $this->groups = collect($groups)
            ->mapWithKeys(function (array $groupConfig, string $group): array {
                return [$group => [
                    'slug' => $group,
                    'label' => Arr::get($groupConfig, 'label', str($group)->replace('-', ' ')->title()->toString()),
                    'description' => Arr::get($groupConfig, 'description', ''),
                    'icon' => Arr::get($groupConfig, 'icon', 'settings-2'),
                    'color' => Arr::get($groupConfig, 'color', 'slate'),
                    'field_count' => count(Arr::get($groupConfig, 'fields', [])),
                ]];
            })
            ->all();
    }

    /**
     * Clear one or more cache layers. Called from the
     * "Clear Cache" buttons in the General Settings UI.
     */
    public function clearCache(ClearCacheAction $action, string $target = 'all'): void
    {
        // We re-use the settings.update permission so anyone allowed to
        // change settings can also flush caches. Tweak if you want a
        // dedicated permission like "system.cache.clear".
        abort_unless(Gate::allows('settings.update'), 403);

        $this->isClearingCache = true;

        try {
            $results = $action->handle($target === 'all' ? ['all'] : [$target]);

            $okCount = count(array_filter($results, fn ($r): bool => $r['ok']));
            $failCount = count($results) - $okCount;

            if ($failCount === 0) {
                $this->dispatch('toast.success', message: sprintf(
                    'Cache cleared (%d %s).',
                    $okCount,
                    $okCount === 1 ? 'target' : 'targets',
                ));
            } else {
                $first = array_values(array_filter($results, fn ($r): bool => ! $r['ok']))[0] ?? null;
                $this->dispatch('toast.danger', message: sprintf(
                    'Partial: %d cleared, %d failed. %s',
                    $okCount,
                    $failCount,
                    $first['message'] ?? '',
                ));
            }
        } finally {
            $this->isClearingCache = false;
        }
    }

    public function render(): View
    {
        return view('admin.settings.index', [
            'colorIconLg' => SettingsGroupEditor::colorIconMap(),
            'cacheTargets' => $this->cacheTargets(),
        ]);
    }

    /**
     * Per-target metadata for the System Maintenance buttons.
     * Defined in PHP (not Blade) so we don't have to fight Blade's
     * tokenizer over strings that contain comment-like sequences.
     *
     * @return list<array{key: string, label: string, icon: string, sub: string}>
     */
    private function cacheTargets(): array
    {
        return [
            ['key' => 'app',      'label' => 'Application Cache', 'icon' => 'database',   'sub' => 'cache:clear - default cache store.'],
            ['key' => 'config',   'label' => 'Config Cache',      'icon' => 'sliders',    'sub' => 'config:clear - reload env and config.'],
            ['key' => 'view',     'label' => 'Compiled Views',    'icon' => 'file-code',  'sub' => 'view:clear - rebuilds Blade templates.'],
            ['key' => 'route',    'label' => 'Route Cache',       'icon' => 'route',      'sub' => 'route:clear - reload route definitions.'],
            ['key' => 'settings', 'label' => 'Settings Cache',    'icon' => 'settings-2', 'sub' => 'Re-read settings table into memory.'],
            ['key' => 'storage',  'label' => 'Storage Link',      'icon' => 'link',       'sub' => 'storage:link - create the public storage symlink.'],
        ];
    }
}
