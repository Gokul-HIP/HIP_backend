@php
    $prefix = $prefix ?? 'cashier';
    $activeExtra = 'active-menu bg-blue-50 text-blue-700';
@endphp

<div>
    <div class="text-xs font-semibold text-gray-500 mb-2">MAIN MENU</div>
    <ul class="space-y-1">
        <li>
            <a href="{{ route($prefix . '.dashboard.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs($prefix . '.dashboard.index') ? $activeExtra : 'hover:bg-gray-100' }}">
                <i class="fa-regular fa-rectangle-list"></i><span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="{{ route($prefix . '.payments.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs($prefix . '.payments.*') ? $activeExtra : 'hover:bg-gray-100' }}">
               <i class="fa-solid fa-indian-rupee-sign"></i><span>Payments</span>
            </a>
        </li>

        <li>
            <a href="{{ route($prefix . '.manage-subscriptions.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs($prefix . '.manage-subscriptions.*') ? $activeExtra : 'hover:bg-gray-100' }}">
               <i class="fa-solid fa-id-card"></i><span>Manage Subscriptions</span>
            </a>
        </li>
    </ul>
</div>

{{-- <div>
    <div class="text-xs font-semibold text-gray-500 mb-2">SETTINGS</div>
    <ul class="space-y-1">
        <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100">
            <i class="fas fa-cog"></i><span>Settings</span></a></li>

        <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100">
            <i class="fas fa-user-circle"></i><span>Manage Profile</span></a></li>
    </ul>
</div> --}}
