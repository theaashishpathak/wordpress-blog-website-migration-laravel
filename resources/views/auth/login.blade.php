@php($title = 'Login')
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
                    <img src="{{ $logoLightUrl }}" alt="{{ $companyName }}" class="mx-auto h-16 w-16 rounded-2xl object-contain {{ $logoDarkUrl && $logoDarkUrl !== $logoLightUrl ? 'dark:hidden' : '' }}">
                    @if ($logoDarkUrl && $logoDarkUrl !== $logoLightUrl)
                        <img src="{{ $logoDarkUrl }}" alt="{{ $companyName }}" class="mx-auto hidden h-16 w-16 rounded-2xl object-contain dark:block">
                    @endif
                @else
                    <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white">
                        {{ strtoupper(substr($companyName, 0, 1)) }}
                    </div>
                @endif

                <h1 class="mt-6 text-2xl font-bold">Sign in to {{ $companyName }}</h1>
                <p class="mt-1 text-xs font-medium uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-300">{{ $appTagline }}</p>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Welcome back — sign in to manage your business or services.</p>
            </div>

            <form action="{{ route('login') }}" method="POST" class="mt-8 space-y-4" id="login-form">
                @csrf

                @if ($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-medium">Email</label>
                    <input type="email" name="email" id="login-email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Password</label>
                    <input type="password" name="password" id="login-password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Forgot password?</a>
                </div>

                <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Enter Dashboard</button>
            </form>
        </div>


    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>
        <script>
            (function () {
                if (window.lucide) window.lucide.createIcons();

                document.querySelectorAll('[data-demo-email]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const emailInput = document.getElementById('login-email');
                        const passwordInput = document.getElementById('login-password');
                        if (emailInput) emailInput.value = btn.dataset.demoEmail;
                        if (passwordInput) passwordInput.value = btn.dataset.demoPassword;
                        emailInput?.focus();
                    });
                });
            })();
        </script>
    @endpush
@endsection
