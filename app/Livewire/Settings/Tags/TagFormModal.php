<?php

namespace App\Livewire\Settings\Tags;

use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class TagFormModal extends Component
{
    public ?int $tagId = null;

    public bool $open = false;

    public string $name = '';

    public string $slug = '';

    public string $color = '#6366f1';

    public string $type = Tag::TYPE_GENERAL;

    public string $status = Tag::STATUS_PUBLISHED;

    public bool $slugManuallyEdited = false;

    public function mount(?int $tagId = null, bool $open = false): void
    {
        $this->open = $open;
        $this->fillForm($tagId);
    }

    public function updatedTagId(?int $tagId): void
    {
        $this->fillForm($tagId);
    }

    public function updatedName(string $value): void
    {
        if ($this->slugManuallyEdited) {
            return;
        }

        $this->slug = Str::slug($value);
    }

    public function updatedSlug(string $value): void
    {
        $this->slugManuallyEdited = $value !== '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'slug')->ignore($this->tagId),
            ],
            'color' => ['required', 'string', 'max:32'],
            'type' => ['required', Rule::in([Tag::TYPE_GENERAL])],
            'status' => ['required', Rule::in([Tag::STATUS_PUBLISHED, Tag::STATUS_UNPUBLISHED])],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $userId = (int) auth()->id();
        $isCreating = $this->tagId === null;

        try {
            if ($isCreating) {
                Tag::query()->create([
                    ...$validated,
                    'code' => $this->generateCode(),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            } else {
                Tag::query()->whereKey($this->tagId)->update([
                    ...$validated,
                    'updated_by' => $userId,
                ]);
            }

            session()->flash('success', $isCreating ? 'Tag created successfully.' : 'Tag updated successfully.');
            $this->dispatch('toast', message: $isCreating ? 'Tag created successfully.' : 'Tag updated successfully.', type: 'success');
            $this->dispatch('tag-saved');
        } catch (Throwable $exception) {
            report($exception);
            $message = $isCreating ? 'Failed to create tag.' : 'Failed to update tag.';
            session()->flash('danger', $message);
            $this->dispatch('toast', message: $message, type: 'danger');
        }
    }

    public function cancel(): void
    {
        $this->dispatch('tag-form-cancelled');
    }

    protected function fillForm(?int $tagId): void
    {
        $this->tagId = $tagId;
        $this->slugManuallyEdited = false;

        if ($tagId === null) {
            $this->reset(['name', 'slug']);
            $this->color = '#6366f1';
            $this->type = Tag::TYPE_GENERAL;
            $this->status = Tag::STATUS_PUBLISHED;
            $this->resetValidation();

            return;
        }

        $tag = Tag::query()->findOrFail($tagId);

        $this->name = $tag->name;
        $this->slug = $tag->slug;
        $this->color = $tag->color;
        $this->type = $tag->type;
        $this->status = $tag->status;
        $this->slugManuallyEdited = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.settings.tags.tag-form-modal');
    }

    protected function generateCode(): string
    {
        $highestNumericCode = Tag::query()
            ->pluck('code')
            ->map(static function (string $code): int {
                return preg_match('/^\d+$/', $code) === 1 ? (int) $code : 0;
            })
            ->max();

        $nextCode = ((int) $highestNumericCode) + 1;

        return str_pad((string) $nextCode, 4, '0', STR_PAD_LEFT);
    }
}
