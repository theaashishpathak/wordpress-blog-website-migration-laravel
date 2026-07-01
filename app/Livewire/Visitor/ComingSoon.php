<?php

declare(strict_types=1);

namespace App\Livewire\Visitor;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Visitor portal placeholder. Every sidebar menu item that hasn't been
 * built yet routes here. Each instance is parameterised via mount() so
 * the title, icon, and description can match the menu item the user
 * clicked. Phases V2–V9 will replace these with real components.
 */
#[Layout('layouts.visitor')]
#[Title('Coming Soon')]
class ComingSoon extends Component
{
    public string $section = 'Feature';

    public string $icon = 'sparkles';

    public string $description = 'This area is on the roadmap and lands in an upcoming phase.';

    public function mount(string $section = 'Feature', string $icon = 'sparkles', string $description = ''): void
    {
        $this->section = $section;
        $this->icon = $icon;

        if ($description !== '') {
            $this->description = $description;
        }
    }

    public function render(): View
    {
        return view('livewire.visitor.coming-soon');
    }
}
