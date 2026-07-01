@props(['icon' => null, 'title', 'href' => '#', 'active' => false, 'isDropdown' => false, 'children' => []])

@php
    $menuBaseClass = 'sidebar-menu-item';
    $menuActiveClass = 'sidebar-menu-item-active';
    $menuInactiveClass = 'sidebar-menu-item-inactive';
    $childMenuBaseClass = 'sidebar-submenu-item';
    $childMenuActiveClass = 'sidebar-submenu-item-active';
    $childMenuInactiveClass = 'sidebar-submenu-item-inactive';
    $dropdownId = \Illuminate\Support\Str::slug($title).'-sidebar-menu';
@endphp

<div>
    @if($isDropdown)
        <button
            type="button"
            class="{{ $menuBaseClass }} sidebar-menu-parent justify-between {{ $active ? $menuActiveClass : $menuInactiveClass }}"
            data-sidebar-menu-toggle="{{ $dropdownId }}"
            aria-controls="{{ $dropdownId }}"
            aria-expanded="{{ $active ? 'true' : 'false' }}"
        >
            <span class="flex min-w-0 flex-1 items-center gap-3">
                @if($icon)
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i>
                @endif
                <span data-sidebar-label class="truncate">{{ $title }}</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-menu-chevron" data-sidebar-label></i>
        </button>

        <div data-sidebar-submenu="{{ $dropdownId }}" class="sidebar-submenu {{ $active ? '' : 'hidden' }}">
            @foreach($children as $child)
                <a href="{{ $child['href'] }}" class="{{ $childMenuBaseClass }} {{ $child['active'] ? $childMenuActiveClass : $childMenuInactiveClass }}">
                    <span data-sidebar-label class="truncate">{{ $child['title'] }}</span>
                </a>
            @endforeach
        </div>
    @else
        <a href="{{ $href }}" class="{{ $menuBaseClass }} {{ $active ? $menuActiveClass : $menuInactiveClass }}">
            @if($icon)
                <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i>
            @endif
            <span data-sidebar-label class="truncate">{{ $title }}</span>
        </a>
    @endif
</div>
