<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HealthInPocket Healthcare Admin')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>      

    @fluxAppearance

    <link rel="stylesheet" href="{{ asset('assets/common.css') }}">
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body class="bg-gray-100">

    <div class="flex h-screen w-full overflow-hidden">
    
        <aside class="sidebar w-70 bg-white shadow-lg border-r border-gray-200" style="width:15rem">
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
                            <a href="{{ route('healthcare.admin.dashboard.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.admin.dashboard.index') || request()->routeIs('healthcare.dashboard.index') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fa-regular fa-rectangle-list"></i><span>Dashboard</span>
                            </a>
                        </li>
    
                        <li>
                            <a href="{{ route('healthcare.hospital-profile.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.hospital-profile.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                               <i class="fa-solid fa-hospital"></i><span>Hospital Onboarding</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('healthcare.hospitals.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.hospitals.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                               <i class="fa-solid fa-house-medical"></i><span>Hospitals</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('healthcare.diagnostics.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.diagnostics.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                               <i class="fa-solid fa-microscope"></i><span>Diagnostic</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('healthcare.pharmacy.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.pharmacy.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                               <i class="fa-solid fa-pills"></i><span>Pharmacy</span>
                            </a>
                        </li>

                    </ul>
                </div>
                
                {{-- BOOKINGS --}}
                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">BOOKINGS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('healthcare.diagnostic.booking') }}"
                             class="flex items-center space-x-3 p-2 rounded transition-colors 
                             {{ request()->routeIs('healthcare.diagnostic.booking*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fa-solid fa-file-circle-plus"></i><span>Diagnostic Bookings</span></a>
                        </li>

                        <li><a href="{{ route('healthcare.procedure.booking') }}"
                             class="flex items-center space-x-3 p-2 rounded transition-colors 
                             {{ request()->routeIs('healthcare.procedure.booking*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fa-solid fa-comment-medical"></i><span>Procedure Bookings</span></a>
                        </li>

                        <li><a href="{{ route('healthcare.doctor.booking') }}"
                             class="flex items-center space-x-3 p-2 rounded transition-colors 
                             {{ request()->routeIs('healthcare.doctor.booking*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fa-solid fa-hospital-user"></i><span>Doctor Bookings</span></a>
                        </li>
                    </ul>
                </div>
                
                <!-- TRANSACTIONS -->
                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">TRANSACTIONS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('healthcare.transactions.index') }}"
                             class="flex items-center space-x-3 p-2 rounded transition-colors 
                             {{ request()->routeIs('healthcare.transactions.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fas fa-chart-line"></i><span>Payment Report</span></a>
                        </li>
                    </ul>
                </div>
    
                <!-- CONTENT & REVIEWS -->
                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">CONTENT &amp; REVIEWS</div>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('healthcare.content.dashboard') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.content.dashboard') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fa-solid fa-chart-pie"></i><span>Content Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('healthcare.content.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.content.index', 'healthcare.content.create', 'healthcare.content.edit') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fa-solid fa-file-lines"></i><span>Content &amp; Review</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">ADS</div>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('healthcare.ads.dashboard') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.ads.dashboard') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('healthcare.ads.ad-management.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('healthcare.ads.ad-management.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fa-brands fa-slideshare"></i><span>Ads Management</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- USERS -->
                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">USERS</div>
                    <ul class="space-y-1">
                        <li><a href="{{ route('healthcare.members.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors
                            {{ request()->routeIs('healthcare.members.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fas fa-user"></i><span>Member Profile</span></a>
                        </li>
    
                        <li>
                            <a href="{{ route('healthcare.doctors.index') }}" 
                               class="flex items-center space-x-3 p-2 rounded transition-colors 
                               {{ request()->routeIs('healthcare.doctors.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fas fa-user-md"></i><span>Doctors</span>
                            </a>
                        </li>

                    </ul>
                </div>
    
            </nav>
        </aside>
    
        <div class="flex-1 flex flex-col overflow-y-auto">
    
            <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 header-blue">
                <div class="flex justify-between items-center">
            
                    <!-- Left side (empty or breadcrumb) -->
                    <div class="text-sm text-gray-600"></div>
            
                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
            
                        <!-- Notification -->
                        <button class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-bell"></i>
                        </button>
            
                        <!-- Messages -->
                        {{-- <button class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-paper-plane"></i>
                        </button> --}}
            
                        <!-- Profile Dropdown -->
                        <div class="relative" x-data="{ profileOpen: false }">
                            <button type="button" @click="profileOpen = !profileOpen"
                                class="inline-flex items-center space-x-2 text-gray-700 focus:outline-none">
            
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                    HC
                                </div>
            
                                <div class="text-left">
                                    <div class="text-sm font-semibold text-gray-900">Healthcare Admin</div>
                                    <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
                                </div>
            
                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                            </button>
            
                            <!-- Dropdown -->
                            <div x-show="profileOpen"
                                 x-cloak
                                 @click.away="profileOpen = false"
                                 x-transition
                                 class="absolute right-0 z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-44">
            
                                <ul class="p-2 text-sm text-gray-700 font-medium">
                                    <li>
                                        <a href="#"
                                           class="flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                            <i class="fas fa-user-circle mr-2"></i>
                                            Profile
                                        </a>
                                    </li>
            
                                    <li>
                                        <a href="#"
                                           class="flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                            <i class="fas fa-list mr-2"></i>
                                            Account
                                        </a>
                                    </li>
            
                                    <li>
                                        <form method="POST" action="{{ route('admin.auth.logout') }}">
                                            @csrf
                                            <button type="submit"
                                                class="flex items-center w-full p-2 text-red-500 hover:bg-red-50 rounded">
                                                <i class="fa-solid fa-right-from-bracket mr-2"></i>
                                                Logout
                                            </button>
                                        </form>
                                    </li>
                                </ul>
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

    @if (request()->routeIs('healthcare.content.create', 'healthcare.content.edit'))
        <script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
    @endif

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
@fluxScripts
</body>
</html>

