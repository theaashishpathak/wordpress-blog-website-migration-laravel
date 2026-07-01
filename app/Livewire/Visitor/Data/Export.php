<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Data;

use App\Actions\Visitor\Data\RequestDataExportAction;
use App\Models\DataExportRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.visitor')]
#[Title('Export My Data')]
class Export extends Component
{
    /**
     * @return Collection<int, DataExportRequest>
     */
    #[Computed]
    public function requests(): Collection
    {
        return DataExportRequest::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();
    }

    public function requestExport(): void
    {
        try {
            app(RequestDataExportAction::class)->handle(auth()->user());
            unset($this->requests);
            $this->dispatch('toast', message: 'Export started — we will notify you when it is ready.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: $e->validator->errors()->first() ?: 'Cannot start a new export right now.');
        }
    }

    public function render(): View
    {
        return view('livewire.visitor.data.export');
    }
}
