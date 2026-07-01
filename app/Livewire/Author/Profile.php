<?php

declare(strict_types=1);

namespace App\Livewire\Author;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Author profile editor — the public-facing slice of the user record.
 *
 * Distinct from the existing /user/profile screen (which handles
 * password + 2FA + email verification). This page is purely about the
 * author's bio, social links, and public profile slug that appears in
 * frontend AuthorShow pages.
 */
#[Layout('layouts.app')]
#[Title('Author Profile')]
class Profile extends Component
{
    public string $displayName = '';

    public string $bio = '';

    public string $publicSlug = '';

    public bool $showInTeam = false;

    /**
     * Flat social link map. Keys mirror well-known platforms; the
     * frontend renders an icon for each non-empty value.
     *
     * @var array<string, string>
     */
    public array $social = [
        'twitter' => '',
        'facebook' => '',
        'instagram' => '',
        'linkedin' => '',
        'youtube' => '',
        'website' => '',
    ];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('posts.create') ?? false,
            403,
            'You do not have author privileges.',
        );

        $user = auth()->user();

        $this->displayName = (string) $user->name;
        $this->bio = (string) ($user->bio ?? '');
        $this->publicSlug = (string) ($user->public_slug ?? '');
        $this->showInTeam = (bool) $user->show_in_team;

        $stored = is_array($user->social_links) ? $user->social_links : [];
        foreach (array_keys($this->social) as $key) {
            $this->social[$key] = (string) ($stored[$key] ?? '');
        }
    }

    public function save(): void
    {
        $this->validate($this->rules());

        $user = auth()->user();

        $social = array_filter(array_map(static fn ($v) => trim((string) $v), $this->social), static fn ($v) => $v !== '');

        $user->fill([
            'name' => trim($this->displayName),
            'bio' => $this->bio !== '' ? $this->bio : null,
            'public_slug' => $this->publicSlug !== '' ? Str::slug($this->publicSlug) : null,
            'show_in_team' => $this->showInTeam,
            'social_links' => $social,
        ])->save();

        $this->dispatch('toast.success', message: 'Profile updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'displayName' => ['required', 'string', 'min:2', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'publicSlug' => [
                'nullable', 'string', 'max:80',
                \Illuminate\Validation\Rule::unique('users', 'public_slug')->ignore(auth()->id()),
            ],
            'social.*' => ['nullable', 'string', 'max:255'],
            'social.twitter' => ['nullable', 'string', 'max:255'],
            'social.facebook' => ['nullable', 'string', 'max:255'],
            'social.linkedin' => ['nullable', 'string', 'max:255'],
            'social.instagram' => ['nullable', 'string', 'max:255'],
            'social.youtube' => ['nullable', 'string', 'max:255'],
            'social.website' => ['nullable', 'url', 'max:255'],
        ];
    }

    #[Computed]
    public function publicUrl(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $locale = app(\App\Support\LocaleResolver::class)->current();

        return route('frontend.author', ['locale' => $locale?->code, 'user' => $user->id]);
    }

    public function render(): View
    {
        return view('livewire.author.profile');
    }
}
