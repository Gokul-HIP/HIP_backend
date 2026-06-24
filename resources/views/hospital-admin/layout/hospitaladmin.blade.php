<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HealthInPocket Healthcare Admin')</title>

    <link rel="stylesheet" href="{{ asset('vendor/flux/flux.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @fluxAppearance

    <link rel="stylesheet" href="{{ asset('assets/common.css') }}">
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireStyles

</head>
<body class="bg-gray-100">

    <div class="flex h-screen w-full overflow-hidden">

        <x-sidebar menu="healthcare" />

        <div class="flex-1 flex flex-col overflow-y-auto">

            <x-topbar />

            <main class="flex-1 bg-gray-100 p-6 overflow-y-auto">
                @yield('content')
            </main>

        </div>
    </div>

    <script src="{{ asset('assets/common.js') }}"></script>

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
@livewireScripts
<script src="{{ asset('vendor/flux/flux-lite.min.js') }}"></script>
@stack('scripts-after-livewire')
</body>
</html>
