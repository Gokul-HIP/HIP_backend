<aside class="sidebar doctor-sidebar flex flex-col h-screen min-h-0 overflow-hidden" style="width: 15rem;">
    <div class="p-4 border-b logo-blue flex-shrink-0">
        <div class="flex items-center justify-center">
            <a href="{{ route('doctor.dashboard.index') }}">
                <img src="{{ asset('assets/healthin-black.png') }}"
                     alt="HealthIn Pocket"
                     class="h-9 w-auto object-contain">
            </a>
        </div>
    </div>

    <nav class="p-4 pt-5 pb-8 space-y-6 flex-1 overflow-y-auto min-h-0 sidebar-nav">
        @include('components.menus.doctor')
    </nav>
</aside>
