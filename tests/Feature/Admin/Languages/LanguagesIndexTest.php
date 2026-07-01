<?php

declare(strict_types=1);

use App\Livewire\Admin\Languages\Index;
use App\Models\Language;
use App\Models\Post;
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

function langUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('users without languages.view permission are denied', function (): void {
    $author = langUser('Author');

    Livewire::actingAs($author)->test(Index::class)->assertForbidden();
});

test('admin can view the languages table', function (): void {
    $admin = langUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertOk()
        ->assertSee('English');
});

test('newLanguage opens the modal with empty form state', function (): void {
    $admin = langUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('newLanguage')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('code', '')
        ->assertSet('name', '');
});

test('save creates a new language', function (): void {
    $admin = langUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('newLanguage')
        ->set('code', 'bn')
        ->set('name', 'Bangla')
        ->set('nativeName', 'বাংলা')
        ->set('flagEmoji', '🇧🇩')
        ->call('save')
        ->assertSet('showForm', false);

    expect(Language::query()->where('code', 'bn')->exists())->toBeTrue();
});

test('editLanguage hydrates the form with current values', function (): void {
    $admin = langUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla'])->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('editLanguage', $bangla->id)
        ->assertSet('editingId', $bangla->id)
        ->assertSet('code', 'bn')
        ->assertSet('name', 'Bangla')
        ->assertSet('showForm', true);
});

test('save updates an existing language', function (): void {
    $admin = langUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla'])->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('editLanguage', $bangla->id)
        ->set('name', 'Bengali')
        ->call('save');

    expect($bangla->fresh()->name)->toBe('Bengali');
});

test('toggleActive flips the is_active flag', function (): void {
    $admin = langUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla', 'is_active' => true])->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('toggleActive', $bangla->id);

    expect($bangla->fresh()->is_active)->toBeFalse();
});

test('toggleActive refuses to deactivate the default language', function (): void {
    $admin = langUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('toggleActive', $this->english->id)
        ->assertDispatched('toast.danger');

    expect($this->english->fresh()->is_active)->toBeTrue();
});

test('makeDefault promotes one language and demotes the previous default', function (): void {
    $admin = langUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla', 'is_default' => false])->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('makeDefault', $bangla->id);

    expect($bangla->fresh()->is_default)->toBeTrue();
    expect($this->english->fresh()->is_default)->toBeFalse();
});

test('deleteLanguage refuses to delete the default language', function (): void {
    $admin = langUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('deleteLanguage', $this->english->id)
        ->assertDispatched('toast.danger');

    expect(Language::query()->where('id', $this->english->id)->exists())->toBeTrue();
});

test('deleteLanguage refuses to delete a language with existing translations', function (): void {
    $admin = langUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla', 'is_default' => false])->create();

    // Seed a post translation in bangla
    Post::factory()->create([
        'default_language_id' => $bangla->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('deleteLanguage', $bangla->id)
        ->assertDispatched('toast.danger');

    expect(Language::query()->where('id', $bangla->id)->exists())->toBeTrue();
});

test('deleteLanguage removes a language with no references', function (): void {
    $admin = langUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla', 'is_default' => false])->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('deleteLanguage', $bangla->id);

    expect(Language::query()->where('id', $bangla->id)->exists())->toBeFalse();
});

test('save rejects duplicate language codes', function (): void {
    $admin = langUser();
    Language::factory()->state(['code' => 'bn', 'name' => 'Bangla'])->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('newLanguage')
        ->set('code', 'bn')
        ->set('name', 'Bangla Duplicate')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

test('save requires code and name', function (): void {
    $admin = langUser();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('newLanguage')
        ->set('code', '')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['code', 'name']);
});
