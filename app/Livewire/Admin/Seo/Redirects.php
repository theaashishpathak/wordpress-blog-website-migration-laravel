<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Seo;

use App\Models\Redirect as RedirectModel;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * SEO Tools — Redirects manager.
 *
 * CRUD over the `redirects` table. The HandleRedirects middleware (out
 * of scope here) consumes `from_path` lookups on every request. We
 * normalise input so admins can type bare paths without a leading slash.
 */
#[Layout('layouts.app')]
#[Title('Redirects')]
class Redirects extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $activeOnly = false;

    public bool $editorOpen = false;

    public ?int $editingId = null;

    public string $form_from_path = '';

    public string $form_to_url = '';

    public int $form_status_code = 301;

    public bool $form_is_active = true;

    public bool $form_preserve_query = true;

    public string $form_notes = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('seo.redirects') ?? false,
            403,
            'You do not have access to manage redirects.',
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<RedirectModel>
     */
    #[Computed]
    public function redirects(): LengthAwarePaginator
    {
        return RedirectModel::query()
            ->when($this->search !== '', function ($q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($qq) use ($term): void {
                    $qq->where('from_path', 'like', $term)->orWhere('to_url', 'like', $term);
                });
            })
            ->when($this->activeOnly, fn ($q) => $q->where('is_active', true))
            ->latest()
            ->paginate(20);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editorOpen = true;
    }

    public function edit(int $id): void
    {
        $r = RedirectModel::query()->findOrFail($id);

        $this->editingId = $r->id;
        $this->form_from_path = $r->from_path;
        $this->form_to_url = $r->to_url;
        $this->form_status_code = $r->status_code;
        $this->form_is_active = (bool) $r->is_active;
        $this->form_preserve_query = (bool) $r->preserve_query;
        $this->form_notes = (string) ($r->notes ?? '');

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
        $this->form_from_path = '';
        $this->form_to_url = '';
        $this->form_status_code = 301;
        $this->form_is_active = true;
        $this->form_preserve_query = true;
        $this->form_notes = '';
    }

    public function save(): void
    {
        $this->authorize('seo.redirects');

        $this->validate([
            'form_from_path' => ['required', 'string', 'max:500'],
            'form_to_url' => ['required', 'string', 'max:1000'],
            'form_status_code' => ['required', 'integer', \Illuminate\Validation\Rule::in(RedirectModel::STATUS_CODES)],
        ]);

        // Normalise: always store with leading slash and no trailing slash
        // (root path "/" stays as "/").
        $from = '/'.ltrim(trim($this->form_from_path), '/');
        if ($from !== '/' && Str::endsWith($from, '/')) {
            $from = rtrim($from, '/');
        }

        try {
            $payload = [
                'from_path' => $from,
                'to_url' => trim($this->form_to_url),
                'status_code' => $this->form_status_code,
                'is_active' => $this->form_is_active,
                'preserve_query' => $this->form_preserve_query,
                'notes' => $this->form_notes !== '' ? $this->form_notes : null,
                'updated_by' => (int) auth()->id(),
            ];

            if ($this->editingId !== null) {
                RedirectModel::query()->findOrFail($this->editingId)->update($payload);
                $this->dispatch('toast.success', message: 'Redirect updated.');
            } else {
                if (RedirectModel::query()->where('from_path', $from)->exists()) {
                    $this->addError('form_from_path', 'A redirect rule already exists for this path.');

                    return;
                }
                $payload['created_by'] = (int) auth()->id();
                RedirectModel::query()->create($payload);
                $this->dispatch('toast.success', message: 'Redirect created.');
            }

            $this->closeEditor();
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast.danger', message: 'Save failed: '.$e->getMessage());
        }
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('seo.redirects');
        $r = RedirectModel::query()->findOrFail($id);
        $r->is_active = ! $r->is_active;
        $r->save();
    }

    public function delete(int $id): void
    {
        $this->authorize('seo.redirects');
        RedirectModel::query()->findOrFail($id)->delete();
        $this->dispatch('toast.success', message: 'Redirect removed.');
    }

    public function render(): View
    {
        return view('livewire.admin.seo.redirects');
    }
}
