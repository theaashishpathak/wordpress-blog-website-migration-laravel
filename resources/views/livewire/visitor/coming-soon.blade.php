<div class="mx-auto max-w-2xl px-4 py-16">
    <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-sky-500 text-white shadow-md">
            <i data-lucide="{{ $icon }}" class="h-7 w-7"></i>
        </span>

        <p class="mt-6 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-300">Reader Portal</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 md:text-4xl dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
            {{ $section }}
        </h1>
        <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ $description }}</p>

        <span class="mt-6 inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">
            <i data-lucide="hammer" class="h-3 w-3"></i>
            Coming soon
        </span>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('visitor.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                Back to Dashboard
            </a>
            <a href="{{ route('frontend.home') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:from-emerald-600 hover:to-teal-600">
                <i data-lucide="newspaper" class="h-4 w-4"></i>
                Browse articles
            </a>
        </div>
    </div>
</div>
