<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileAvatarController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        abort_unless($user !== null, 403);

        $oldAvatar = $user->avatar;

        $avatarPath = $request->file('avatar')->store('avatars', 'public');

        $user->forceFill([
            'avatar' => $avatarPath,
        ])->save();

        $user->logProfileActivity('avatar_updated', 'Updated profile avatar.');

        if (
            is_string($oldAvatar)
            && $oldAvatar !== ''
            && ! filter_var($oldAvatar, FILTER_VALIDATE_URL)
            && Storage::disk('public')->exists($oldAvatar)
        ) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()
            ->route('profile')
            ->with('status', 'Avatar updated successfully.');
    }
}
