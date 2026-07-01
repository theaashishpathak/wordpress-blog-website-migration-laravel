<?php

namespace App\Livewire\Admin\Staff;

use App\Models\ProfileActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Staff Profile')]
class Show extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user->load(['department:id,name', 'manager:id,name', 'roles:id,name']);
    }

    public function render(): View
    {
        return view('livewire.admin.staff.show', [
            'subordinates' => User::query()
                ->where('manager_id', $this->user->id)
                ->where('portal_type', '!=', 'visitor')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'job_title']),
            'recentActivity' => ProfileActivityLog::query()
                ->where('user_id', $this->user->id)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
