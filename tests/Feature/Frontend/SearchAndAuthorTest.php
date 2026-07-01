<?php

declare(strict_types=1);

use App\Livewire\Frontend\AuthorShow;
use App\Livewire\Frontend\Search;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

// -------------------------------------------------------------------------
// Search
// -------------------------------------------------------------------------

test('search returns empty result set when query is empty', function (): void {
    Post::factory()->count(3)->published()->create();

    $component = Livewire::test(Search::class)->set('query', '');

    expect($component->instance()->results->total())->toBe(0);
});

test('search returns posts matching title term', function (): void {
    $hit = Post::factory()->published()->create();
    $hit->translations()->first()->update(['title' => 'AI Marketing Trends 2026']);

    $miss = Post::factory()->published()->create();
    $miss->translations()->first()->update(['title' => 'Quantum Computing']);

    $component = Livewire::test(Search::class)->set('query', 'AI Marketing');

    expect($component->instance()->results->total())->toBe(1);
    expect($component->instance()->results->first()->id)->toBe($hit->id);
});

test('search ignores draft posts', function (): void {
    $draft = Post::factory()->draft()->create();
    $draft->translations()->first()->update(['title' => 'Hidden article about AI']);

    $component = Livewire::test(Search::class)->set('query', 'AI');

    expect($component->instance()->results->total())->toBe(0);
});

test('search matches on excerpt + content too', function (): void {
    $post = Post::factory()->published()->create();
    $post->translations()->first()->update([
        'title' => 'Unrelated headline',
        'excerpt' => 'The future of machine learning is here.',
    ]);

    $component = Livewire::test(Search::class)->set('query', 'machine learning');

    expect($component->instance()->results->pluck('id'))->toContain($post->id);
});

test('updating query resets pagination', function (): void {
    Post::factory()->count(20)->published()->create();
    Post::query()
        ->limit(20)
        ->get()
        ->each(fn ($p) => $p->translations()->first()->update(['title' => 'AI '.$p->id]));

    $component = Livewire::test(Search::class)
        ->set('query', 'AI')
        ->call('gotoPage', 2)
        ->set('query', 'AI 1');     // narrower term

    // page should reset to 1 — first result should appear
    expect($component->instance()->results->currentPage())->toBe(1);
});

// -------------------------------------------------------------------------
// AuthorShow
// -------------------------------------------------------------------------

test('author-show lists published posts by the author', function (): void {
    $author = User::factory()->create();
    $mine = Post::factory()->published()->state(['author_id' => $author->id])->create();
    $theirs = Post::factory()->published()->create();

    $component = Livewire::test(AuthorShow::class, ['author' => $author->fresh()]);

    expect($component->instance()->posts->pluck('id'))->toContain($mine->id);
    expect($component->instance()->posts->pluck('id'))->not->toContain($theirs->id);
});

test('author-show excludes drafts', function (): void {
    $author = User::factory()->create();
    Post::factory()->draft()->state(['author_id' => $author->id])->create();

    expect(Livewire::test(AuthorShow::class, ['author' => $author])
        ->instance()->posts->total())
        ->toBe(0);
});

test('author-show renders the author name', function (): void {
    $author = User::factory()->create(['name' => 'Jane Reporter']);

    Livewire::test(AuthorShow::class, ['author' => $author])
        ->assertOk()
        ->assertSee('Jane Reporter');
});
