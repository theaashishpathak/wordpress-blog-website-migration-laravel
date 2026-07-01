@php($title = 'Profile')
@extends('layouts.app')

@section('content')
    @php($user = auth()->user())
    @php($activityLogs = $activityLogs ?? collect())

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Profile Settings</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Update your profile information, password, and two-factor authentication.</p>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm p-5 dark:bg-slate-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <img src="{{ $user?->avatarUrl() }}" alt="Avatar" class="h-20 w-20 rounded-full object-cover">
                    <div>
                        <h2 class="text-lg font-semibold">Profile Avatar</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Upload a new avatar to update the topbar and sidebar everywhere in the app.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="space-y-3 sm:min-w-80">
                    @csrf

                    <div>
                        <label class="mb-2 block text-sm font-medium">Choose Avatar</label>
                        <input type="file" name="avatar" accept="image/*" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('avatar')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Update Avatar</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 dark:bg-slate-900">
            <h2 class="text-lg font-semibold">Profile Information</h2>
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <!-- Basic Information -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('name', 'updateProfileInformation')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('email', 'updateProfileInformation')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" placeholder="+1 (555) 000-0000" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('phone', 'updateProfileInformation')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Mobile</label>
                        <input type="tel" name="mobile" value="{{ old('mobile', auth()->user()?->mobile) }}" placeholder="+1 (555) 000-0000" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('mobile', 'updateProfileInformation')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Gender</label>
                        <select name="gender" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                            <option value="">Select Gender</option>
                            <option value="male" @selected(old('gender', auth()->user()?->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', auth()->user()?->gender) === 'female')>Female</option>
                            <option value="other" @selected(old('gender', auth()->user()?->gender) === 'other')>Other</option>
                        </select>
                        @error('gender', 'updateProfileInformation')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Date of Birth</label>
                        <input type="text" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', auth()->user()?->date_of_birth?->format('Y-m-d')) }}" placeholder="Select date" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('date_of_birth', 'updateProfileInformation')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Timezone</label>
                        <select name="timezone" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                            @php($timezones = \DateTimeZone::listIdentifiers())
                            @php($userTimezone = old('timezone', auth()->user()?->timezone ?? 'UTC'))
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}" @selected($userTimezone === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone', 'updateProfileInformation')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Language -->
                <div>
                    <label class="mb-2 block text-sm font-medium">Language</label>
                    <select name="locale" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        <option value="">Select Language</option>
                        <option value="en" @selected(old('locale', auth()->user()?->locale) === 'en')>English</option>
                        <option value="es" @selected(old('locale', auth()->user()?->locale) === 'es')>Español</option>
                        <option value="fr" @selected(old('locale', auth()->user()?->locale) === 'fr')>Français</option>
                        <option value="de" @selected(old('locale', auth()->user()?->locale) === 'de')>Deutsch</option>
                        <option value="it" @selected(old('locale', auth()->user()?->locale) === 'it')>Italiano</option>
                        <option value="pt" @selected(old('locale', auth()->user()?->locale) === 'pt')>Português</option>
                    </select>
                    @error('locale', 'updateProfileInformation')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Save Profile</button>
            </form>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="bg-white rounded-xl shadow-sm p-5 dark:bg-slate-900">
                <h2 class="text-lg font-semibold">Update Password</h2>
                <form method="POST" action="{{ route('user-password.update') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-2 block text-sm font-medium">Current Password</label>
                        <input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('current_password', 'updatePassword')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Password</label>
                        <input type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @error('password', 'updatePassword')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Confirm Password</label>
                        <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Update Password</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-5 dark:bg-slate-900">
                <h2 class="text-lg font-semibold">Two-Factor Authentication</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Add additional security to your account by enabling two-factor authentication.</p>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Enable 2FA</button>
                    </form>

                    <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Disable 2FA</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 dark:bg-slate-900">
            <h2 class="text-lg font-semibold">Activity Log</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Recent changes made to your profile and account security settings.</p>

            @if ($activityLogs->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    No activity yet.
                </div>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($activityLogs as $activityLog)
                        <li class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $activityLog->description }}</p>
                                    @if (! empty($activityLog->meta['changed_fields']) && is_array($activityLog->meta['changed_fields']))
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Fields: {{ implode(', ', $activityLog->meta['changed_fields']) }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ $activityLog->created_at?->diffForHumans() }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if (method_exists($activityLogs, 'hasPages') && $activityLogs->hasPages())
                    <div class="mt-4">
                        {{ $activityLogs->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr('#date_of_birth', {
            mode: 'single',
            dateFormat: 'Y-m-d',
            maxDate: 'today',
            disableMobile: false,
        });
    </script>
@endsection

