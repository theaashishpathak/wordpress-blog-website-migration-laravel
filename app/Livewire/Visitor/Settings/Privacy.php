<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Settings;

use App\Models\UserSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Privacy controls — all stored in user_settings JSON values so new
 * options can be added without migrations.
 *
 * Keys:
 *   profile_visibility   public | followers | private
 *   show_reading_history bool
 *   show_followers       bool
 *   show_following       bool
 *   allow_dms            bool
 */
#[Layout('layouts.visitor')]
#[Title('Privacy')]
class Privacy extends Component
{
    public string $profileVisibility = 'public';

    public bool $showReadingHistory = false;

    public bool $showFollowers = true;

    public bool $showFollowing = true;

    public bool $allowDms = true;

    public function mount(): void
    {
        $uid = auth()->id();
        $this->profileVisibility = (string) UserSetting::getValue($uid, 'profile_visibility', 'public');
        $this->showReadingHistory = (bool) UserSetting::getValue($uid, 'show_reading_history', false);
        $this->showFollowers = (bool) UserSetting::getValue($uid, 'show_followers', true);
        $this->showFollowing = (bool) UserSetting::getValue($uid, 'show_following', true);
        $this->allowDms = (bool) UserSetting::getValue($uid, 'allow_dms', true);
    }

    public function save(): void
    {
        $this->validate([
            'profileVisibility' => 'required|in:public,followers,private',
        ]);

        $uid = auth()->id();
        UserSetting::setValue($uid, 'profile_visibility', $this->profileVisibility);
        UserSetting::setValue($uid, 'show_reading_history', $this->showReadingHistory);
        UserSetting::setValue($uid, 'show_followers', $this->showFollowers);
        UserSetting::setValue($uid, 'show_following', $this->showFollowing);
        UserSetting::setValue($uid, 'allow_dms', $this->allowDms);

        auth()->user()?->logProfileActivity(
            'privacy_updated',
            'Updated privacy preferences.',
            [
                'profile_visibility' => $this->profileVisibility,
                'show_reading_history' => $this->showReadingHistory,
                'show_followers' => $this->showFollowers,
                'show_following' => $this->showFollowing,
                'allow_dms' => $this->allowDms,
            ],
        );

        $this->dispatch('toast', message: 'Privacy preferences saved.');
    }

    public function render(): View
    {
        return view('livewire.visitor.settings.privacy');
    }
}
