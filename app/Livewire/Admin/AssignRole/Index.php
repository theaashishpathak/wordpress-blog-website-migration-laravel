<?php

namespace App\Livewire\Admin\AssignRole;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Assign Role')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingUserId = null;

    public string $editingUserName = '';

    public string $editingUserEmail = '';

    public bool $showModal = false;

    /**
     * @var array<int, int>
     */
    public array $selectedRoles = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function editUser(int $id): void
    {
        $user = User::query()->with('roles')->findOrFail($id);

        $this->editingUserId = $id;
        $this->editingUserName = $user->name;
        $this->editingUserEmail = $user->email;
        $this->selectedRoles = $user->roles->pluck('id')->map(fn ($roleId) => (int) $roleId)->all();
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->editingUserId = null;
        $this->editingUserName = '';
        $this->editingUserEmail = '';
        $this->selectedRoles = [];
    }

    public function saveRoles(): void
    {
        $this->validate([
            'selectedRoles' => ['array'],
            'selectedRoles.*' => [
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
        ]);

        $user = User::query()->findOrFail($this->editingUserId);

        $roleIds = collect($this->selectedRoles)
            ->map(fn ($roleId) => (int) $roleId)
            ->filter(fn (int $roleId) => $roleId > 0)
            ->unique()
            ->values();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $roleIds)
            ->get();

        $user->syncRoles($roles);

        session()->flash('success', 'User roles updated successfully.');
        $this->close();
    }

    public function cancelEdit(): void
    {
        $this->close();
    }

    public function render(): View
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        // Roles list is only needed when the modal is open. Skipping the
        // query while the modal is closed makes the search/pagination
        // re-render path significantly faster — every keystroke used to
        // hit the roles table even though the result was never displayed.
        $roles = $this->showModal
            ? Role::query()->where('guard_name', 'web')->orderBy('name')->get()
            : collect();

        return view('livewire.admin.assign-role.index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}
