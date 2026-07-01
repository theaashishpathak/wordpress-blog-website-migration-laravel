<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Visitor profile editor — name, email, bio, avatar, social links.
 * Email changes require password confirmation since changing the email
 * effectively transfers account access to the new address.
 */
#[Layout('layouts.visitor')]
#[Title('My Profile')]
class Profile extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:190')]
    public string $email = '';

    #[Validate('nullable|string|max:500')]
    public string $bio = '';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    /** Password required only when email changes. */
    #[Validate('nullable|string|min:8')]
    public string $currentPassword = '';

    public $avatar = null;

    /** @var array<string, string> */
    public array $social = [
        'twitter' => '',
        'facebook' => '',
        'linkedin' => '',
        'instagram' => '',
        'youtube' => '',
        'website' => '',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->bio = (string) ($user->bio ?? '');
        $this->phone = (string) ($user->phone ?? '');

        $stored = is_array($user->social_links) ? $user->social_links : [];
        foreach ($this->social as $key => $_) {
            $this->social[$key] = (string) ($stored[$key] ?? '');
        }
    }

    public function save(): void
    {
        $user = auth()->user();
        $emailChanging = $this->email !== $user->email;

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];

        if ($emailChanging) {
            $rules['currentPassword'] = ['required', 'string'];
        }

        if ($this->avatar) {
            $rules['avatar'] = ['file', 'image', 'max:2048'];
        }

        $this->validate($rules);

        if ($emailChanging && ! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'That password is incorrect.');

            return;
        }

        $payload = [
            'name' => trim($this->name),
            'email' => mb_strtolower(trim($this->email)),
            'bio' => $this->bio ?: null,
            'phone' => $this->phone ?: null,
            'social_links' => array_filter($this->social, fn ($v) => $v !== ''),
        ];

        if ($emailChanging) {
            $payload['email_verified_at'] = null;
        }

        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $payload['avatar'] = $path;
        }

        $user->forceFill($payload)->save();

        $this->avatar = null;
        $this->currentPassword = '';

        $this->dispatch('toast', message: $emailChanging ? 'Profile saved — verify your new email.' : 'Profile saved.');
    }

    public function removeAvatar(): void
    {
        $user = auth()->user();
        if ($user->avatar) {
            try {
                Storage::disk('public')->delete($user->avatar);
            } catch (\Throwable) {
                // Best effort — don't block on storage errors.
            }
            $user->forceFill(['avatar' => null])->save();
        }

        $this->dispatch('toast', message: 'Avatar removed.');
    }

    public function render(): View
    {
        return view('livewire.visitor.settings.profile');
    }
}
