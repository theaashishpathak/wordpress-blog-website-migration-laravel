@props([
    'rows' => 3,
    'columns' => 1,
    'tall' => false,
])

{{--
    Generic skeleton placeholder. Use with wire:loading.delay or inside
    @placeholder for slow Livewire components. Examples:

      <div wire:loading wire:target="search">
          <x-admin.skeleton rows="5" />
      </div>

      // taller variant for header / stat tiles
      <x-admin.skeleton rows="2" tall />
--}}
<div role="status" class="animate-pulse space-y-3" {{ $attributes }}>
    @for ($i = 0; $i < (int) $rows; $i++)
        <div class="grid gap-3 sm:grid-cols-{{ max(1, (int) $columns) }}">
            @for ($c = 0; $c < max(1, (int) $columns); $c++)
                <div @class([
                    'rounded-xl bg-slate-200 dark:bg-slate-800',
                    'h-20' => $tall,
                    'h-4' => ! $tall,
                ])></div>
            @endfor
        </div>
    @endfor
    <span class="sr-only">Loading…</span>
</div>
