@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'NewsPilot AI'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription confirmed — {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-indigo-50 via-violet-50 to-fuchsia-50 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-xl">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-500 text-white shadow-lg">
                <i data-lucide="check" class="h-8 w-8"></i>
            </span>
            <h1 class="mt-6 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                You're confirmed!
            </h1>
            <p class="mt-3 text-sm text-slate-600">
                Thanks for confirming your subscription to <strong>{{ $siteName }}</strong>.
                You'll start receiving our newsletter from the next edition.
            </p>
            <a href="{{ url('/') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:from-indigo-700">
                <i data-lucide="home" class="h-4 w-4"></i>
                Back to homepage
            </a>
        </div>
    </div>
</body>
</html>
