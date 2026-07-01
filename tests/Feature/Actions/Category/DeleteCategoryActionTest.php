<?php

declare(strict_types=1);

use App\Actions\Category\DeleteCategoryAction;
use App\Actions\Category\ReorderCategoriesAction;
use App\Models\Category;
use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('soft-deletes the category and re-parents children to its parent', function (): void {
    $grandparent = Category::factory()->create();
    $parent = Category::factory()->child($grandparent->id)->create();
    $childA = Category::factory()->child($parent->id)->create();
    $childB = Category::factory()->child($parent->id)->create();

    app(DeleteCategoryAction::class)->handle($parent);

    // Parent soft-deleted
    expect(Category::query()->find($parent->id))->toBeNull();
    expect(Category::withTrashed()->find($parent->id))->not->toBeNull();

    // Children re-parented to grandparent
    expect($childA->fresh()->parent_id)->toBe($grandparent->id);
    expect($childB->fresh()->parent_id)->toBe($grandparent->id);
});

test('with orphanChildren=true the children become root-level', function (): void {
    $parent = Category::factory()->create();
    $child = Category::factory()->child($parent->id)->create();

    app(DeleteCategoryAction::class)->handle($parent, orphanChildren: true);

    expect($child->fresh()->parent_id)->toBeNull();
});

test('reorder updates sort_order for each supplied id', function (): void {
    $a = Category::factory()->state(['sort_order' => 1])->create();
    $b = Category::factory()->state(['sort_order' => 2])->create();
    $c = Category::factory()->state(['sort_order' => 3])->create();

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $a->id, 'sort_order' => 30],
        ['id' => $b->id, 'sort_order' => 10],
        ['id' => $c->id, 'sort_order' => 20],
    ]);

    expect($a->fresh()->sort_order)->toBe(30);
    expect($b->fresh()->sort_order)->toBe(10);
    expect($c->fresh()->sort_order)->toBe(20);
});

test('reorder can simultaneously move under a new parent', function (): void {
    $root = Category::factory()->create();
    $other = Category::factory()->create();
    $moving = Category::factory()->child($root->id)->state(['sort_order' => 5])->create();

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $moving->id, 'sort_order' => 0, 'parent_id' => $other->id],
    ]);

    $moving->refresh();
    expect($moving->parent_id)->toBe($other->id);
    expect($moving->sort_order)->toBe(0);
});

test('reorder refuses to set a category as its own parent', function (): void {
    $category = Category::factory()->create();

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $category->id, 'sort_order' => 0, 'parent_id' => $category->id],
    ]);

    expect($category->fresh()->parent_id)->toBeNull();
});
