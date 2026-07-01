@props([
    'slot',
    'class' => '',
])

@php
    $zone = \App\Models\AdZone::query()->active()->byKey($slot)->first();

    $creatives = $zone === null
        ? collect()
        : \App\Models\AdCreative::query()
            ->where('zone_id', $zone->id)
            ->servable()
            ->orderBy('priority')
            ->inRandomOrder()
            ->limit($zone->max_creatives)
            ->get();

    // Increment impressions for every creative we're about to render.
    // We do this inline (instead of via JS beacon) because the alternative
    // is shipping JS that the user could block; server-side is reliable.
    foreach ($creatives as $c) {
        \Illuminate\Support\Facades\DB::table('ad_creatives')->whereKey($c->id)->increment('impression_count');
    }
@endphp

@if ($zone === null || $creatives->isEmpty())
    {{-- Render an invisible spacer if width/height defined, otherwise nothing. --}}
    @if ($zone && $zone->width && $zone->height)
        <div class="ad-zone-empty {{ $class }}"
             style="width:{{ $zone->width }}px;height:{{ $zone->height }}px;"
             aria-hidden="true"></div>
    @endif
@else
    <div class="ad-zone {{ $class }}"
         data-slot="{{ $zone->key }}">
        @foreach ($creatives as $creative)
            @switch ($creative->type)
                @case (\App\Models\AdCreative::TYPE_IMAGE)
                    <a href="{{ route('ads.click', ['creative' => $creative->id]) }}"
                       target="_blank" rel="noopener sponsored"
                       class="block">
                        @if ($creative->media)
                            <img src="{{ $creative->media->url() }}"
                                 alt="{{ $creative->alt_text ?? $creative->name }}"
                                 @if ($zone->width) width="{{ $zone->width }}" @endif
                                 @if ($zone->height) height="{{ $zone->height }}" @endif
                                 loading="lazy"
                                 class="max-w-full">
                        @endif
                    </a>
                    @break

                @case (\App\Models\AdCreative::TYPE_HTML)
                    {!! $creative->html_code !!}
                    @break

                @case (\App\Models\AdCreative::TYPE_SPONSORED)
                    <a href="{{ route('ads.click', ['creative' => $creative->id]) }}"
                       target="_blank" rel="noopener sponsored"
                       class="block overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-4 transition hover:shadow-md dark:border-amber-500/30 dark:from-amber-500/10 dark:to-orange-500/10">
                        <div class="flex items-center gap-3">
                            @if ($creative->media)
                                <img src="{{ $creative->media->url() }}"
                                     alt="{{ $creative->alt_text ?? $creative->name }}"
                                     class="h-16 w-16 rounded-xl object-cover">
                            @endif
                            <div class="min-w-0">
                                <span class="rounded-md bg-amber-200 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-800">Sponsored</span>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ $creative->name }}</p>
                                @if ($creative->alt_text)
                                    <p class="truncate text-xs text-slate-600 dark:text-slate-400">{{ $creative->alt_text }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                    @break
            @endswitch
        @endforeach
    </div>
@endif
