@props([
    'menu' => null,
])

@php
    $menuKey = $menu ?? match (true) {
        request()->routeIs('admin.*') => 'admin',
        request()->routeIs('healthcare.*') => 'healthcare',
        request()->routeIs('doctor.*') => 'doctor',
        request()->routeIs('cashier.*') => 'cashier',
        request()->routeIs('pharmacist.*') => 'pharmacist',
        request()->routeIs('technician.*') => 'technician',
        request()->routeIs('receptionist.*') => 'receptionist',
        default => 'admin',
    };

    $isScrollable = $menuKey === 'admin' || $menuKey === 'healthcare';
@endphp

@if ($isScrollable)
    <aside {{ $attributes->merge(['class' => 'sidebar w-70 bg-white shadow-lg border-r border-gray-200 flex flex-col h-screen min-h-0 overflow-hidden']) }} @if ($menuKey === 'healthcare') style="width:15rem" @endif>
        <div class="p-4 border-b logo-blue flex-shrink-0">
            <div class="flex items-center justify-center">
                <img src="{{ asset('assets/healthin-black.png') }}"
                     alt="Logo" class="h-9 w-100 object-contain">
            </div>
        </div>

        <nav class="p-4 pt-5 pb-8 space-y-6 flex-1 overflow-y-auto min-h-0 sidebar-nav" @if ($menuKey === 'healthcare') style="width:16rem" @endif>
            @include('components.menus.' . $menuKey)
        </nav>
    </aside>
@else
    <aside {{ $attributes->merge(['class' => 'sidebar bg-white shadow-lg border-r border-gray-200']) }} style="width: 15rem;">
        <div class="p-4 border-b logo-blue">
            <div class="flex items-center justify-center">
                <img src="{{ asset('assets/healthin-black.png') }}"
                     alt="Logo" class="h-9 w-100 object-contain">
            </div>
        </div>

        <nav class="p-4 pt-5 space-y-6">
            @include('components.menus.' . $menuKey)
        </nav>
    </aside>
@endif
