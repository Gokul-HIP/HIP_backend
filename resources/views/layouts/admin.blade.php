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

</head>
<body class="bg-gray-100">

    <div class="flex h-screen w-full overflow-hidden">

        <x-sidebar menu="admin" />

        <div class="flex-1 flex flex-col overflow-y-auto">

            <x-topbar />

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
