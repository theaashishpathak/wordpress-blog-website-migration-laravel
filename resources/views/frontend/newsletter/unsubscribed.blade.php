@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'NewsPilot AI'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed — {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-xl">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-slate-500 text-white shadow-lg">
                <i data-lucide="check" class="h-8 w-8"></i>
            </span>
            <h1 class="mt-6 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                You've unsubscribed
            </h1>
            <p class="mt-3 text-sm text-slate-600">
                You won't receive any more newsletter emails from <strong>{{ $siteName }}</strong>.
                Changed your mind? Sign up again anytime from the homepage footer.
            </p>
            <a href="{{ url('/') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                <i data-lucide="home" class="h-4 w-4"></i>
                Back to homepage
            </a>
        </div>
    </div>
</body>
</html>
