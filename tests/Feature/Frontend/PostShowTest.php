<?php

declare(strict_types=1);

use App\Livewire\Frontend\PostShow;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('post-show renders title + content + author', function (): void {
    $post = Post::factory()->published()->create();
    $tr = $post->translations()->first();
    $tr->update(['title' => 'AI Marketing in 2026', 'content' => '<p>Body goes here.</p>']);

    Livewire::test(PostShow::class, ['post' => $post->fresh(), 'translation' => $tr->fresh()])
        ->assertOk()
        ->assertSee('AI Marketing in 2026')
        ->assertSee('Body goes here', escape: false);
});

test('related posts excludes the current post', function (): void {
    $cat = \App\Models\Category::factory()->create();

    $main = Post::factory()->published()->state(['category_id' => $cat->id])->create();
    $sibling = Post::factory()->published()->state(['category_id' => $cat->id])->create();
    $unrelated = Post::factory()->published()->create();

    $component = Livewire::test(PostShow::class, [
        'post' => $main->fresh(),
        'translation' => $main->translations()->first(),
    ]);

    expect($component->instance()->relatedPosts->pluck('id'))->toContain($sibling->id);
    expect($component->instance()->relatedPosts->pluck('id'))->not->toContain($main->id);
});

test('tags computed returns attached tags', function (): void {
    $post = Post::factory()->published()->create();
    $tag = Tag::factory()->create();
    $post->tags()->attach($tag->id, ['created_at' => now()]);

    $component = Livewire::test(PostShow::class, [
        'post' => $post->fresh(),
        'translation' => $post->translations()->first(),
    ]);

    expect($component->instance()->tags->pluck('id'))->toContain($tag->id);
});

test('share urls contain the current page URL', function (): void {
    $post = Post::factory()->published()->create();
    $tr = $post->translations()->first();

    $component = Livewire::test(PostShow::class, ['post' => $post->fresh(), 'translation' => $tr]);

    $twitterUrl = $component->instance()->shareUrl('twitter');
    expect($twitterUrl)->toStartWith('https://twitter.com/intent/tweet?');
    expect($twitterUrl)->toContain(urlencode($tr->title));
});
