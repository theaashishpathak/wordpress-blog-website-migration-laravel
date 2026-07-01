<?php

declare(strict_types=1);

use App\Livewire\Frontend\Home;
use App\Models\Language;
use App\Models\Post;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('home renders for an anonymous visitor', function (): void {
    Livewire::test(Home::class)->assertOk();
});

test('home shows only published posts in the latest grid', function (): void {
    $published = Post::factory()->published()->create();
    $published->translations()->first()->update(['title' => 'Public published post']);

    $draft = Post::factory()->draft()->create();
    $draft->translations()->first()->update(['title' => 'Hidden draft post']);

    $component = Livewire::test(Home::class);

    expect($component->instance()->latest->pluck('id'))->toContain($published->id);
    expect($component->instance()->latest->pluck('id'))->not->toContain($draft->id);
});

test('home excludes posts scheduled in the future', function (): void {
    $future = Post::factory()->published()->state([
        'published_at' => now()->addDays(2),
    ])->create();

    expect(Livewire::test(Home::class)->instance()->latest->pluck('id'))->not->toContain($future->id);
});

test('breaking section surfaces is_breaking posts only', function (): void {
    $bp = Post::factory()->breaking()->create();
    $other = Post::factory()->published()->state(['is_breaking' => false])->create();

    $component = Livewire::test(Home::class);

    expect($component->instance()->breaking->pluck('id'))->toContain($bp->id);
    expect($component->instance()->breaking->pluck('id'))->not->toContain($other->id);
});

test('breaking section excludes posts whose breaking_expires_at has passed', function (): void {
    $expired = Post::factory()->published()->state([
        'is_breaking' => true,
        'breaking_expires_at' => now()->subHour(),
    ])->create();

    expect(Livewire::test(Home::class)->instance()->breaking->pluck('id'))->not->toContain($expired->id);
});

test('featured section surfaces is_featured posts', function (): void {
    $f = Post::factory()->published()->featured()->create();

    expect(Livewire::test(Home::class)->instance()->featured->pluck('id'))->toContain($f->id);
});

test('trending section surfaces is_trending posts', function (): void {
    $t = Post::factory()->published()->trending()->create();

    expect(Livewire::test(Home::class)->instance()->trending->pluck('id'))->toContain($t->id);
});

test('home renders empty-state when no posts exist', function (): void {
    Livewire::test(Home::class)
        ->assertOk()
        ->assertSee('No posts published yet');
});
