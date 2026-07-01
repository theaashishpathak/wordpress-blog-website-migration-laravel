<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'timezone' => ['nullable', 'string', 'timezone'],
            'locale' => ['nullable', 'string', 'max:10'],
        ])->validateWithBag('updateProfileInformation');

        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? $user->phone,
            'mobile' => $input['mobile'] ?? $user->mobile,
            'gender' => $input['gender'] ?? $user->gender,
            'date_of_birth' => $input['date_of_birth'] ?? $user->date_of_birth,
            'timezone' => $input['timezone'] ?? $user->timezone,
            'locale' => $input['locale'] ?? $user->locale,
        ]);

        $changedFields = array_keys($user->getDirty());

        $user->save();

        if ($changedFields !== []) {
            $user->logProfileActivity(
                'profile_information_updated',
                'Updated profile information.',
                ['changed_fields' => $changedFields],
            );
        }
    }
}
