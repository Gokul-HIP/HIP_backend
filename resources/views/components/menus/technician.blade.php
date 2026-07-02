@include('components.menus.staff', ['prefix' => 'technician'])

<div class="mt-6">
    <div class="text-xs font-semibold text-gray-500 mb-2">DIAGNOSTICS</div>
    <ul class="space-y-1">
        <li>
            <a href="{{ route('technician.diagnostic-bookings.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('technician.diagnostic-bookings.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-vial"></i><span>Diagnostic Bookings</span>
            </a>
        </li>
        <li>
            <a href="{{ route('technician.upload-report.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('technician.upload-report.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-file-arrow-up"></i><span>Upload Report</span>
            </a>
        </li>
        <li>
            <a href="{{ route('technician.patients.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('technician.patients.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-user-injured"></i><span>Patients</span>
            </a>
        </li>
    </ul>
</div>
