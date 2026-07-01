<?php

declare(strict_types=1);

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

test('persists sort_order updates across the batch', function (): void {
    $a = Category::factory()->create(['sort_order' => 0]);
    $b = Category::factory()->create(['sort_order' => 0]);
    $c = Category::factory()->create(['sort_order' => 0]);

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $a->id, 'sort_order' => 2],
        ['id' => $b->id, 'sort_order' => 0],
        ['id' => $c->id, 'sort_order' => 1],
    ]);

    expect($a->fresh()->sort_order)->toBe(2);
    expect($b->fresh()->sort_order)->toBe(0);
    expect($c->fresh()->sort_order)->toBe(1);
});

test('re-parents categories when parent_id is supplied', function (): void {
    $parent = Category::factory()->create();
    $child = Category::factory()->create();

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $child->id, 'sort_order' => 0, 'parent_id' => $parent->id],
    ]);

    expect($child->fresh()->parent_id)->toBe($parent->id);
});

test('moves a category back to root when parent_id is null', function (): void {
    $parent = Category::factory()->create();
    $child = Category::factory()->child($parent->id)->create();

    expect($child->parent_id)->toBe($parent->id);

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $child->id, 'sort_order' => 0, 'parent_id' => null],
    ]);

    expect($child->fresh()->parent_id)->toBeNull();
});

test('refuses to set a category as its own parent', function (): void {
    $category = Category::factory()->create();

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $category->id, 'sort_order' => 0, 'parent_id' => $category->id],
    ]);

    expect($category->fresh()->parent_id)->toBeNull();
});

test('skips rows with invalid ids without throwing', function (): void {
    $category = Category::factory()->create(['sort_order' => 5]);

    app(ReorderCategoriesAction::class)->handle([
        ['id' => 0, 'sort_order' => 99],
        ['id' => $category->id, 'sort_order' => 1],
    ]);

    expect($category->fresh()->sort_order)->toBe(1);
});

test('runs every update inside a single transaction', function (): void {
    $a = Category::factory()->create(['sort_order' => 0]);

    // The query log doesn't always capture BEGIN/COMMIT across drivers
    // (SQLite in particular elides them), so we observe transaction
    // boundaries through Laravel's transactionLevel() instead.
    $depths = [];
    \Illuminate\Support\Facades\DB::listen(function ($query) use (&$depths): void {
        $depths[] = \Illuminate\Support\Facades\DB::transactionLevel();
    });

    app(ReorderCategoriesAction::class)->handle([
        ['id' => $a->id, 'sort_order' => 9],
    ]);

    expect($depths)->not->toBeEmpty();
    // RefreshDatabase wraps every test in level 1; the Action's own
    // DB::transaction() pushes us to level 2 for its inner queries.
    expect(collect($depths)->max())->toBeGreaterThanOrEqual(2);
});
