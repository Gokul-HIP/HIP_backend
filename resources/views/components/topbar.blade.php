@props([
    'logoutRoute' => null,
    'defaultName' => null,
    'actionsSpacing' => null,
])

@php
    $logoutRouteName = $logoutRoute ?? match (true) {
        request()->routeIs('healthcare.*') => 'admin.auth.logout',
        request()->routeIs('admin.*') => 'admin.auth.logout',
        request()->routeIs('doctor.*') => 'doctor.auth.logout',
        request()->routeIs('cashier.*') => 'cashier.auth.logout',
        request()->routeIs('pharmacist.*') => 'pharmacist.auth.logout',
        request()->routeIs('technician.*') => 'technician.auth.logout',
        request()->routeIs('receptionist.*') => 'receptionist.auth.logout',
        default => 'admin.auth.logout',
    };

    $fallbackName = $defaultName ?? match (true) {
        request()->routeIs('healthcare.*') => 'Healthcare Admin',
        request()->routeIs('admin.*') => 'Super Admin',
        request()->routeIs('doctor.*') => 'Doctor Admin',
        request()->routeIs('cashier.*') => 'Cashier Admin',
        request()->routeIs('pharmacist.*') => 'Pharmacist',
        request()->routeIs('technician.*') => 'Technician',
        request()->routeIs('receptionist.*') => 'Receptionist',
        default => 'Admin',
    };

    $spacing = $actionsSpacing ?? (request()->routeIs('admin.*', 'healthcare.*') ? 'space-x-3' : 'space-x-4');

    $u = auth()->user();
    $email = (string) ($u?->email ?? '');
    $name = trim((string) ($u?->full_name ?? $fallbackName)) ?: $fallbackName;
    $initials = strtoupper(substr($name, 0, 1) . substr($email, 0, 1));
@endphp

<header {{ $attributes->merge(['class' => 'topbar shadow-sm border-b border-gray-200 px-8 py-4 header-blue']) }}>
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600"></div>

        <div class="flex items-center {{ $spacing }}">
            <button class="bell-btn" type="button">
                <i class="fas fa-bell" style="font-size:13px;"></i>
                <span class="bell-dot"></span>
            </button>

            <div class="relative" x-data="{ profileOpen: false }">
                <button type="button"
                        @click="profileOpen = !profileOpen"
                        class="profile-trigger">
                    <div class="profile-avatar-sq">{{ $initials }}</div>

                    <div class="text-left leading-tight">
                        <div class="pname">{{ $name }}</div>
                        <div class="pemail">{{ $email }}</div>
                    </div>

                    <i class="fas fa-chevron-down pchev"
                       :class="profileOpen ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="profileOpen"
                     x-cloak
                     @click.away="profileOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="profile-panel">
                    <div class="pp-head">
                        <div class="pp-head-avatar">{{ $initials }}</div>
                        <div>
                            <div class="pp-head-name">{{ $name }}</div>
                            <div class="pp-head-email">{{ $email }}</div>
                        </div>
                    </div>

                    <div class="pp-menu">
                        <a href="#" class="pp-item">
                            <i class="fas fa-user-circle"></i> Profile
                        </a>
                        <a href="#" class="pp-item">
                            <i class="fas fa-list"></i> Account
                        </a>
                        <div class="pp-divider"></div>
                        <form method="POST" action="{{ route($logoutRouteName) }}">
                            @csrf
                            <button type="submit" class="pp-item logout">
                                <i class="fa-solid fa-right-from-bracket"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
