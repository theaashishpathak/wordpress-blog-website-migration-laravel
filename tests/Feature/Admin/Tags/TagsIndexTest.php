<?php

declare(strict_types=1);

use App\Livewire\Admin\Tags\Index;
use App\Models\Language;
use App\Models\Tag;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function tagsUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('non-Admin without tags.view is denied', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    // No role assigned → no permissions

    Livewire::actingAs($user)->test(Index::class)->assertForbidden();
});

test('admin can view the tags table', function (): void {
    $admin = tagsUser();
    Tag::factory()->create(['name' => 'Tech', 'slug' => 'tech']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertOk()
        ->assertSee('Tech');
});

test('newTag opens the modal with one row per active language', function (): void {
    $admin = tagsUser();
    Language::factory()->state(['code' => 'bn', 'name' => 'Bangla', 'is_active' => true])->create();

    $component = Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('newTag')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null);

    expect(array_keys($component->get('rows')))->toHaveCount(2);
});

test('save creates a new tag with a default-language translation', function (): void {
    $admin = tagsUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('newTag')
        ->set("rows.{$this->english->id}.name", 'AI Marketing')
        ->call('save')
        ->assertSet('showForm', false);

    $tag = Tag::query()->latest('id')->first();
    expect($tag)->not->toBeNull();
    expect($tag->translate('name'))->toBe('AI Marketing');
});

test('save refuses to create a tag without any translation', function (): void {
    $admin = tagsUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('newTag')
        ->call('save')
        ->assertDispatched('toast.danger');

    expect(Tag::query()->count())->toBe(0);
});

test('editTag hydrates the form with translations', function (): void {
    $admin = tagsUser();
    $tag = Tag::factory()->create(['name' => 'Tech', 'slug' => 'tech']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('editTag', $tag->id)
        ->assertSet('editingId', $tag->id)
        ->assertSet("rows.{$this->english->id}.name", 'Tech');
});

test('save updates an existing tag translation', function (): void {
    $admin = tagsUser();
    $tag = Tag::factory()->create(['name' => 'Tech', 'slug' => 'tech']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('editTag', $tag->id)
        ->set("rows.{$this->english->id}.name", 'Technology')
        ->call('save');

    expect($tag->fresh()->translate('name'))->toBe('Technology');
});

test('deleteTag removes the tag', function (): void {
    $admin = tagsUser();
    $tag = Tag::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('deleteTag', $tag->id);

    expect(Tag::query()->find($tag->id))->toBeNull();
});

test('openMerge sets up the merge modal with selected target', function (): void {
    $admin = tagsUser();
    $target = Tag::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openMerge', $target->id)
        ->assertSet('mergeTargetId', $target->id)
        ->assertSet('showMerge', true);
});

test('performMerge re-tags posts to target and deletes sources', function (): void {
    $admin = tagsUser();
    $target = Tag::factory()->create(['name' => 'AI', 'slug' => 'ai']);
    $source = Tag::factory()->create(['name' => 'ML', 'slug' => 'ml']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openMerge', $target->id)
        ->set('mergeSourceIds', [$source->id])
        ->call('performMerge')
        ->assertSet('showMerge', false);

    expect(Tag::query()->find($source->id))->toBeNull();
    expect(Tag::query()->find($target->id))->not->toBeNull();
});

test('performMerge does nothing without sources selected', function (): void {
    $admin = tagsUser();
    $target = Tag::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openMerge', $target->id)
        ->set('mergeSourceIds', [])
        ->call('performMerge')
        ->assertDispatched('toast.danger');
});

test('search filter narrows tag list', function (): void {
    $admin = tagsUser();
    Tag::factory()->create(['name' => 'AI Marketing', 'slug' => 'ai-marketing']);
    Tag::factory()->create(['name' => 'Quantum Physics', 'slug' => 'quantum-physics']);

    $component = Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'Quantum');

    expect($component->instance()->tags->pluck('name'))->toContain('Quantum Physics');
    expect($component->instance()->tags->pluck('name'))->not->toContain('AI Marketing');
});
