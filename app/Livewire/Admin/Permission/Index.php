<?php

namespace App\Livewire\Admin\Permission;

use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Permission')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingPermissionId = null;

    public int $formKey = 0;

    public bool $showModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->editingPermissionId = null;
        $this->formKey++;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->editingPermissionId = $id;
        $this->formKey++;
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->editingPermissionId = null;
    }

    public function delete(int $id): void
    {
        Permission::query()->whereKey($id)->delete();

        session()->flash('success', 'Permission deleted successfully.');
        $this->resetPage();
    }

    #[On('permission-saved')]
    #[On('permission-cancelled')]
    public function refreshList(): void
    {
        $this->editingPermissionId = null;
        $this->formKey++;
        $this->showModal = false;
        $this->resetPage();
    }

    public function render(): View
    {
        $permissions = Permission::query()
            ->with('permissionGroup')
            ->where('guard_name', 'web')
            ->when($this->search !== '', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.permissions.index', [
            'permissions' => $permissions,
        ]);
    }
}
