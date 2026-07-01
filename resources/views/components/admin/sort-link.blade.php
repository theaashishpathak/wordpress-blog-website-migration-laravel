@props([
    'field',                  // the column name to sort by
    'current' => null,        // the currently-sorted column from the component
    'direction' => 'asc',     // current direction
    'method' => 'sortBy',     // Livewire method name to call (defaults to sortBy)
    'label' => null,
])

{{--
    Sortable table-header helper. Usage:

      <th>
          <x-admin.sort-link field="title" :current="$sortField" :direction="$sortDirection">
              Title
          </x-admin.sort-link>
      </th>

    Default slot is the visible column label. Clicking it calls
    wire:click="sortBy('{{ $field }}')" on the parent component.

    The component must implement `sortBy(string $field): void` and
    track `$sortField` + `$sortDirection` public properties.
--}}
@php
    $isActive = $current === $field;
    $nextIcon = $isActive
        ? ($direction === 'asc' ? 'arrow-up' : 'arrow-down')
        : 'chevrons-up-down';
@endphp

<button
    type="button"
    wire:click="{{ $method }}('{{ $field }}')"
    class="inline-flex items-center gap-1 font-bold uppercase tracking-wider text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100 {{ $isActive ? 'text-slate-900 dark:text-slate-100' : '' }}"
>
    @if ($label)
        {{ $label }}
    @else
        {{ $slot }}
    @endif
    <i data-lucide="{{ $nextIcon }}" class="h-3 w-3 {{ $isActive ? 'opacity-100' : 'opacity-40' }}"></i>
</button>
