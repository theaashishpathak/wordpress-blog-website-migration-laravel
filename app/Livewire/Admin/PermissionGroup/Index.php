<?php

namespace App\Livewire\Admin\PermissionGroup;

use App\Models\PermissionGroup;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Permission Group')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingPermissionGroupId = null;

    public int $formKey = 0;

    public bool $showModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->editingPermissionGroupId = null;
        $this->formKey++;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->editingPermissionGroupId = $id;
        $this->formKey++;
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->editingPermissionGroupId = null;
    }

    public function delete(int $id): void
    {
        PermissionGroup::query()->whereKey($id)->delete();

        session()->flash('success', 'Permission group deleted successfully.');
        $this->resetPage();
    }

    #[On('permission-group-saved')]
    #[On('permission-group-cancelled')]
    public function refreshList(): void
    {
        $this->editingPermissionGroupId = null;
        $this->formKey++;
        $this->showModal = false;
        $this->resetPage();
    }

    public function render(): View
    {
        $permissionGroups = PermissionGroup::query()
            ->when($this->search !== '', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.permission-group.index', [
            'permissionGroups' => $permissionGroups,
        ]);
    }
}
