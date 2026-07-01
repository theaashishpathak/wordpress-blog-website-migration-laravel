<?php

declare(strict_types=1);

use App\Livewire\Admin\Categories\Index;
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
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function categoriesIndexUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('users without categories.view are denied', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)->test(Index::class)->assertForbidden();
});

test('admin sees every category', function (): void {
    $admin = categoriesIndexUser('Admin');
    Category::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertOk();
});

test('tree includes every category in pre-order traversal', function (): void {
    $admin = categoriesIndexUser('Admin');

    $parent = Category::factory()->create(['sort_order' => 0]);
    $childA = Category::factory()->child($parent->id)->create(['sort_order' => 0]);
    $childB = Category::factory()->child($parent->id)->create(['sort_order' => 1]);
    $sibling = Category::factory()->create(['sort_order' => 5]);

    $component = Livewire::actingAs($admin)->test(Index::class);
    $tree = $component->instance()->tree;

    expect(collect($tree)->pluck('category.id')->all())->toBe([
        $parent->id,
        $childA->id,
        $childB->id,
        $sibling->id,
    ]);

    expect($tree[1]['depth'])->toBe(1);
    expect($tree[0]['depth'])->toBe(0);
});

test('search filters by translated name and keeps ancestors visible', function (): void {
    $admin = categoriesIndexUser('Admin');

    $parent = Category::factory()->create();
    $parent->translations()->first()->update(['name' => 'Technology', 'slug' => 'technology']);

    $child = Category::factory()->child($parent->id)->create();
    $child->translations()->first()->update(['name' => 'Gadgets', 'slug' => 'gadgets']);

    $other = Category::factory()->create();
    $other->translations()->first()->update(['name' => 'Sports', 'slug' => 'sports']);

    $component = Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'Gadget');

    $ids = collect($component->instance()->tree)->pluck('category.id')->all();

    expect($ids)->toContain($child->id);
    expect($ids)->toContain($parent->id);
    expect($ids)->not->toContain($other->id);
});

test('featuredOnly filter narrows to is_featured rows', function (): void {
    $admin = categoriesIndexUser('Admin');

    $featured = Category::factory()->featured()->create();
    $regular = Category::factory()->create();

    $component = Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('featuredOnly', true);

    $ids = collect($component->instance()->tree)->pluck('category.id')->all();

    expect($ids)->toContain($featured->id);
    expect($ids)->not->toContain($regular->id);
});

test('reorder persists new sort_order across the batch', function (): void {
    $admin = categoriesIndexUser('Admin');

    $a = Category::factory()->create(['sort_order' => 0]);
    $b = Category::factory()->create(['sort_order' => 0]);
    $c = Category::factory()->create(['sort_order' => 0]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('reorder', [
            ['id' => $a->id, 'sort_order' => 2, 'parent_id' => null],
            ['id' => $b->id, 'sort_order' => 0, 'parent_id' => null],
            ['id' => $c->id, 'sort_order' => 1, 'parent_id' => null],
        ])
        ->assertDispatched('toast.success');

    expect($a->fresh()->sort_order)->toBe(2);
    expect($b->fresh()->sort_order)->toBe(0);
    expect($c->fresh()->sort_order)->toBe(1);
});

test('reorder re-parents a category onto another', function (): void {
    $admin = categoriesIndexUser('Admin');

    $parent = Category::factory()->create();
    $orphan = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('reorder', [
            ['id' => $orphan->id, 'sort_order' => 0, 'parent_id' => $parent->id],
        ]);

    expect($orphan->fresh()->parent_id)->toBe($parent->id);
});

test('reorder denies users without categories.edit', function (): void {
    $author = categoriesIndexUser('Author');

    $a = Category::factory()->create(['sort_order' => 5]);

    Livewire::actingAs($author)
        ->test(Index::class)
        ->call('reorder', [
            ['id' => $a->id, 'sort_order' => 0, 'parent_id' => null],
        ])
        ->assertDispatched('toast.danger');

    expect($a->fresh()->sort_order)->toBe(5);
});

test('deleteCategory removes the row and re-parents children', function (): void {
    $admin = categoriesIndexUser('Admin');

    $parent = Category::factory()->create();
    $child = Category::factory()->child($parent->id)->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('deleteCategory', $parent->id)
        ->assertDispatched('toast.success');

    expect(Category::query()->find($parent->id))->toBeNull();
    expect($child->fresh()->parent_id)->toBeNull();
});

test('deleteCategory is denied without categories.delete permission', function (): void {
    $author = categoriesIndexUser('Author');
    $category = Category::factory()->create();

    Livewire::actingAs($author)
        ->test(Index::class)
        ->call('deleteCategory', $category->id)
        ->assertDispatched('toast.danger');

    expect(Category::query()->find($category->id))->not->toBeNull();
});

test('bulkDelete removes every selected row', function (): void {
    $admin = categoriesIndexUser('Admin');

    $one = Category::factory()->create();
    $two = Category::factory()->create();
    $three = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('selectedIds', [$one->id, $two->id])
        ->call('bulkDelete')
        ->assertDispatched('toast.success');

    expect(Category::query()->find($one->id))->toBeNull();
    expect(Category::query()->find($two->id))->toBeNull();
    expect(Category::query()->find($three->id))->not->toBeNull();
});

test('clearFilters resets every filter property', function (): void {
    $admin = categoriesIndexUser('Admin');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'tech')
        ->set('languageFilter', '1')
        ->set('featuredOnly', true)
        ->set('inMenuOnly', true)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('languageFilter', '')
        ->assertSet('featuredOnly', false)
        ->assertSet('inMenuOnly', false);
});

test('reorder rejects self-parenting by ignoring the parent_id', function (): void {
    $admin = categoriesIndexUser('Admin');

    $category = Category::factory()->create(['sort_order' => 0]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('reorder', [
            ['id' => $category->id, 'sort_order' => 0, 'parent_id' => $category->id],
        ]);

    expect($category->fresh()->parent_id)->toBeNull();
});
