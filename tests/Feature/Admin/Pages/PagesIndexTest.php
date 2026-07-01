<?php

declare(strict_types=1);

use App\Enums\PageStatus;
use App\Livewire\Admin\Pages\Create as PagesCreate;
use App\Livewire\Admin\Pages\Edit as PagesEdit;
use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
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

function pagesUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('users without pages.view are denied', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)->test(PagesIndex::class)->assertForbidden();
});

test('admin sees the pages table', function (): void {
    $admin = pagesUser();
    $page = Page::factory()->withoutTranslations()->create();
    $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'About Us',
        'slug' => 'about-us',
    ]);

    Livewire::actingAs($admin)
        ->test(PagesIndex::class)
        ->assertOk()
        ->assertSee('About Us');
});

test('status filter narrows results', function (): void {
    $admin = pagesUser();
    Page::factory()->state(['status' => PageStatus::Draft->value])->create();
    Page::factory()->state(['status' => PageStatus::Published->value])->create();

    $component = Livewire::actingAs($admin)
        ->test(PagesIndex::class)
        ->set('statusFilter', PageStatus::Published->value);

    expect($component->instance()->pages->total())->toBe(1);
});

test('publish action moves a draft to published', function (): void {
    $admin = pagesUser();
    $page = Page::factory()->state(['status' => PageStatus::Draft->value])->create();

    Livewire::actingAs($admin)
        ->test(PagesIndex::class)
        ->call('publish', $page->id);

    expect($page->fresh()->status)->toBe(PageStatus::Published);
});

test('archive action moves a published page to archived', function (): void {
    $admin = pagesUser();
    $page = Page::factory()->state(['status' => PageStatus::Published->value])->create();

    Livewire::actingAs($admin)
        ->test(PagesIndex::class)
        ->call('archive', $page->id);

    expect($page->fresh()->status)->toBe(PageStatus::Archived);
});

test('deletePage removes the page', function (): void {
    $admin = pagesUser();
    $page = Page::factory()->create();

    Livewire::actingAs($admin)
        ->test(PagesIndex::class)
        ->call('deletePage', $page->id);

    expect(Page::query()->find($page->id))->toBeNull();
});

test('Create component mounts with one blank translation tab', function (): void {
    $admin = pagesUser();

    $component = Livewire::actingAs($admin)
        ->test(PagesCreate::class);

    expect($component->get('activeLanguageId'))->toBe($this->english->id);
    expect($component->get('translations'))->toHaveKey($this->english->id);
});

test('Create save persists a new page with one translation', function (): void {
    $admin = pagesUser();

    Livewire::actingAs($admin)
        ->test(PagesCreate::class)
        ->set('title', 'About Us')
        ->set('content', '<p>Welcome to our site.</p>')
        ->set('isPublished', true)
        ->call('save');

    $page = Page::query()->latest('id')->first();
    expect($page)->not->toBeNull();
    $translation = $page->translations()->where('language_id', $this->english->id)->first();
    expect($translation->title)->toBe('About Us');
    expect($translation->is_published)->toBeTrue();
});

test('Edit mounts with existing translations hydrated', function (): void {
    $admin = pagesUser();
    $page = Page::factory()->withoutTranslations()->create();
    $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'Privacy Policy',
        'slug' => 'privacy',
        'content' => '<p>...</p>',
    ]);

    Livewire::actingAs($admin)
        ->test(PagesEdit::class, ['page' => $page->fresh()])
        ->assertSet('title', 'Privacy Policy')
        ->assertSet('slug', 'privacy');
});

test('Edit save persists changes', function (): void {
    $admin = pagesUser();
    $page = Page::factory()->withoutTranslations()->create();
    $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'Original',
        'slug' => 'original',
    ]);

    Livewire::actingAs($admin)
        ->test(PagesEdit::class, ['page' => $page->fresh()])
        ->set('title', 'Renamed Title')
        ->call('save');

    expect($page->fresh()->translate('title'))->toBe('Renamed Title');
});

test('Edit addTranslation + save creates new locale row', function (): void {
    $admin = pagesUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla', 'is_active' => true])->create();
    $page = Page::factory()->withoutTranslations()->create();
    $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'Hello',
        'slug' => 'hello',
    ]);

    Livewire::actingAs($admin)
        ->test(PagesEdit::class, ['page' => $page->fresh()])
        ->call('addTranslation', $bangla->id)
        ->set('title', 'হ্যালো')
        ->set('content', '<p>স্বাগতম</p>')
        ->call('save');

    expect($page->fresh()->translations()->where('language_id', $bangla->id)->exists())->toBeTrue();
});

test('Edit publish action flips the page status', function (): void {
    $admin = pagesUser();
    $page = Page::factory()->state(['status' => PageStatus::Draft->value])->create();

    Livewire::actingAs($admin)
        ->test(PagesEdit::class, ['page' => $page->fresh()])
        ->call('publish');

    expect($page->fresh()->status)->toBe(PageStatus::Published);
});

test('Edit removeTranslation marks for deletion and Save drops the row', function (): void {
    $admin = pagesUser();
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla', 'is_active' => true])->create();
    $page = Page::factory()->withoutTranslations()->create();
    $page->translations()->create(['language_id' => $this->english->id, 'title' => 'Hello', 'slug' => 'hello']);
    $page->translations()->create(['language_id' => $bangla->id, 'title' => 'হ্যালো', 'slug' => 'hello-bn']);

    Livewire::actingAs($admin)
        ->test(PagesEdit::class, ['page' => $page->fresh()])
        ->call('removeTranslation', $bangla->id)
        ->call('save');

    expect($page->fresh()->translations()->where('language_id', $bangla->id)->exists())->toBeFalse();
});
