@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'NewsPilot AI'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invalid link — {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-xl">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-rose-500 text-white shadow-lg">
                <i data-lucide="alert-triangle" class="h-8 w-8"></i>
            </span>
            <h1 class="mt-6 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                This link is no longer valid
            </h1>
            <p class="mt-3 text-sm text-slate-600">
                The link may have expired or been used already. Try signing up again from the homepage —
                we'll send you a fresh confirmation email.
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
