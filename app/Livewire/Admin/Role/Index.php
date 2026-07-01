<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Role;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Roles')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        Role::query()->where('guard_name', 'web')->whereKey($id)->delete();

        session()->flash('success', 'Role deleted successfully.');
        $this->resetPage();
    }

    public function render(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->where('guard_name', 'web')
            ->when($this->search !== '', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.role.index', [
            'roles' => $roles,
        ]);
    }
}
