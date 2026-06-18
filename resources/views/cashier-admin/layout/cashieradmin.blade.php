<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HealthInPocket Admin')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>      

    @fluxAppearance

    <link rel="stylesheet" href="{{ asset('vendor/flux/flux.css') }}">

    @livewireStyles

    <link rel="stylesheet" href="{{ asset('assets/common.css') }}">
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    [x-cloak] { display: none !important; }

    aside.sidebar {
        border-top-right-radius: 22px;
        border-bottom-right-radius: 22px;
    }

    .active-menu {
        background: #0da2e7 !important;
        color: #fff !important;
        border-radius: 9999px !important;
        font-weight: 600 !important;
    }
    .active-menu i { color: #fff !important; }

    .bell-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.15s;
        position: relative;
        flex-shrink: 0;
    }
    .bell-btn:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .bell-dot {
        position: absolute;
        top: 6px; right: 6px;
        width: 6px; height: 6px;
        background: #ef4444;
        border-radius: 50%;
        border: 1.5px solid #fff;
    }

    .profile-trigger {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 4px 10px 4px 4px;
        border-radius: 40px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        cursor: pointer;
        transition: all 0.15s;
        color: #374151;
        outline: none;
    }
    .profile-trigger:hover { background: #eff6ff; border-color: #bfdbfe; }
    .profile-avatar-sq {
        width: 28px;
        height: 28px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0da2e7 0%, #38bdf8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 0.04em;
        flex-shrink: 0;
        text-transform: uppercase;
    }
    .profile-trigger .pname {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        line-height: 1.25;
    }
    .profile-trigger .pemail {
        font-size: 10.5px;
        color: #9ca3af;
        line-height: 1.25;
    }
    .profile-trigger .pchev {
        font-size: 9px;
        color: #9ca3af;
        margin-left: 2px;
        transition: transform 0.2s;
    }

    .profile-panel {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 216px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.13), 0 4px 14px rgba(0,0,0,0.07);
        overflow: hidden;
        z-index: 9999;
    }
    .pp-head {
        padding: 14px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-bottom: 1px solid #bae6fd;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .pp-head-avatar {
        width: 38px; height: 38px;
        border-radius: 12px;
        background: linear-gradient(135deg, #0da2e7, #38bdf8);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; color: #fff;
        flex-shrink: 0;
        text-transform: uppercase;
    }
    .pp-head-name { font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.3; }
    .pp-head-email { font-size: 10px; color: #64748b; margin-top: 1px; word-break: break-all; }
    .pp-menu { padding: 6px; }
    .pp-item {
        display: flex; align-items: center; gap: 9px;
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 13px; font-weight: 500; color: #374151;
        text-decoration: none;
        transition: background 0.12s, color 0.12s;
        cursor: pointer; width: 100%; border: none;
        background: none; text-align: left;
    }
    .pp-item i { width: 14px; text-align: center; color: #9ca3af; font-size: 12px; flex-shrink: 0; }
    .pp-item:hover { background: #f3f4f6; color: #111827; }
    .pp-item:hover i { color: #374151; }
    .pp-divider { height: 1px; background: #f1f5f9; margin: 4px 6px; }
    .pp-item.logout { color: #ef4444; }
    .pp-item.logout i { color: #f87171; }
    .pp-item.logout:hover { background: #fef2f2; color: #dc2626; }
    .pp-item.logout:hover i { color: #dc2626; }
    </style>

</head>
<body class="bg-gray-100">

    <div class="flex h-screen w-full overflow-hidden">
    
        <aside class="sidebar bg-white shadow-lg border-r border-gray-200" style="width: 15rem;">
            <div class="p-4 border-b logo-blue">
                <div class="flex items-center justify-center">
                    <img src="{{ asset('assets/healthin-black.png') }}" 
                        alt="Logo" 
                        class="h-9 w-100 object-contain">
                </div>
            </div>
    
            <nav class="p-4 pt-5 space-y-6">
    
                <!-- MAIN MENU -->
                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">MAIN MENU</div>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('cashier.dashboard.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('cashier.dashboard.index') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fa-regular fa-rectangle-list"></i><span>Dashboard</span>
                            </a>
                        </li>
    
                        <li>
                            <a href="{{ route('cashier.payments.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('cashier.payments.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                               <i class="fa-solid fa-indian-rupee-sign"></i><span>Payments</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('cashier.manage-subscriptions.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('cashier.manage-subscriptions.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                               <i class="fa-solid fa-id-card"></i><span>Manage Subscriptions</span>
                            </a>
                        </li>
                    </ul>
                </div>
    
                <!-- SETTINGS -->
                {{-- <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">SETTINGS</div>
                    <ul class="space-y-1">
                        <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100">
                            <i class="fas fa-cog"></i><span>Settings</span></a></li>
    
                        <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100">
                            <i class="fas fa-user-circle"></i><span>Manage Profile</span></a></li>
                    </ul>
                </div> --}}
    
            </nav>
        </aside>
    
        <div class="flex-1 flex flex-col overflow-y-auto">
    
            <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 header-blue">
                <div class="flex justify-between items-center">
            
                    <!-- Left side (empty or breadcrumb) -->
                    <div class="text-sm text-gray-600"></div>
            
                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
            
                        <button class="bell-btn" type="button">
                            <i class="fas fa-bell" style="font-size:13px;"></i>
                            <span class="bell-dot"></span>
                        </button>
            
                        <!-- Messages -->
                        {{-- <button class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-paper-plane"></i>
                        </button> --}}
            
                        <!-- Profile Dropdown -->
                        <div class="relative" x-data="{ profileOpen: false }">
                            @php
                                $u = auth()->user();
                                $email = (string) ($u?->email ?? '');
                                $name = trim((string) ($u?->full_name ?? 'Cashier Admin')) ?: 'Cashier Admin';
                                $initials = strtoupper(substr($name, 0, 1) . substr($email, 0, 1));
                            @endphp
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
                                    <form method="POST" action="{{ route('cashier.auth.logout') }}">
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
    
            <main class="flex-1 bg-gray-100 p-6 overflow-y-auto">
                @yield('content')
            </main>
    
        </div>
    </div>

    <script src="{{ asset('assets/common.js') }}"></script>
    {{-- <script>
        function toggleActionMenu(event, id) {
            event.stopPropagation();

            document.querySelectorAll(".action-menu").forEach(m => {
                if (m.id !== id) m.classList.add("hidden");
            });
        }

    </script> --}}

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', ({ type, message }) => {

                let toastClass = 'glass-toast';

                if (type === 'success') toastClass += ' toast-success';
                if (type === 'error') toastClass += ' toast-error';
                if (type === 'warning') toastClass += ' toast-warning';
                if (type === 'info') toastClass += ' toast-info';

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: type,
                    title: message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: {
                        popup: toastClass
                    }
                });
            });
        });
    </script>
@stack('scripts')
@livewireScripts
@fluxScripts
<script>
    (function () {
        if (typeof window.fluxModal === 'function') return;

        var fallback = document.createElement('script');
        fallback.src = '/flux/flux.min.js?v={{ now()->timestamp }}';
        fallback.setAttribute('data-navigate-once', '');
        document.body.appendChild(fallback);
    })();
</script>
</body>
</html>
