@php
    $settings = app(\App\Services\SettingService::class);
    $companyName = $settings->get('company.name') ?: config('app.name', 'NewsPilot AI');
@endphp

<footer class="border-t border-slate-200 px-4 py-5 text-xs text-slate-500 sm:px-6 dark:border-slate-800 dark:text-slate-400">
    <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
        <p>
            <span class="font-bold tracking-tight text-slate-700 dark:text-slate-300" style="font-family: 'Playfair Display', serif;">{{ $companyName }}</span>
            <span class="mx-1.5 text-slate-300 dark:text-slate-600">·</span>
            Reader Portal
            <span class="mx-1.5 text-slate-300 dark:text-slate-600">·</span>
            © {{ now()->year }}
        </p>
        <p class="italic" style="font-family: 'Playfair Display', serif;">
            For the curious reader.
        </p>
    </div>
</footer>
