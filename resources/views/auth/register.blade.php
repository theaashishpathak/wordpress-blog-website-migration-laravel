@php($title = 'Register')
@extends('layouts.guest')

@section('content')
    @php($companyName = $settings->get('company.name') ?: config('app.name', 'Rupantrix'))
    @php($appTagline = $settings->get('company.tagline') ?: '')
    @php($logoLight = $settings->get('branding.logo'))
    @php($logoDark = $settings->get('branding.logo_dark') ?: $logoLight)
    @php($logoLightUrl = $logoLight ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoLight) : null)
    @php($logoDarkUrl = $logoDark ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoDark) : null)

    <div class="w-full max-w-md space-y-4">
        <div class="rounded-3xl bg-white p-8 shadow-xl dark:bg-slate-900">
            <div class="text-center">
                @if ($logoLightUrl)
                    <img src="{{ $logoLightUrl }}" alt="{{ $companyName }}"
                        class="mx-auto h-16 w-16 rounded-2xl object-contain {{ $logoDarkUrl && $logoDarkUrl !== $logoLightUrl ? 'dark:hidden' : '' }}">
                    @if ($logoDarkUrl && $logoDarkUrl !== $logoLightUrl)
                        <img src="{{ $logoDarkUrl }}" alt="{{ $companyName }}"
                            class="mx-auto hidden h-16 w-16 rounded-2xl object-contain dark:block">
                    @endif
                @else
                    <div
                        class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white">
                        {{ strtoupper(substr($companyName, 0, 1)) }}
                    </div>
                @endif

                <h1 class="mt-6 text-2xl font-bold">Create your account</h1>
                <p class="mt-1 text-xs font-medium uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-300">
                    {{ $appTagline }}</p>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Join {{ $companyName }} to bookmark, follow
                    authors, and more.</p>
            </div>

            <form action="{{ route('register') }}" method="POST" class="mt-8 space-y-4" id="register-form">
                @csrf

                @if ($errors->any())
                    <div
                        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-medium">Name</label>
                    <input type="text" name="name" id="register-name" value="{{ old('name') }}" required autofocus
                        autocomplete="name"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Email</label>
                    <input type="email" name="email" id="register-email" value="{{ old('email') }}" required
                        autocomplete="username"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Password</label>
                    <input type="password" name="password" id="register-password" required autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="register-password-confirmation" required
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                </div>

                <button type="submit"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Create
                    Account</button>

                <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Sign in</a>
                </p>
            </form>
        </div>
    </div>
@endsection
