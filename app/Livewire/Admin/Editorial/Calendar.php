<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Editorial;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Editorial Calendar — month view of post activity.
 *
 * Each day cell shows posts whose `scheduled_at` or `published_at`
 * falls on that day, plus any drafts/pending posts created that day.
 * Color-coded by status so editors can visualise the upcoming pipeline.
 */
#[Layout('layouts.app')]
#[Title('Editorial Calendar')]
class Calendar extends Component
{
    #[Url(as: 'm')]
    public string $month = '';   // YYYY-MM, defaults to current month

    #[Url(as: 'author')]
    public string $authorFilter = '';

    #[Url(as: 'category')]
    public string $categoryFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('editorial.calendar') ?? false,
            403,
            'You do not have access to the editorial calendar.',
        );

        if ($this->month === '') {
            $this->month = now()->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonthNoOverflow()->format('Y-m');
    }

    public function jumpToday(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function clearFilters(): void
    {
        $this->reset(['authorFilter', 'categoryFilter', 'statusFilter']);
    }

    /**
     * @return array{first: Carbon, last: Carbon, gridStart: Carbon, gridEnd: Carbon}
     */
    #[Computed]
    public function monthRange(): array
    {
        $first = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $last = (clone $first)->endOfMonth();

        // Anchor the grid to the Sunday before the 1st so the first row
        // always starts on Sunday — keeps the calendar 7-column-clean.
        $gridStart = (clone $first)->startOfWeek(Carbon::SUNDAY);
        $gridEnd = (clone $last)->endOfWeek(Carbon::SATURDAY);

        return compact('first', 'last', 'gridStart', 'gridEnd');
    }

    /**
     * Build a day → list-of-posts map for the visible grid range.
     *
     * A post may appear in two cells (its draft creation day AND its
     * scheduled day) — that's intentional so editors can see where it
     * came from and where it's going.
     *
     * @return array<string, list<Post>>
     */
    #[Computed]
    public function postsByDay(): array
    {
        $range = $this->monthRange;
        $query = Post::query()
            ->with(['translations', 'category.translations', 'author:id,name'])
            ->where(function ($q) use ($range): void {
                $q->whereBetween('scheduled_at', [$range['gridStart'], $range['gridEnd']])
                    ->orWhereBetween('published_at', [$range['gridStart'], $range['gridEnd']])
                    ->orWhereBetween('created_at', [$range['gridStart'], $range['gridEnd']]);
            });

        if ($this->authorFilter !== '') {
            $query->where('author_id', (int) $this->authorFilter);
        }
        if ($this->categoryFilter !== '') {
            $query->where('category_id', (int) $this->categoryFilter);
        }
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $posts = $query->limit(500)->get();

        $byDay = [];
        foreach ($posts as $post) {
            $anchor = $post->scheduled_at ?? $post->published_at ?? $post->created_at;
            if ($anchor === null) {
                continue;
            }
            $key = $anchor->format('Y-m-d');
            $byDay[$key][] = $post;
        }

        return $byDay;
    }

    /**
     * Day cells for the visible grid (Sunday-anchored).
     *
     * @return list<array{date: Carbon, inMonth: bool, isToday: bool, posts: list<Post>}>
     */
    #[Computed]
    public function days(): array
    {
        $range = $this->monthRange;
        $byDay = $this->postsByDay;
        $first = $range['first'];
        $today = now()->startOfDay();

        $days = [];
        $cursor = clone $range['gridStart'];

        while ($cursor->lte($range['gridEnd'])) {
            $key = $cursor->format('Y-m-d');
            $days[] = [
                'date' => $cursor->copy(),
                'inMonth' => $cursor->month === $first->month,
                'isToday' => $cursor->startOfDay()->equalTo($today),
                'posts' => $byDay[$key] ?? [],
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function authorOptions(): Collection
    {
        $ids = Post::query()->whereNotNull('author_id')->distinct()->pluck('author_id');

        return User::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryOptions(): Collection
    {
        return Category::query()->ordered()->limit(200)->get();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return array_map(
            static fn (PostStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
            PostStatus::cases(),
        );
    }

    public function render(): View
    {
        return view('livewire.admin.editorial.calendar');
    }
}
