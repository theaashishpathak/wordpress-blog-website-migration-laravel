<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AI;

use App\Models\AIPromptTemplate;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * AI Prompt Templates — versioned CRUD.
 *
 * Edits don't mutate the row; they call AIPromptTemplate::bumpVersion()
 * so historical generations stay reproducible.
 */
#[Layout('layouts.app')]
#[Title('Prompt Templates')]
class PromptTemplates extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'locale')]
    public string $localeFilter = '';

    #[Url(as: 'key')]
    public string $keyFilter = '';

    public bool $activeOnly = false;

    // Modal state.
    public bool $editorOpen = false;

    public ?int $editingId = null;

    public string $form_key = '';

    public string $form_locale = 'en';

    public string $form_system_prompt = '';

    public string $form_user_prompt_template = '';

    /** @var list<string> */
    public array $form_variables = [];

    public string $form_variables_csv = '';

    public string $form_model_hint = '';

    public ?float $form_temperature_hint = null;

    public bool $form_is_active = true;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('ai.templates') ?? false,
            403,
            'You do not have access to manage prompt templates.',
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLocaleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingKeyFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'localeFilter', 'keyFilter', 'activeOnly']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<AIPromptTemplate>
     */
    #[Computed]
    public function templates(): LengthAwarePaginator
    {
        return AIPromptTemplate::query()
            ->with('createdBy:id,name')
            ->when($this->search !== '', function ($q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($qq) use ($term): void {
                    $qq->where('key', 'like', $term)
                        ->orWhere('system_prompt', 'like', $term)
                        ->orWhere('user_prompt_template', 'like', $term);
                });
            })
            ->when($this->localeFilter !== '', fn ($q) => $q->where('locale', $this->localeFilter))
            ->when($this->keyFilter !== '', fn ($q) => $q->where('key', $this->keyFilter))
            ->when($this->activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('key')
            ->orderBy('locale')
            ->orderByDesc('version')
            ->paginate(20);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    #[Computed]
    public function locales(): \Illuminate\Support\Collection
    {
        return Language::query()->active()->ordered()->pluck('code');
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    #[Computed]
    public function keyOptions(): \Illuminate\Support\Collection
    {
        return AIPromptTemplate::query()->distinct()->orderBy('key')->pluck('key');
    }

    /**
     * Open the create form.
     */
    public function openCreate(): void
    {
        $this->authorize('ai.templates');
        $this->resetForm();
        $this->editorOpen = true;
    }

    /**
     * Open the edit form — pre-fills from the existing row.
     * Editing will create a NEW version, not mutate the row.
     */
    public function edit(int $id): void
    {
        $this->authorize('ai.templates');
        $tpl = AIPromptTemplate::query()->findOrFail($id);

        $this->editingId = $tpl->id;
        $this->form_key = $tpl->key;
        $this->form_locale = $tpl->locale;
        $this->form_system_prompt = (string) $tpl->system_prompt;
        $this->form_user_prompt_template = (string) $tpl->user_prompt_template;
        $this->form_variables = $tpl->requiredVariables();
        $this->form_variables_csv = implode(', ', $this->form_variables);
        $this->form_model_hint = (string) ($tpl->model_hint ?? '');
        $this->form_temperature_hint = $tpl->temperature_hint;
        $this->form_is_active = (bool) $tpl->is_active;

        $this->editorOpen = true;
    }

    public function closeEditor(): void
    {
        $this->editorOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form_key = '';
        $this->form_locale = 'en';
        $this->form_system_prompt = '';
        $this->form_user_prompt_template = '';
        $this->form_variables = [];
        $this->form_variables_csv = '';
        $this->form_model_hint = '';
        $this->form_temperature_hint = null;
        $this->form_is_active = true;
    }

    public function save(): void
    {
        $this->authorize('ai.templates');

        $this->validate([
            'form_key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.-]+$/i'],
            'form_locale' => ['required', 'string', 'max:10'],
            'form_system_prompt' => ['required', 'string', 'min:5'],
            'form_user_prompt_template' => ['required', 'string', 'min:5'],
            'form_model_hint' => ['nullable', 'string', 'max:80'],
            'form_temperature_hint' => ['nullable', 'numeric', 'min:0', 'max:2'],
        ]);

        $variables = array_values(array_filter(array_map(
            'trim',
            explode(',', $this->form_variables_csv),
        )));

        try {
            $payload = [
                'system_prompt' => $this->form_system_prompt,
                'user_prompt_template' => $this->form_user_prompt_template,
                'variables' => $variables,
                'model_hint' => $this->form_model_hint !== '' ? $this->form_model_hint : null,
                'temperature_hint' => $this->form_temperature_hint,
                'is_active' => $this->form_is_active,
            ];

            if ($this->editingId !== null) {
                // Bump version — never mutate an existing row.
                $existing = AIPromptTemplate::query()->findOrFail($this->editingId);
                $existing->bumpVersion(array_merge($payload, [
                    'created_by' => auth()->id(),
                ]));
                $this->dispatchSuccessToast('New version saved.');
            } else {
                // New (key, locale) — version 1.
                $existingForKey = AIPromptTemplate::query()
                    ->where('key', $this->form_key)
                    ->where('locale', $this->form_locale)
                    ->exists();

                if ($existingForKey) {
                    $this->addError('form_key', 'A template with this key + locale already exists. Use Edit to create a new version.');

                    return;
                }

                AIPromptTemplate::query()->create(array_merge($payload, [
                    'key' => $this->form_key,
                    'locale' => $this->form_locale,
                    'version' => 1,
                    'created_by' => auth()->id(),
                ]));
                $this->dispatchSuccessToast('Template created.');
            }

            $this->editorOpen = false;
            $this->resetForm();
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast('Save failed: '.$e->getMessage());
        }
    }

    /**
     * Flip is_active on a row. Only one active per (key, locale) — the
     * UI shows a confirm if another active row exists.
     */
    public function toggleActive(int $id): void
    {
        $this->authorize('ai.templates');

        $tpl = AIPromptTemplate::query()->findOrFail($id);

        if (! $tpl->is_active) {
            // Deactivate other active rows in the same (key, locale).
            AIPromptTemplate::query()
                ->where('key', $tpl->key)
                ->where('locale', $tpl->locale)
                ->where('id', '!=', $tpl->id)
                ->update(['is_active' => false]);
        }

        $tpl->is_active = ! $tpl->is_active;
        $tpl->save();

        $this->dispatchSuccessToast($tpl->is_active ? 'Activated.' : 'Deactivated.');
    }

    /**
     * Soft-style delete by deactivation. Hard delete is only allowed
     * for templates with no usage logs referencing them.
     */
    public function delete(int $id): void
    {
        $this->authorize('ai.templates');

        $tpl = AIPromptTemplate::query()->findOrFail($id);

        $hasUsage = \App\Models\AIUsageLog::query()
            ->where('prompt_template_key', $tpl->key)
            ->where('prompt_template_version', $tpl->version)
            ->exists();

        if ($hasUsage) {
            // Preserve history — just deactivate.
            $tpl->is_active = false;
            $tpl->save();
            $this->dispatchSuccessToast('Template deactivated (kept for usage-log reference).');

            return;
        }

        $tpl->delete();
        $this->dispatchSuccessToast('Template deleted.');
    }

    public function render(): View
    {
        return view('livewire.admin.ai.prompt-templates');
    }

    protected function dispatchSuccessToast(string $message): void
    {
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        $this->dispatch('toast.danger', message: $message);
    }
}
