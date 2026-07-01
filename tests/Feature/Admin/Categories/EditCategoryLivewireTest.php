<?php

declare(strict_types=1);

use App\Livewire\Admin\Categories\Edit;
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

function categoryEditUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('users without categories.edit are denied', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::factory()->create();

    Livewire::actingAs($user)->test(Edit::class, ['category' => $category])->assertForbidden();
});

test('mount hydrates structural fields from the category', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->featured()->create([
        'icon' => 'flame',
        'color' => '#ff0000',
        'sort_order' => 7,
        'layout' => Category::LAYOUT_MAGAZINE,
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->assertOk()
        ->assertSet('icon', 'flame')
        ->assertSet('color', '#ff0000')
        ->assertSet('sortOrder', 7)
        ->assertSet('layout', Category::LAYOUT_MAGAZINE)
        ->assertSet('isFeatured', true);
});

test('mount loads every existing translation into the dictionary', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();
    $category->translations()->create([
        'language_id' => $this->bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'prajukti',
    ]);

    $component = Livewire::actingAs($admin)->test(Edit::class, ['category' => $category]);

    $translations = $component->get('translations');

    expect($translations)->toHaveKey($this->english->id);
    expect($translations)->toHaveKey($this->bangla->id);
    expect($translations[$this->bangla->id]['name'])->toBe('প্রযুক্তি');
});

test('save updates structural fields', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create([
        'icon' => 'folder',
        'color' => null,
        'is_featured' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->set('icon', 'rocket')
        ->set('color', '#10b981')
        ->set('isFeatured', true)
        ->call('save')
        ->assertDispatched('toast.success');

    $fresh = $category->fresh();
    expect($fresh->icon)->toBe('rocket');
    expect($fresh->color)->toBe('#10b981');
    expect($fresh->is_featured)->toBeTrue();
});

test('save updates an existing translation in place', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();
    $category->translations()->first()->update(['name' => 'Old', 'slug' => 'old']);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->set("translations.{$this->english->id}.name", 'New Name')
        ->call('save');

    expect($category->fresh()->translate('name', 'en'))->toBe('New Name');
});

test('addTranslation lets the user add a new language tab', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->call('addTranslation', $this->bangla->id)
        ->set("translations.{$this->bangla->id}.name", 'প্রযুক্তি')
        ->call('save');

    expect($category->fresh()->translate('name', 'bn'))->toBe('প্রযুক্তি');
});

test('save fails validation when default-language name is blanked', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->set("translations.{$this->english->id}.name", '')
        ->call('save')
        ->assertHasErrors(["translations.{$this->english->id}.name"]);
});

test('removeTranslation marks a non-default translation for deletion on save', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();
    $category->translations()->create([
        'language_id' => $this->bangla->id,
        'name' => 'খেলা',
        'slug' => 'khela',
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->call('removeTranslation', $this->bangla->id)
        ->call('save');

    expect($category->fresh()->translations()->where('language_id', $this->bangla->id)->exists())->toBeFalse();
});

test('removeTranslation refuses to drop the default language', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->call('removeTranslation', $this->english->id)
        ->assertDispatched('toast.danger');

    expect($category->fresh()->translations()->where('language_id', $this->english->id)->exists())->toBeTrue();
});

test('save persists per-language slug correctly', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->call('addTranslation', $this->bangla->id)
        ->set("translations.{$this->bangla->id}.name", 'খেলা')
        ->set("translations.{$this->bangla->id}.slug", 'sports-bn')
        ->call('save');

    expect($category->fresh()->translate('slug', 'bn'))->toBe('sports-bn');
});

test('switchLanguage activates the requested tab', function (): void {
    $admin = categoryEditUser('Admin');
    $category = Category::factory()->create();
    $category->translations()->create([
        'language_id' => $this->bangla->id,
        'name' => 'বাংলা',
        'slug' => 'bangla',
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['category' => $category])
        ->call('switchLanguage', $this->bangla->id)
        ->assertSet('activeLanguageId', $this->bangla->id);
});
