<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Doctor Portal')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @fluxAppearance

    <link rel="stylesheet" href="{{ asset('vendor/flux/flux.css') }}">
    @livewireStyles

    <link rel="stylesheet" href="{{ versioned_asset('assets/common.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('assets/doctor-admin.css') }}">
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen w-full overflow-hidden">

        <x-doctor.sidebar />

        <div class="flex-1 flex flex-col min-h-0 overflow-hidden">

            <x-doctor.topbar />

            <main class="doctor-main">
                @yield('content')
            </main>

        </div>
    </div>

    <script src="{{ versioned_asset('assets/common.js') }}"></script>

    @auth('filament')
        @if(session('doctor_id') && config('services.doctor_notifications.enabled', true))
            <script>
                window.DoctorNotificationsConfig = {
                    doctorId: @json(session('doctor_id')),
                    appName: @json(config('app.name', 'HealthInPocket')),
                    iconUrl: @json(asset('assets/favicon.png')),
                    soundUrl: @json(asset('sounds/notification.mp3')),
                    indexUrl: @json(route('doctor.notifications.index')),
                    unreadCountUrl: @json(route('doctor.notifications.unread-count')),
                    markReadUrl: @json(url('/doctor/notifications')),
                    reverb: {
                        key: @json(config('broadcasting.connections.reverb.key')),
                        host: @json(config('broadcasting.connections.reverb.options.host') ?: request()->getHost()),
                        port: @json((int) config('broadcasting.connections.reverb.options.port', 443)),
                        scheme: @json(config('broadcasting.connections.reverb.options.scheme', 'https')),
                    },
                };
            </script>
        @endif
    @endauth

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
