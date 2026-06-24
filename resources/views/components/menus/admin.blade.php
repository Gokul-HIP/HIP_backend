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
        {{-- <li>
            <a href="{{ route('admin.hospital-onboarding.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.hospital-onboarding.*') ? 'active-menu' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-hospital"></i><span>Hospital Onboarding</span>
            </a>
        </li> --}}
        {{-- <li>
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
        </li> --}}
    </ul>
</div>

<div>
    <div class="text-xs font-semibold text-gray-500 mb-2">CONTENT & REVIEWS</div>
    <ul class="space-y-1">
        <li><a href="{{ route('admin.content.dashboard') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.content.dashboard') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-file-alt"></i><span>Dashboard</span></a></li>
        <li><a href="{{ route('admin.how-to-earn.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.how-to-earn.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-coins"></i><span>How to Earn Content</span></a></li>
        <li><a href="{{ route('admin.content-moderation.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.content-moderation.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-masks-theater"></i><span>Content Moderation</span></a></li>
        <li><a href="{{ route('admin.doctor-review.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.doctor-review.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-star-half-stroke"></i><span>Doctor Reviews</span></a></li>
        <li><a href="{{ route('admin.hospital-review.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.hospital-review.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-star-half-stroke"></i><span>Hospital Reviews</span></a></li>
        {{-- <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li> --}}
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
        <li><a href="{{ route('admin.second-opinion.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.second-opinion.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-stethoscope"></i><span>Second Opinion</span></a></li>
        {{-- <li><a href="{{ route('admin.procedure-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.procedure-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-person-dots-from-line"></i><span>Procedure Bookings</span></a></li> --}}
        {{-- <li><a href="{{ route('admin.wellness-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.wellness-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-heart-circle-check"></i><span>Wellness Bookings</span></a></li> --}}
        {{-- <li><a href="{{ route('admin.stemcell-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.stemcell-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-dna"></i><span>Stemcell Bookings</span></a></li> --}}
        <li><a href="{{ route('admin.diagnostic-test-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.diagnostic-test-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-microscope"></i><span>Diagnostic Test Bookings</span></a></li>
        {{-- <li><a href="{{ route('admin.caregiver-booking.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.caregiver-booking.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fa-solid fa-hand-holding-hand"></i><span>Caregiver Bookings</span></a></li> --}}
    </ul>
</div>

<div>
    <div class="text-xs font-semibold text-gray-500 mb-2">USERS</div>
    <ul class="space-y-1">
        <li><a href="{{ route('admin.member-profile.member-index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.member-profile.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-user"></i><span>Member Profile</span></a></li>
        {{-- <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100"><i class="fas fa-user-shield"></i><span>Admins</span></a></li> --}}
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
        <li><a href="{{ route('admin.membership-packages.index') }}" class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('admin.membership-packages.*') ? 'active-menu' : 'hover:bg-gray-100' }}"><i class="fas fa-id-card"></i><span>Membership Packages</span></a></li>
        {{-- <li><a href="#" class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100"><i class="fas fa-user-circle"></i><span>Manage Profile</span></a></li> --}}
    </ul>
</div>
