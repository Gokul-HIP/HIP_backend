@include('components.menus.staff', ['prefix' => 'receptionist'])

<div class="mt-6">
    <div class="text-xs font-semibold text-gray-500 mb-2">BOOKINGS</div>
    <ul class="space-y-1">
        <li>
            <a href="{{ route('receptionist.doctor-bookings.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('receptionist.doctor-bookings.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-user-doctor"></i><span>Doctor Bookings</span>
            </a>
        </li>
        <li>
            <a href="{{ route('receptionist.second-opinion-bookings.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('receptionist.second-opinion-bookings.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-stethoscope"></i><span>Second Opinion Bookings</span>
            </a>
        </li>
        <li>
            <a href="{{ route('receptionist.diagnostic-bookings.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('receptionist.diagnostic-bookings.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-vial"></i><span>Diagnostic Test Bookings</span>
            </a>
        </li>
    </ul>
</div>

<div class="mt-6">
    <div class="text-xs font-semibold text-gray-500 mb-2">PATIENTS</div>
    <ul class="space-y-1">
        <li>
            <a href="{{ route('receptionist.patients.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('receptionist.patients.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-user-injured"></i><span>Patients</span>
            </a>
        </li>
    </ul>
</div>
