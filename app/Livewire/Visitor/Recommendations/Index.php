<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Recommendations;

use App\Actions\Visitor\Recommendation\BuildRecommendationsAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.visitor')]
#[Title('For You')]
class Index extends Component
{
    /**
     * @return Collection<int, \App\Models\Post>
     */
    #[Computed(persist: false)]
    public function picks(): Collection
    {
        return app(BuildRecommendationsAction::class)->handle(auth()->user(), limit: 30);
    }

    public function refreshFeed(): void
    {
        unset($this->picks);
        $this->dispatch('toast', message: 'Recommendations refreshed.');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.visitor.recommendations.index', [
            'hasSignal' => $user->readingHistory()->exists() || $user->reactions()->exists(),
        ]);
    }
}
