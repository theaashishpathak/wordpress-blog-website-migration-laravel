<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Highlights;

use App\Actions\Visitor\Highlight\DeleteHighlightAction;
use App\Actions\Visitor\Highlight\UpdateHighlightNoteAction;
use App\Models\Highlight;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('My Highlights')]
class Index extends Component
{
    use WithPagination;

    /** Note being edited. Keyed by highlight id. */
    public ?int $editingId = null;

    public string $editingNote = '';

    /**
     * @return LengthAwarePaginator<Highlight>
     */
    #[Computed]
    public function highlights(): LengthAwarePaginator
    {
        return Highlight::query()
            ->where('user_id', auth()->id())
            ->with([
                'post.translations',
                'post.author:id,name',
                'post.category.translations',
            ])
            ->latest()
            ->paginate(15);
    }

    public function startEditingNote(int $id): void
    {
        $highlight = $this->loadOwned($id);
        $this->editingId = $highlight->id;
        $this->editingNote = (string) $highlight->note;
    }

    public function cancelEditingNote(): void
    {
        $this->editingId = null;
        $this->editingNote = '';
    }

    public function saveNote(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $highlight = $this->loadOwned($this->editingId);
        app(UpdateHighlightNoteAction::class)->handle($highlight, $this->editingNote);

        $this->cancelEditingNote();
        unset($this->highlights);
        $this->dispatch('toast', message: 'Note saved');
    }

    public function delete(int $id): void
    {
        $highlight = $this->loadOwned($id);
        app(DeleteHighlightAction::class)->handle($highlight);

        unset($this->highlights);
        $this->dispatch('toast', message: 'Highlight deleted');
    }

    private function loadOwned(int $id): Highlight
    {
        return Highlight::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.visitor.highlights.index');
    }
}
