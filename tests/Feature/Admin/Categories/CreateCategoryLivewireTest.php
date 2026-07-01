<?php

declare(strict_types=1);

use App\Livewire\Admin\Categories\Create;
use App\Models\Category;
use App\Models\Language;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function categoryCreateUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('users without categories.create are denied', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)->test(Create::class)->assertForbidden();
});

test('mount seeds the default language translation tab', function (): void {
    $admin = categoryCreateUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->assertOk()
        ->assertSet('defaultLanguageId', $this->english->id)
        ->assertSet('activeLanguageId', $this->english->id);
});

test('save creates the category with a single translation', function (): void {
    $admin = categoryCreateUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('translations.'.$this->english->id.'.name', 'Technology')
        ->set('icon', 'cpu')
        ->set('color', '#4f46e5')
        ->call('save');

    $category = Category::query()->first();

    expect($category)->not->toBeNull();
    expect($category->translate('name', 'en'))->toBe('Technology');
    expect($category->translate('slug', 'en'))->toBe('technology');
    expect($category->icon)->toBe('cpu');
    expect($category->color)->toBe('#4f46e5');
});

test('save fails validation when default-language name is missing', function (): void {
    $admin = categoryCreateUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('translations.'.$this->english->id.'.name', '')
        ->call('save')
        ->assertHasErrors(['translations.'.$this->english->id.'.name']);

    expect(Category::query()->count())->toBe(0);
});

test('addTranslation appends a new language tab and switches to it', function (): void {
    $admin = categoryCreateUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->call('addTranslation', $this->bangla->id)
        ->assertSet('activeLanguageId', $this->bangla->id)
        ->tap(function ($component): void {
            $translations = $component->get('translations');
            expect($translations)->toHaveKey($this->bangla->id);
        });
});

test('save persists multiple translations in one call', function (): void {
    $admin = categoryCreateUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('translations.'.$this->english->id.'.name', 'Sports')
        ->call('addTranslation', $this->bangla->id)
        ->set('translations.'.$this->bangla->id.'.name', 'খেলা')
        ->set('translations.'.$this->bangla->id.'.slug', 'khela')
        ->call('save');

    $category = Category::query()->with('translations')->first();

    expect($category)->not->toBeNull();
    expect($category->translations)->toHaveCount(2);
    expect($category->translate('name', 'en'))->toBe('Sports');
    expect($category->translate('name', 'bn'))->toBe('খেলা');
    expect($category->translate('slug', 'bn'))->toBe('khela');
});

test('removeTranslation removes a non-default tab', function (): void {
    $admin = categoryCreateUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->call('addTranslation', $this->bangla->id)
        ->call('removeTranslation', $this->bangla->id)
        ->assertSet('activeLanguageId', $this->english->id)
        ->tap(function ($component): void {
            $translations = $component->get('translations');
            expect($translations)->not->toHaveKey($this->bangla->id);
        });
});

test('removeTranslation refuses to drop the default language tab', function (): void {
    $admin = categoryCreateUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->call('removeTranslation', $this->english->id)
        ->assertDispatched('toast.danger')
        ->tap(function ($component): void {
            $translations = $component->get('translations');
            expect($translations)->toHaveKey($this->english->id);
        });
});
