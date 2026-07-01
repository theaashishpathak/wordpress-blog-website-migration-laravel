<?php

namespace App\Livewire\Admin\PermissionGroup;

use App\Models\PermissionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $permissionGroupId = null;

    public string $name = '';

    public string $slug = '';

    public function mount(?int $permissionGroupId = null): void
    {
        $this->permissionGroupId = $permissionGroupId;

        if ($permissionGroupId === null) {
            return;
        }

        $permissionGroup = PermissionGroup::query()->findOrFail($permissionGroupId);

        $this->name = $permissionGroup->name;
        $this->slug = $permissionGroup->slug;
    }

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
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
                Rule::unique('permission_groups', 'slug')->ignore($this->permissionGroupId),
            ],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->permissionGroupId === null) {
            PermissionGroup::query()->create($validated);
        } else {
            PermissionGroup::query()->whereKey($this->permissionGroupId)->update($validated);
        }

        session()->flash('success', 'Permission group saved successfully.');
        $this->dispatch('permission-group-saved');
    }

    public function cancel(): void
    {
        $this->dispatch('permission-group-cancelled');
    }

    public function render(): View
    {
        return view('livewire.admin.permission-group.form');
    }
}
