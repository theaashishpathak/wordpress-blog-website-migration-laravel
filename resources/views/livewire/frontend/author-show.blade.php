@php
    $social = is_array($author->social_links) ? $author->social_links : [];
    $socialIcons = [
        'twitter' => 'twitter',
        'facebook' => 'facebook',
        'linkedin' => 'linkedin',
        'instagram' => 'instagram',
        'youtube' => 'youtube',
        'website' => 'globe',
    ];
@endphp

<div>
    {{-- Hero profile banner --}}
    <header class="relative overflow-hidden border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
        <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 80% 20%, #10b98133 0%, transparent 50%);"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-12 lg:py-16">
            {{-- Breadcrumb --}}
            <nav class="mb-5 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-neutral-400" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Home</a>
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300 dark:text-neutral-600"></i>
                <span class="text-slate-400 dark:text-neutral-500">Authors</span>
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300 dark:text-neutral-600"></i>
                <span class="text-slate-600 dark:text-neutral-300 font-medium truncate">{{ $author->name }}</span>
            </nav>

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 sm:gap-6">
                {{-- Avatar --}}
                <div class="relative h-20 w-20 sm:h-24 sm:w-24 shrink-0 overflow-hidden rounded-2xl ring-2 ring-emerald-500/20 shadow-md bg-white dark:bg-slate-800 flex items-center justify-center">
                    @if ($author->avatar)
                        <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="grid h-full w-full place-items-center bg-gradient-to-br from-emerald-500/10 to-teal-500/20 text-emerald-700 dark:text-emerald-300 text-3xl sm:text-4xl font-black uppercase">
                            {{ mb_substr($author->name, 0, 1) }}
                        </span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-black leading-tight tracking-tight text-slate-900 sm:text-4xl md:text-5xl dark:text-slate-100"
                        style="font-family: 'Playfair Display', serif;">
                        {{ $author->name }}
                    </h1>

                    @if ($author->bio)
                        <p class="mt-2.5 max-w-2xl text-sm sm:text-base leading-relaxed text-slate-600 dark:text-neutral-300">
                            {{ $author->bio }}
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1.5 font-bold text-slate-600 dark:text-neutral-300">
                            <i data-lucide="file-text" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"></i>
                            {{ $this->posts->total() }} {{ \Illuminate\Support\Str::plural('article', $this->posts->total()) }}
                        </span>

                        <livewire:frontend.follow-button targetType="author" :targetId="$author->id" :wire:key="'follow-author-'.$author->id" />

                        @if (! empty($social))
                            <div class="flex flex-wrap items-center gap-1.5 sm:ml-2">
                                @foreach ($socialIcons as $platform => $icon)
                                    @if (! empty($social[$platform]))
                                        @php
                                            $href = $social[$platform];
                                            if (! str_starts_with($href, 'http') && $platform !== 'website') {
                                                $handle = ltrim($href, '@');
                                                $href = match ($platform) {
                                                    'twitter' => "https://twitter.com/{$handle}",
                                                    'facebook' => "https://facebook.com/{$handle}",
                                                    'linkedin' => "https://linkedin.com/in/{$handle}",
                                                    'instagram' => "https://instagram.com/{$handle}",
                                                    'youtube' => "https://youtube.com/@{$handle}",
                                                    default => $href,
                                                };
                                            }
                                        @endphp
                                        <a href="{{ $href }}" target="_blank" rel="noopener"
                                           aria-label="{{ $platform }}"
                                           class="grid h-7 w-7 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-500 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-neutral-300 dark:hover:text-emerald-400">
                                            <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Author's posts --}}
    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="mb-6 flex items-end justify-between gap-3 border-b-2 border-slate-900 pb-3 dark:border-slate-100">
            <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
                Latest from {{ $author->name }}
            </h2>
        </div>

        @if ($this->posts->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center dark:border-slate-700">
                <i data-lucide="user" class="mx-auto h-12 w-12 text-slate-300"></i>
                <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-slate-100">No articles yet</h3>
                <p class="mt-1 text-sm text-slate-500">This author hasn't published any articles yet.</p>
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
