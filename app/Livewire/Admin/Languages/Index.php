<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Languages;

use App\Actions\Language\CreateLanguageAction;
use App\Actions\Language\DeleteLanguageAction;
use App\Actions\Language\UpdateLanguageAction;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Languages')]
class Index extends Component
{
    // --- Modal form state -----------------------------------------------------

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $nativeName = '';

    public string $flagEmoji = '';

    public string $direction = Language::DIRECTION_LTR;

    public bool $isDefault = false;

    public bool $isActive = true;

    public bool $isAdminLocale = false;

    public int $sortOrder = 0;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('languages.view') ?? false,
            403,
            'You do not have access to manage languages.',
        );
    }

    // --- List computed --------------------------------------------------------

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::query()->ordered()->get();
    }

    // --- Inline toggles -------------------------------------------------------

    public function toggleActive(int $languageId, UpdateLanguageAction $update): void
    {
        $this->authorize('languages.toggle');

        $language = Language::query()->findOrFail($languageId);

        if ($language->is_default && $language->is_active) {
            $this->dispatchDangerToast('Cannot deactivate the default language.');

            return;
        }

        try {
            $update->handle($language, ['is_active' => ! $language->is_active]);
            $this->dispatchSuccessToast(
                $language->is_active ? "{$language->name} deactivated." : "{$language->name} activated."
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Toggle failed: '.$exception->getMessage());
        }
    }

    public function makeDefault(int $languageId, UpdateLanguageAction $update): void
    {
        $this->authorize('languages.edit');

        $language = Language::query()->findOrFail($languageId);

        if ($language->is_default) {
            return;
        }

        try {
            $update->handle($language, ['is_default' => true, 'is_active' => true]);
            $this->dispatchSuccessToast("{$language->name} is now the default language.");
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Set default failed: '.$exception->getMessage());
        }
    }

    public function deleteLanguage(int $languageId, DeleteLanguageAction $delete): void
    {
        $this->authorize('languages.delete');

        $language = Language::query()->findOrFail($languageId);

        try {
            $delete->handle($language);
            $this->dispatchSuccessToast("Language deleted.");
        } catch (Throwable $exception) {
            $this->dispatchDangerToast($exception->getMessage());
        }
    }

    // --- Form (create + edit) -------------------------------------------------

    public function newLanguage(): void
    {
        $this->authorize('languages.create');

        $this->resetForm();
        $this->showForm = true;
    }

    public function editLanguage(int $languageId): void
    {
        $this->authorize('languages.edit');

        $language = Language::query()->findOrFail($languageId);

        $this->editingId = $language->id;
        $this->code = (string) $language->code;
        $this->name = (string) $language->name;
        $this->nativeName = (string) ($language->native_name ?? '');
        $this->flagEmoji = (string) ($language->flag_emoji ?? '');
        $this->direction = (string) ($language->direction ?? Language::DIRECTION_LTR);
        $this->isDefault = (bool) $language->is_default;
        $this->isActive = (bool) $language->is_active;
        $this->isAdminLocale = (bool) $language->is_admin_locale;
        $this->sortOrder = (int) ($language->sort_order ?? 0);
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(CreateLanguageAction $create, UpdateLanguageAction $update): void
    {
        $this->validate($this->rules());

        try {
            $payload = [
                'code' => $this->code,
                'name' => $this->name,
                'native_name' => $this->nativeName,
                'flag_emoji' => $this->flagEmoji,
                'direction' => $this->direction,
                'is_default' => $this->isDefault,
                'is_active' => $this->isActive,
                'is_admin_locale' => $this->isAdminLocale,
                'sort_order' => $this->sortOrder,
            ];

            if ($this->editingId === null) {
                $this->authorize('languages.create');
                $create->handle($payload);
                $this->dispatchSuccessToast("Language '{$this->name}' created.");
            } else {
                $this->authorize('languages.edit');
                $language = Language::query()->findOrFail($this->editingId);
                $update->handle($language, $payload);
                $this->dispatchSuccessToast("Language '{$this->name}' updated.");
            }

            $this->cancelForm();
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast($exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $codeRule = ['required', 'string', 'min:2', 'max:10'];

        $codeRule[] = $this->editingId === null
            ? 'unique:languages,code'
            : 'unique:languages,code,'.$this->editingId;

        return [
            'code' => $codeRule,
            'name' => ['required', 'string', 'max:100'],
            'nativeName' => ['nullable', 'string', 'max:100'],
            'flagEmoji' => ['nullable', 'string', 'max:10'],
            'direction' => ['required', \Illuminate\Validation\Rule::in(Language::DIRECTIONS)],
            'sortOrder' => ['integer', 'min:0', 'max:1000'],
        ];
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->nativeName = '';
        $this->flagEmoji = '';
        $this->direction = Language::DIRECTION_LTR;
        $this->isDefault = false;
        $this->isActive = true;
        $this->isAdminLocale = false;
        $this->sortOrder = 0;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.languages.index');
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
