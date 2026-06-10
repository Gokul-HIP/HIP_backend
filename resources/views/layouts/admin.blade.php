<!DOCTYPE html>
<html lang="en" class="light" style="color-scheme: light;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HealthInPocket Admin')</title>

    {{--
        HEAD ASSET ORDER:
        1. Flux CSS (manual — your Flux version doesn't have @fluxStyles directive)
        2. Vite bundle (app.css + app.js — but app.js must NOT import/start Alpine)
        3. Third-party CSS (FontAwesome, etc.)
        4. Livewire styles
        5. Your own CSS
    --}}
    <link rel="stylesheet" href="{{ asset('vendor/flux/flux.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @livewireStyles

    <link rel="stylesheet" href="{{ asset('assets/common.css') }}">
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Flux appearance (dark/light mode cookie) --}}
    @fluxAppearance

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
        width: 34px; height: 34px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        display: flex; align-items: center; justify-content: center;
        color: #6b7280; cursor: pointer;
        transition: all 0.15s; position: relative; flex-shrink: 0;
    }
    .bell-btn:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .bell-dot {
        position: absolute; top: 6px; right: 6px;
        width: 6px; height: 6px;
        background: #ef4444; border-radius: 50%; border: 1.5px solid #fff;
    }

    .profile-trigger {
        display: inline-flex; align-items: center; gap: 9px;
        padding: 4px 10px 4px 4px; border-radius: 40px;
        border: 1px solid #e5e7eb; background: #f9fafb;
        cursor: pointer; transition: all 0.15s; color: #374151; outline: none;
    }
    .profile-trigger:hover { background: #eff6ff; border-color: #bfdbfe; }
    .profile-avatar-sq {
        width: 28px; height: 28px; border-radius: 10px;
        background: linear-gradient(135deg, #0da2e7 0%, #38bdf8 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700; color: #fff;
        letter-spacing: 0.04em; flex-shrink: 0; text-transform: uppercase;
    }
    .profile-trigger .pname  { font-size: 13px; font-weight: 600; color: #111827; line-height: 1.25; }
    .profile-trigger .pemail { font-size: 10.5px; color: #9ca3af; line-height: 1.25; }
    .profile-trigger .pchev  { font-size: 9px; color: #9ca3af; margin-left: 2px; transition: transform 0.2s; }

    .profile-panel {
        position: absolute; top: calc(100% + 8px); right: 0; width: 216px;
        background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.13), 0 4px 14px rgba(0,0,0,0.07);
        overflow: hidden; z-index: 9999;
    }
    .pp-head {
        padding: 14px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-bottom: 1px solid #bae6fd;
        display: flex; align-items: center; gap: 10px;
    }
    .pp-head-avatar {
        width: 38px; height: 38px; border-radius: 12px;
        background: linear-gradient(135deg, #0da2e7, #38bdf8);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0; text-transform: uppercase;
    }
    .pp-head-name  { font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.3; }
    .pp-head-email { font-size: 10px; color: #64748b; margin-top: 1px; word-break: break-all; }
    .pp-menu { padding: 6px; }
    .pp-item {
        display: flex; align-items: center; gap: 9px; padding: 8px 10px;
        border-radius: 10px; font-size: 13px; font-weight: 500; color: #374151;
        text-decoration: none; transition: background 0.12s, color 0.12s;
        cursor: pointer; width: 100%; border: none; background: none; text-align: left;
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

        <aside class="sidebar w-70 bg-white shadow-lg border-r border-gray-200 flex flex-col h-screen min-h-0 overflow-hidden">
            <div class="p-4 border-b logo-blue flex-shrink-0">
                <div class="flex items-center justify-center">
                    <img src="{{ asset('assets/healthin-black.png') }}"
                         alt="Logo" class="h-9 w-100 object-contain">
                </div>
            </div>

            <nav class="p-4 pt-5 pb-8 space-y-6 flex-1 overflow-y-auto min-h-0 sidebar-nav">

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">MAIN MENU</div>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('admin.dashboard.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.dashboard.index') ? 'active-menu' : 'hover:bg-gray-100' }}">
                                <i class="fa-regular fa-rectangle-list"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.organizations.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.organizations.*') ? 'active-menu' : 'hover:bg-gray-100' }}">
                                <i class="fas fa-building"></i><span>Organization</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.hospital-onboarding.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.hospital-onboarding.*') ? 'active-menu' : 'hover:bg-gray-100' }}">
                                <i class="fa-solid fa-hospital"></i><span>Hospital Onboarding</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.wellness-services.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.wellness-services.*') ? 'active-menu' : 'hover:bg-gray-100' }}">
                                <i class="fa-solid fa-spa"></i><span>Wellness Services</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.caregiver.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.caregiver.*') ? 'active-menu' : 'hover:bg-gray-100' }}">
                                <i class="fa-solid fa-user-nurse"></i><span>Caregiver</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">CONTENT & REVIEWS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('admin.content.dashboard') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.content.dashboard') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-file-alt"></i><span>Dashboard</span></a></li>
                        <li><a href="{{ route('admin.content-moderation.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.content-moderation.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-masks-theater"></i><span>Content Moderation</span></a></li>
                        <li><a href="{{ route('admin.doctor-review.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.doctor-review.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-star-half-stroke"></i><span>Doctor Reviews</span></a></li>
                        <li><a href="{{ route('admin.hospital-review.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.hospital-review.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-star-half-stroke"></i><span>Hospital Reviews</span></a></li>
                        <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">ADS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('admin.ads.dashboard') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.ads.dashboard') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
                        <li><a href="{{ route('admin.ads.ad-management.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.ads.ad-management.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-brands fa-slideshare"></i><span>Ads Management</span></a></li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">BOOKINGS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('admin.doctor-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.doctor-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-hospital-user"></i><span>Doctor Bookings</span></a></li>
                        <li><a href="{{ route('admin.procedure-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.procedure-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-person-dots-from-line"></i><span>Procedure Bookings</span></a></li>
                        <li><a href="{{ route('admin.wellness-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.wellness-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-heart-circle-check"></i><span>Wellness Bookings</span></a></li>
                        <li><a href="{{ route('admin.stemcell-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.stemcell-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-dna"></i><span>Stemcell Bookings</span></a></li>
                        <li><a href="{{ route('admin.diagnostic-test-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.diagnostic-test-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-microscope"></i><span>Diagnostic Test Bookings</span></a></li>
                        <li><a href="{{ route('admin.caregiver-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.caregiver-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-hand-holding-hand"></i><span>Caregiver Bookings</span></a></li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">USERS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('admin.member-profile.member-index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.member-profile.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-user"></i><span>Member Profile</span></a></li>
                        <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100"><i class="fas fa-user-shield"></i><span>Admins</span></a></li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">TRANSACTIONS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('admin.transactions.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.transactions.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-chart-line"></i><span>Transaction Report</span></a></li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">SETTINGS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('admin.settings.setting') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.settings.setting') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-cog"></i><span>Settings</span></a></li>
                        <li><a href="{{ route('admin.settings.reward-tiers') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.settings.reward-tiers') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-trophy"></i><span>Reward Tiers</span></a></li>
                        <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100"><i class="fas fa-user-circle"></i><span>Manage Profile</span></a></li>
                    </ul>
                </div>

            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-y-auto">

            <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 header-blue">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600"></div>
                    <div class="flex items-center space-x-3">

                        <button class="bell-btn" type="button">
                            <i class="fas fa-bell" style="font-size:13px;"></i>
                            <span class="bell-dot"></span>
                        </button>

                        <div class="relative" x-data="{ profileOpen: false }">
                            @php
                                $u = auth()->user();
                                $email = (string) ($u?->email ?? '');
                                $name = trim((string) ($u?->full_name ?? 'Super Admin')) ?: 'Super Admin';
                                $initials = strtoupper(substr($name, 0, 1) . substr($email, 0, 1));
                            @endphp

                            <button type="button" @click="profileOpen = !profileOpen" class="profile-trigger">
                                <div class="profile-avatar-sq">{{ $initials }}</div>
                                <div class="text-left leading-tight">
                                    <div class="pname">{{ $name }}</div>
                                    <div class="pemail">{{ $email }}</div>
                                </div>
                                <i class="fas fa-chevron-down pchev" :class="profileOpen ? 'rotate-180' : ''"></i>
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
                                    <a href="#" class="pp-item"><i class="fas fa-user-circle"></i> Profile</a>
                                    <a href="#" class="pp-item"><i class="fas fa-list"></i> Account</a>
                                    <div class="pp-divider"></div>
                                    <form method="POST" action="{{ route('admin.auth.logout') }}">
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

    {{-- ================================================================
         CRITICAL SCRIPT LOAD ORDER — DO NOT CHANGE WITHOUT UNDERSTANDING
         
         The problem: "handleShow is not defined" happens when:
           (a) Alpine loads twice (once from app.js, once from Livewire), OR
           (b) flux-lite.min.js loads before Alpine is initialized
         
         Correct order:
           1. Non-Alpine scripts (SweetAlert, common.js, TinyMCE)
           2. @stack('scripts') — page scripts that don't need Alpine
           3. Livewire script — this initializes Alpine
           4. flux-lite.min.js — registers handleShow into Alpine (MUST be after Livewire)
           5. Toast + other livewire:init listeners
           6. @stack('scripts-after-livewire')
         
         ALSO REQUIRED: Remove any Alpine import/start from resources/js/app.js
         Check with: grep -n "alpine\|Alpine" resources/js/app.js
         If found, delete those lines and run: npm run build
    ================================================================ --}}

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/common.js') }}"></script>

    @if (request()->routeIs('admin.content-moderation.create', 'admin.content-moderation.edit'))
        <script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
    @endif

    @stack('scripts')

    {{-- Step 3: Livewire (boots Alpine) --}}
    @livewireScripts

    {{-- Step 4: Flux lite — registers Alpine plugin including handleShow.
         Must be AFTER @livewireScripts. Do NOT use @fluxScripts if your
         Flux version doesn't have that directive (causes literal output). --}}
    <script src="{{ asset('vendor/flux/flux-lite.min.js') }}"></script>

    {{-- Step 5: Toast event listener --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', ({ type, message }) => {
                let toastClass = 'glass-toast';
                if (type === 'success') toastClass += ' toast-success';
                if (type === 'error')   toastClass += ' toast-error';
                if (type === 'warning') toastClass += ' toast-warning';
                if (type === 'info')    toastClass += ' toast-info';

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: type,
                    title: message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: { popup: toastClass }
                });
            });
        });
    </script>

    @stack('scripts-after-livewire')

</body>
</html>