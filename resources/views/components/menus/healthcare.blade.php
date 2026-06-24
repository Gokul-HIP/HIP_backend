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

        {{-- <li>
            <a href="{{ route('healthcare.hospital-profile.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('healthcare.hospital-profile.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
               <i class="fa-solid fa-hospital"></i><span>Hospital Onboarding</span>
            </a>
        </li> --}}

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

<div>
    <div class="text-xs font-semibold text-gray-500 mb-2">BOOKINGS</div>
    <ul class="space-y-1">
        <li><a href="{{ route('healthcare.diagnostic.booking') }}"
             class="flex items-center space-x-3 p-2 rounded transition-colors
             {{ request()->routeIs('healthcare.diagnostic.booking*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
            <i class="fa-solid fa-file-circle-plus"></i><span>Diagnostic Bookings</span></a>
        </li>

        {{-- <li><a href="{{ route('healthcare.procedure.booking') }}"
             class="flex items-center space-x-3 p-2 rounded transition-colors
             {{ request()->routeIs('healthcare.procedure.booking*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
            <i class="fa-solid fa-comment-medical"></i><span>Procedure Bookings</span></a>
        </li> --}}

        <li><a href="{{ route('healthcare.doctor.booking') }}"
             class="flex items-center space-x-3 p-2 rounded transition-colors
             {{ request()->routeIs('healthcare.doctor.booking*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
            <i class="fa-solid fa-hospital-user"></i><span>Doctor Bookings</span></a>
        </li>

        <li><a href="{{ route('healthcare.second-opinion.booking') }}"
             class="flex items-center space-x-3 p-2 rounded transition-colors
             {{ request()->routeIs('healthcare.second-opinion.booking*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
            <i class="fa-solid fa-comments"></i><span>Second Opinion</span></a>
        </li>
    </ul>
</div>

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
        <li>
            <a href="{{ route('healthcare.how-to-earn.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('healthcare.how-to-earn.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-coins"></i><span>How to Earn Content</span>
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

<div>
    <div class="text-xs font-semibold text-gray-500 mb-2">SETTINGS</div>
    <ul class="space-y-1">
        <li>
            <a href="{{ route('healthcare.settings.reward-tiers') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('healthcare.settings.reward-tiers') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fas fa-trophy"></i><span>Reward Tiers</span>
            </a>
        </li>
        <li>
            <a href="{{ route('healthcare.settings.membership-packages.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('healthcare.settings.membership-packages.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fas fa-id-card"></i><span>Membership Packages</span>
            </a>
        </li>
    </ul>
</div>
