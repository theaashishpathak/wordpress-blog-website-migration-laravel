@php
    $name = $tag->translate('name') ?? $tag->name;
    $color = $tag->color ?? '#4f46e5';
@endphp

<div>
    {{-- Hero banner --}}
    <header class="relative overflow-hidden border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
        <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 20% 80%, {{ $color }}33 0%, transparent 50%);"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-12 lg:py-14">
            <nav class="mb-5 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-neutral-400" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Home</a>
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300 dark:text-neutral-600"></i>
                <span class="text-slate-400 dark:text-neutral-500">Tags</span>
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300 dark:text-neutral-600"></i>
                <span class="text-slate-600 dark:text-neutral-300 font-medium truncate">#{{ $name }}</span>
            </nav>

            <h1 class="flex items-baseline gap-2 text-4xl font-black tracking-tight text-slate-900 md:text-5xl dark:text-slate-100"
                style="font-family: 'Playfair Display', serif;">
                <span style="color: {{ $color }};">#</span>{{ $name }}
            </h1>
            <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5 font-bold text-slate-600 dark:text-neutral-300">
                    <i data-lucide="file-text" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    {{ $this->posts->total() }} {{ \Illuminate\Support\Str::plural('article', $this->posts->total()) }} tagged
                </span>
                <livewire:frontend.follow-button targetType="tag" :targetId="$tag->id" :wire:key="'follow-tag-'.$tag->id" />
            </div>
        </div>
    </header>

    <section class="mx-auto max-w-7xl px-4 py-10 lg:py-12">
        @if ($this->posts->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center dark:border-slate-700">
                <i data-lucide="hash" class="mx-auto h-12 w-12 text-slate-300"></i>
                <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-slate-100">No articles with this tag yet</h3>
                <p class="mt-1 text-sm text-slate-500">Browse other tags or explore our homepage.</p>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->posts as $post)
                    <x-frontend.post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">{{ $this->posts->onEachSide(1)->links('livewire.frontend.pagination', ['scrollTo' => 'header']) }}</div>
        @endif
    </section>
</div>
