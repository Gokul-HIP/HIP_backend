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

    <link rel="stylesheet" href="{{ asset('assets/common.css') }}">
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                            <a href="{{ route('doctor.dashboard.index') }}"
                               class="flex items-center space-x-3 p-2 rounded transition-colors
                               {{ request()->routeIs('doctor.dashboard.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                                <i class="fa-regular fa-rectangle-list"></i><span>Dashboard</span>
                            </a>
                        </li>
    
                        <li><a href="{{ route('doctor.member-profile.member-index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors
                            {{ request()->routeIs('doctor.member-profile.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fas fa-user"></i><span>Patient</span></a></li>
                        <li>

                        <li><a href="{{ route('doctor.referral.send') }}" class="flex items-center space-x-3 p-2 rounded transition-colors
                            {{ request()->routeIs('doctor.referral.send*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fa-solid fa-paper-plane"></i><span>Send Referral</span></a></li>
                        <li>

                        <li><a href="{{ route('doctor.referral.receive') }}" class="flex items-center space-x-3 p-2 rounded transition-colors
                            {{ request()->routeIs('doctor.referral.receive*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                            <i class="fa-solid fa-right-left"></i><span>Receive Referral</span></a></li>
                        <li>

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
                                    HA
                                </div>
            
                                <div class="text-left">
                                    <div class="text-sm font-semibold text-gray-900">Doctor Admin</div>
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
                                        <form method="POST" action="{{ route('doctor.auth.logout') }}">
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
