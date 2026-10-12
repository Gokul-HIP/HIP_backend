<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HealthInPocket Admin')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @fluxAppearance

    <link rel="stylesheet" href="{{ asset('vendor/flux/flux.css') }}">

    @livewireStyles

    <link rel="stylesheet" href="{{ versioned_asset('assets/common.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('assets/pharmacist-admin.css') }}">
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body class="bg-gray-100">

    <div class="flex h-screen w-full overflow-hidden">

        <x-sidebar menu="pharmacist" />

        <div class="flex-1 flex flex-col overflow-y-auto">

            <x-topbar />

            <main class="flex-1 bg-gray-100 p-6 overflow-y-auto">
                @yield('content')
            </main>

        </div>
    </div>

    <script src="{{ versioned_asset('assets/common.js') }}"></script>

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
@auth('filament')
    @if(optional(auth('filament')->user())->hasRole('pharmacist'))
        <script>
            window.PharmacistOrdersConfig = {
                reverb: {
                    key: @json(config('broadcasting.connections.reverb.key')),
                    host: @json(config('broadcasting.connections.reverb.options.host')),
                    {{-- host: @json(config('broadcasting.connections.reverb.options.host') ?: request()->getHost()), --}}
                    port: @json((int) config('broadcasting.connections.reverb.options.port', 443)),
                    scheme: @json(config('broadcasting.connections.reverb.options.scheme', 'https')),
                },
            };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.5.0/dist/web/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.3.7/dist/echo.iife.js"></script>
        <script>
            /*
            (function () {
                var server = (window.PharmacistOrdersConfig && window.PharmacistOrdersConfig.reverb) || {};
                var key = server.key || '';
                var host = server.host || window.location.hostname;
                var port = Number(server.port || 8080);
                var scheme = server.scheme || (window.location.protocol === 'https:' ? 'https' : 'http');
                var forceTLS = scheme === 'https';

                if (!key || typeof Echo === 'undefined') {
                    console.warn('[PharmacistOrders] Reverb app key is not configured.');
                    return;
                }

                window.Pusher = window.Pusher || Pusher;
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: key,
                    wsHost: host,
                    wsPort: port,
                    wssPort: port,
                    forceTLS: forceTLS,
                    enabledTransports: forceTLS ? ['wss'] : ['ws', 'wss'],
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                });
            })();
            */
            (function () {
                var echoModule = (typeof Echo !== 'undefined') ? Echo : null;
                var EchoCtor = (echoModule && typeof echoModule.default === 'function')
                    ? echoModule.default
                    : (typeof echoModule === 'function' ? echoModule : null);

                window.Echo = undefined;

                function startPharmacistEcho() {
                    try {
                        var server = (window.PharmacistOrdersConfig && window.PharmacistOrdersConfig.reverb) || {};
                        var key = server.key || '';
                        var host = server.host || '';

                        if (!key || !host || typeof EchoCtor !== 'function') {
                            console.warn('[PharmacistOrders] Reverb app key is not configured.');
                            return;
                        }

                        var port = Number(server.port || 8080);
                        var scheme = server.scheme || (window.location.protocol === 'https:' ? 'https' : 'http');
                        var forceTLS = scheme === 'https';

                        if (typeof Pusher !== 'undefined') {
                            window.Pusher = window.Pusher || Pusher;
                        }

                        window.Echo = new EchoCtor({
                            broadcaster: 'reverb',
                            key: key,
                            wsHost: host,
                            wsPort: port,
                            wssPort: port,
                            forceTLS: forceTLS,
                            enabledTransports: forceTLS ? ['wss'] : ['ws', 'wss'],
                            authEndpoint: '/broadcasting/auth',
                            auth: {
                                headers: {
                                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            },
                        });
                    } catch (error) {
                        window.Echo = undefined;
                        console.warn('[PharmacistOrders] Echo did not start.', error);
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', startPharmacistEcho);
                } else {
                    startPharmacistEcho();
                }
            })();
        </script>
    @endif
@endauth
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
