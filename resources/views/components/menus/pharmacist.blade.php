@include('components.menus.staff', ['prefix' => 'pharmacist'])

<div class="mt-6">
    <div class="text-xs font-semibold text-gray-500 mb-2">PATIENTS</div>
    <ul class="space-y-1">
        <li>
            <a href="{{ route('pharmacist.prescription-orders.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors
               {{ request()->routeIs('pharmacist.prescription-orders.*') ? 'active-menu bg-blue-50 text-blue-700' : 'hover:bg-gray-100' }}">
                <i class="fa-solid fa-prescription-bottle-medical"></i><span>Prescription Orders</span>
            </a>
        </li>
    </ul>
</div>
