<?php

declare(strict_types=1);

use App\Actions\AI\TranslateContentAction;
use App\Livewire\Admin\Posts\Edit;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->state([
        'code' => 'bn',
        'name' => 'Bangla',
        'flag_emoji' => '🇧🇩',
        'is_active' => true,
    ])->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function ttUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('mount loads every existing translation into the dictionary', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();

    // The factory created the English translation. Add a Bangla one.
    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'language_id' => $this->bangla->id,
        'title' => 'বাংলা শিরোনাম',
        'content' => '<p>বাংলা কনটেন্ট</p>',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post->fresh()]);

    expect($component->get('translations'))->toHaveKey($this->english->id);
    expect($component->get('translations'))->toHaveKey($this->bangla->id);
    expect($component->get('activeLanguageId'))->toBe($this->english->id);
});

test('switching language tabs swaps the scalar editor contents', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();
    $post->translations()->first()->update(['title' => 'English title', 'content' => '<p>English body</p>']);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'language_id' => $this->bangla->id,
        'title' => 'বাংলা শিরোনাম',
        'content' => '<p>বাংলা বডি</p>',
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post->fresh()])
        ->assertSet('title', 'English title')
        ->call('switchLanguage', $this->bangla->id)
        ->assertSet('title', 'বাংলা শিরোনাম')
        ->assertSet('content', '<p>বাংলা বডি</p>')
        ->assertSet('activeLanguageId', $this->bangla->id);
});

test('addTranslation creates a blank tab and makes it active', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();   // English only

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('addTranslation', $this->bangla->id)
        ->assertSet('activeLanguageId', $this->bangla->id)
        ->assertSet('title', '')
        ->assertSet('content', '');
});

test('save persists translations for every language in the dictionary', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();
    $post->translations()->first()->update(['title' => 'English title', 'content' => '<p>English body</p>']);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('addTranslation', $this->bangla->id)
        ->set('title', 'নতুন বাংলা শিরোনাম')
        ->set('content', '<p>নতুন বাংলা বডি</p>')
        ->call('save');

    $post->refresh();
    $bn = $post->translations()->where('language_id', $this->bangla->id)->first();
    expect($bn)->not->toBeNull();
    expect($bn->title)->toBe('নতুন বাংলা শিরোনাম');
    expect($bn->content)->toBe('<p>নতুন বাংলা বডি</p>');

    $en = $post->translations()->where('language_id', $this->english->id)->first();
    expect($en->title)->toBe('English title');  // untouched
});

test('removeTranslation marks the tab for deletion and Save drops the row', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'language_id' => $this->bangla->id,
        'title' => 'temp',
        'content' => '<p>temp</p>',
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post->fresh()])
        ->call('removeTranslation', $this->bangla->id)
        ->call('save');

    expect($post->fresh()->translations()->where('language_id', $this->bangla->id)->exists())
        ->toBeFalse();
});

test('removeTranslation refuses to remove the default language', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('removeTranslation', $this->english->id);

    expect($post->fresh()->translations()->where('language_id', $this->english->id)->exists())
        ->toBeTrue();
});

test('translationTabs computed reflects active state and completion percent', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();
    $post->translations()->first()->update([
        'title' => 'Full English Title',
        'content' => '<p>Full English Body</p>',
        'excerpt' => 'short excerpt',
        'meta_title' => 'meta',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'language_id' => $this->bangla->id,
        'title' => '',
        'content' => null,
        'excerpt' => null,
        'meta_title' => null,
    ]);

    $component = Livewire::actingAs($admin)->test(Edit::class, ['post' => $post->fresh()]);
    $tabs = $component->instance()->translationTabs;

    $byId = collect($tabs)->keyBy('id');

    expect($byId[$this->english->id]['percent'])->toBe(100);
    expect($byId[$this->english->id]['is_default'])->toBeTrue();
    expect($byId[$this->english->id]['active'])->toBeTrue();

    expect($byId[$this->bangla->id]['percent'])->toBe(0);
    expect($byId[$this->bangla->id]['is_default'])->toBeFalse();
});

test('AI translate refuses on the default tab and prompts switching', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('translateActiveFromDefault')
        ->assertDispatched('toast.danger', fn (string $event, array $params) =>
            str_contains((string) ($params['message'] ?? ''), 'non-default language')
        );
});

test('AI translate populates active tab content from default language via TranslateContentAction', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();
    $post->translations()->first()->update([
        'title' => 'AI Marketing',
        'content' => '<p>The future of marketing is AI.</p>',
    ]);

    $mock = mock(TranslateContentAction::class);
    $mock->shouldReceive('handle')
        ->andReturnUsing(fn (string $article) => 'TRANSLATED::'.$article);
    $this->app->instance(TranslateContentAction::class, $mock);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('addTranslation', $this->bangla->id)
        ->call('translateActiveFromDefault')
        ->assertSet('title', 'TRANSLATED::AI Marketing')
        ->assertSet('content', 'TRANSLATED::<p>The future of marketing is AI.</p>');
});

test('translation_status flips to ai_generated after AI translate and persists on save', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();
    $post->translations()->first()->update([
        'title' => 'Hello',
        'content' => '<p>Hello world.</p>',
    ]);

    $mock = mock(TranslateContentAction::class);
    $mock->shouldReceive('handle')->andReturn('translated');
    $this->app->instance(TranslateContentAction::class, $mock);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('addTranslation', $this->bangla->id)
        ->call('translateActiveFromDefault')
        ->call('save');

    $bn = $post->fresh()->translations()->where('language_id', $this->bangla->id)->first();
    expect($bn->translation_status)->toBe(PostTranslation::TRANSLATION_STATUS_AI_GENERATED);
});

test('languagesAvailableToAdd hides languages already covered', function (): void {
    $admin = ttUser();
    $post = Post::factory()->draft()->create();   // English only

    $component = Livewire::actingAs($admin)->test(Edit::class, ['post' => $post]);
    $available = $component->instance()->languagesAvailableToAdd;

    expect($available->pluck('id'))->not->toContain($this->english->id);
    expect($available->pluck('id'))->toContain($this->bangla->id);

    $component->call('addTranslation', $this->bangla->id);
    expect($component->instance()->languagesAvailableToAdd->pluck('id'))->not->toContain($this->bangla->id);
});
