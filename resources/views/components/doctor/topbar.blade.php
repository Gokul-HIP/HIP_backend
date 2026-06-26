@php
    use App\Support\CurrentDoctor;

    $user = auth('filament')->user() ?? auth()->user();
    $doctor = CurrentDoctor::resolve();

    $doctorName = trim((string) ($doctor?->name ?? $user?->name ?? $user?->full_name ?? 'Doctor'));
    if ($doctorName !== '' && ! str_starts_with(strtolower($doctorName), 'dr')) {
        $doctorName = 'Dr. ' . $doctorName;
    }

    $specialty = trim((string) ($doctor?->speciality_names ?? ''));
    $roleLabel = $specialty !== '' && $specialty !== '-'
        ? $specialty
        : 'Medical Professional';

    $avatarUrl = ! empty($doctor?->doctor_image)
        ? asset('storage/doctor/' . ltrim((string) $doctor->doctor_image, '/'))
        : null;

    $initials = strtoupper(
        substr(preg_replace('/^Dr\.?\s*/i', '', $doctorName), 0, 1)
        . substr((string) ($user?->email ?? 'D'), 0, 1)
    );
@endphp

<header class="topbar header-blue">
    <div class="doctor-topbar-inner">
        <div class="topbar-search">
            <i class="fas fa-search"></i>
            <input type="search" placeholder="Search patients, records..." aria-label="Search patients and records">
        </div>

        <div class="flex items-center space-x-3">
            @if(config('services.doctor_notifications.enabled', true))
                <x-doctor.notification-bell />
            @endif

            <button type="button" class="bell-btn" aria-label="Settings">
                <i class="fas fa-cog" style="font-size:13px;"></i>
            </button>

            <span class="doctor-topbar-divider" aria-hidden="true"></span>

            <div class="relative" x-data="{ profileOpen: false }">
                <button type="button"
                        class="profile-trigger"
                        @click="profileOpen = !profileOpen"
                        aria-label="Open profile menu">
                    <div>
                        <div class="pname">{{ $doctorName }}</div>
                        {{-- <div class="pemail">{{ $roleLabel }}</div> --}}
                    </div>

                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $doctorName }}" class="profile-avatar-sq profile-avatar-photo">
                    @else
                        <div class="profile-avatar-sq">{{ $initials }}</div>
                    @endif

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
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $doctorName }}" class="pp-head-avatar profile-avatar-photo">
                        @else
                            <div class="pp-head-avatar">{{ $initials }}</div>
                        @endif
                        <div>
                            <div class="pp-head-name">{{ $doctorName }}</div>
                            {{-- <div class="pp-head-email">{{ $roleLabel }}</div> --}}
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
                        <a href="{{ route('doctor.auth.logout') }}" class="pp-item logout">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
