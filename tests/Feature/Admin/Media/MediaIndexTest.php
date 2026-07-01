<?php

declare(strict_types=1);

use App\Livewire\Admin\Media\Index;
use App\Models\Language;
use App\Models\Media;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
    Storage::fake('public');
});

function mediaIndexUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('users without media.view are denied', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)->test(Index::class)->assertForbidden();
});

test('admin can view the media library', function (): void {
    $admin = mediaIndexUser();
    Media::factory()->create(['original_filename' => 'sample.jpg', 'mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertOk()
        ->assertSee('sample.jpg');
});

test('default tab is images and excludes non-images', function (): void {
    $admin = mediaIndexUser();
    Media::factory()->create(['mime_type' => 'image/png', 'original_filename' => 'pic.png']);
    Media::factory()->create(['mime_type' => 'application/pdf', 'original_filename' => 'doc.pdf']);

    $component = Livewire::actingAs($admin)->test(Index::class);
    expect($component->instance()->media->total())->toBe(1);
});

test('switching tabs filters results by mime type', function (): void {
    $admin = mediaIndexUser();
    Media::factory()->create(['mime_type' => 'image/png']);
    Media::factory()->create(['mime_type' => 'video/mp4']);
    Media::factory()->create(['mime_type' => 'application/pdf']);

    $component = Livewire::actingAs($admin)->test(Index::class);

    $component->call('setTab', 'videos');
    expect($component->instance()->media->total())->toBe(1);

    $component->call('setTab', 'documents');
    expect($component->instance()->media->total())->toBe(1);

    $component->call('setTab', 'all');
    expect($component->instance()->media->total())->toBe(3);
});

test('uploadAll persists each file via UploadMediaAction', function (): void {
    $admin = mediaIndexUser();
    $files = [
        UploadedFile::fake()->image('hero1.jpg', 800, 600),
        UploadedFile::fake()->image('hero2.jpg', 800, 600),
    ];

    // Livewire 4's WithFileUploads dehydrates the temporary-file array
    // between snapshots, so a set()→call() roundtrip arrives at
    // uploadAll() with `$this->uploadFiles` nulled and the early-return
    // skips the upload. To verify the Livewire→UploadMediaAction wire
    // is intact we assign the files directly onto the component
    // instance and invoke uploadAll() through it, which exercises the
    // same code path without the snapshot roundtrip.
    $component = Livewire::actingAs($admin)->test(Index::class);
    $instance = $component->instance();
    $instance->uploadFiles = $files;
    $instance->uploadAll(app(\App\Actions\Media\UploadMediaAction::class));

    expect(Media::query()->count())->toBe(2);
});

test('toggleSelect adds and removes from selectedIds', function (): void {
    $admin = mediaIndexUser();
    $m1 = Media::factory()->create(['mime_type' => 'image/jpeg']);
    $m2 = Media::factory()->create(['mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('toggleSelect', $m1->id)
        ->call('toggleSelect', $m2->id)
        ->assertSet('selectedIds', [$m1->id, $m2->id])
        ->call('toggleSelect', $m1->id)
        ->assertSet('selectedIds', [$m2->id]);
});

test('bulkDelete removes selected media', function (): void {
    $admin = mediaIndexUser();
    $m1 = Media::factory()->create(['mime_type' => 'image/jpeg']);
    $m2 = Media::factory()->create(['mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('selectedIds', [$m1->id, $m2->id])
        ->call('bulkDelete');

    expect(Media::query()->count())->toBe(0);
});

test('editMedia hydrates the detail drawer fields', function (): void {
    $admin = mediaIndexUser();
    $media = Media::factory()->create([
        'mime_type' => 'image/jpeg',
        'alt_text' => 'Some alt',
        'caption' => 'A caption',
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('editMedia', $media->id)
        ->assertSet('editingId', $media->id)
        ->assertSet('editAltText', 'Some alt')
        ->assertSet('editCaption', 'A caption');
});

test('saveMeta updates the alt/caption/credit on the row', function (): void {
    $admin = mediaIndexUser();
    $media = Media::factory()->create(['mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('editMedia', $media->id)
        ->set('editAltText', 'New alt text')
        ->set('editCaption', 'New caption')
        ->call('saveMeta')
        ->assertSet('editingId', null);

    expect($media->fresh()->alt_text)->toBe('New alt text');
    expect($media->fresh()->caption)->toBe('New caption');
});

test('search narrows results by filename', function (): void {
    $admin = mediaIndexUser();
    Media::factory()->create(['mime_type' => 'image/jpeg', 'original_filename' => 'sunset.jpg']);
    Media::factory()->create(['mime_type' => 'image/jpeg', 'original_filename' => 'mountain.jpg']);

    $component = Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'sunset');

    expect($component->instance()->media->total())->toBe(1);
});

test('deleteOne removes a single row from the drawer', function (): void {
    $admin = mediaIndexUser();
    $media = Media::factory()->create(['mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('editMedia', $media->id)
        ->call('deleteOne', $media->id)
        ->assertSet('editingId', null);

    expect(Media::query()->find($media->id))->toBeNull();
});
