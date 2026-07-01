<?php

declare(strict_types=1);

use App\Livewire\Admin\Media\MediaPickerModal;
use App\Livewire\Admin\Posts\Edit as EditPostLivewire;
use App\Models\Language;
use App\Models\Media;
use App\Models\Post;
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

function pickerUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('opens with the target tag when media-picker.open is dispatched', function (): void {
    $admin = pickerUser();

    Livewire::actingAs($admin)
        ->test(MediaPickerModal::class)
        ->assertSet('open', false)
        ->dispatch('media-picker.open', payload: ['target' => 'featured_image'])
        ->assertSet('open', true)
        ->assertSet('target', 'featured_image');
});

test('selecting an existing media row dispatches media.selected with payload and closes', function (): void {
    $admin = pickerUser();
    $media = Media::factory()->create([
        'mime_type' => 'image/jpeg',
        'alt_text' => 'Sample alt',
    ]);

    Livewire::actingAs($admin)
        ->test(MediaPickerModal::class)
        ->dispatch('media-picker.open', payload: ['target' => 'featured_image'])
        ->call('select', $media->id)
        ->assertDispatched('media.selected', fn (string $event, array $params) =>
            ($params['payload']['target'] ?? null) === 'featured_image'
            && ($params['payload']['mediaId'] ?? null) === $media->id
        )
        ->assertSet('open', false);
});

test('search filter narrows the result list', function (): void {
    $admin = pickerUser();
    Media::factory()->create(['original_filename' => 'sunset-beach.jpg', 'mime_type' => 'image/jpeg']);
    Media::factory()->create(['original_filename' => 'mountain-vista.jpg', 'mime_type' => 'image/jpeg']);

    $component = Livewire::actingAs($admin)
        ->test(MediaPickerModal::class)
        ->dispatch('media-picker.open', payload: ['target' => 'featured_image'])
        ->set('search', 'sunset');

    $results = $component->instance()->results;
    expect($results->total())->toBe(1);
    expect($results->first()->original_filename)->toBe('sunset-beach.jpg');
});

test('non-image media is excluded when mime filter defaults to image', function (): void {
    $admin = pickerUser();
    Media::factory()->create(['mime_type' => 'image/png', 'original_filename' => 'cat.png']);
    Media::factory()->create(['mime_type' => 'application/pdf', 'original_filename' => 'doc.pdf']);

    $component = Livewire::actingAs($admin)
        ->test(MediaPickerModal::class)
        ->dispatch('media-picker.open', payload: ['target' => 'featured_image']);

    expect($component->instance()->results->total())->toBe(1);
});

test('upload-and-select stores the file, creates Media, dispatches selection, closes', function (): void {
    $admin = pickerUser();
    $file = UploadedFile::fake()->image('hero.jpg', 1024, 768);

    Livewire::actingAs($admin)
        ->test(MediaPickerModal::class)
        ->dispatch('media-picker.open', payload: ['target' => 'featured_image'])
        ->set('uploadFile', $file)
        ->set('uploadAltText', 'A hero image')
        ->call('uploadAndSelect')
        ->assertDispatched('media.selected', fn (string $event, array $params) =>
            ($params['payload']['target'] ?? null) === 'featured_image'
            && isset($params['payload']['mediaId'])
        )
        ->assertSet('open', false);

    $media = Media::query()->latest('id')->first();
    expect($media)->not->toBeNull();
    expect($media->alt_text)->toBe('A hero image');
    expect($media->mime_type)->toStartWith('image/');
});

test('parent post Edit component picks up selected media and persists featured_image_id on save', function (): void {
    $admin = pickerUser();
    $post = Post::factory()->draft()->create();
    $media = Media::factory()->create(['mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(EditPostLivewire::class, ['post' => $post])
        ->dispatch('media.selected', payload: [
            'target' => 'featured_image',
            'mediaId' => $media->id,
            'url' => $media->url(),
            'altText' => '',
        ])
        ->assertSet('featuredImageId', $media->id)
        ->call('save');

    expect($post->fresh()->featured_image_id)->toBe($media->id);
});

test('media.selected event with a different target is ignored', function (): void {
    $admin = pickerUser();
    $post = Post::factory()->draft()->create();
    $media = Media::factory()->create(['mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(EditPostLivewire::class, ['post' => $post])
        ->dispatch('media.selected', payload: [
            'target' => 'some_other_picker',
            'mediaId' => $media->id,
        ])
        ->assertSet('featuredImageId', null);
});

test('clearFeaturedImage sets the property back to null', function (): void {
    $admin = pickerUser();
    $media = Media::factory()->create(['mime_type' => 'image/jpeg']);
    $post = Post::factory()->draft()->state(['featured_image_id' => $media->id])->create();

    Livewire::actingAs($admin)
        ->test(EditPostLivewire::class, ['post' => $post])
        ->assertSet('featuredImageId', $media->id)
        ->call('clearFeaturedImage')
        ->assertSet('featuredImageId', null)
        ->call('save');

    expect($post->fresh()->featured_image_id)->toBeNull();
});
