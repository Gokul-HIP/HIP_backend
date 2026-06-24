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
