<div class="space-y-6"
     x-data
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <div>
        <h2 class="text-2xl font-semibold mb-4 text-gray-900">Second Opinion Overview</h2>

        <div class="flex gap-4 overflow-x-auto pb-2">

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Second Opinions</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $secondOpinions->total() }}</p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Pending</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ \App\Models\SecondOpinion::where('status', 'pending')->count() }}
                </p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Cancellation</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ \App\Models\SecondOpinion::where('status', 'cancelled')->count() }}
                </p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Completed</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ \App\Models\SecondOpinion::where('status', 'completed')->count() }}
                </p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[260px]">
                <p class="text-xs text-gray-500">
                    Upcoming Second Opinions
                    <span class="text-xs">(Next 7 days)</span>
                </p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ \App\Models\SecondOpinion::whereBetween('preferred_date', [now()->toDateString(), now()->addDays(7)->toDateString()])->count() }}
                </p>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border">

        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3 flex-wrap gap-3">

                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input
                        type="text"
                        placeholder="Search patient, member, hospital, doctor..."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>

                <div class="relative">
                    <button onclick="toggleFilter('soStatusFilter')" class="filter-btn">
                        <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $status === 'all' ? 'All Status' : ucfirst($status) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="soStatusFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            @foreach(['all' => 'All Status', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'soStatusFilter')"
                                    wire:click="$set('status','{{ $value }}')">
                                    {{ $label }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="relative">
                    <button onclick="toggleFilter('soDoctorFilter')" class="filter-btn">
                        <i class="fas fa-user-doctor mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($doctorFilter === 'all')
                                All Doctors
                            @else
                                {{ $availableDoctors->firstWhere('id', $doctorFilter)?->name ?? 'All Doctors' }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="soDoctorFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'soDoctorFilter')"
                                    wire:click="$set('doctorFilter','all')">
                                    All Doctors
                                </button>
                            </li>
                            @foreach($availableDoctors as $doctor)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'soDoctorFilter')"
                                    wire:click="$set('doctorFilter','{{ $doctor->id }}')">
                                    {{ $doctor->name }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="relative">
                    <button onclick="toggleFilter('soHospitalFilter')" class="filter-btn">
                        <i class="fas fa-hospital mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($hospitalFilter === 'all')
                                All Hospitals
                            @else
                                {{ $availableHospitals->firstWhere('id', $hospitalFilter)?->name ?? 'All Hospitals' }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="soHospitalFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'soHospitalFilter')"
                                    wire:click="$set('hospitalFilter','all')">
                                    All Hospitals
                                </button>
                            </li>
                            @foreach($availableHospitals as $hospital)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'soHospitalFilter')"
                                    wire:click="$set('hospitalFilter',{{ $hospital->id }})">
                                    {{ $hospital->name }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="relative">
                    <div class="flex items-center border border-gray-300 rounded-lg bg-white px-4 py-2">
                        <i class="fas fa-calendar-alt mr-2 text-gray-700"></i>
                        <input
                            type="date"
                            class="border-none outline-none bg-transparent text-sm text-gray-700 cursor-pointer flex-1"
                            wire:model.live="dateFilter"
                            placeholder="Select Date"
                        />
                        @if($dateFilter)
                        <button
                            type="button"
                            wire:click="clearDateFilter"
                            class="ml-2 text-gray-400 hover:text-gray-600 cursor-pointer"
                            title="Clear date filter">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                        @endif
                    </div>
                </div>

            </div>

        </div>

        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Apt ID</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Patient</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Member</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Hospital</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Doctor</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Preferred Slot</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Payment</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($secondOpinions as $secondOpinion)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            SO-{{ str_pad($secondOpinion->id, 4, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $secondOpinion->patient_name ?? 'N/A' }}
                            @if($secondOpinion->relationship)
                                <br><span class="text-xs text-gray-500">{{ ucfirst($secondOpinion->relationship) }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $secondOpinion->member?->name ?? 'N/A' }}
                            <br>
                            <span class="text-xs text-gray-500">
                                {{ $secondOpinion->member?->hip_id ?? 'N/A' }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $secondOpinion->branch?->name ?? 'N/A' }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $secondOpinion->doctor?->name ?? 'N/A' }}
                            <br>
                            <span class="text-xs text-gray-500">
                                {{ $secondOpinion->speciality?->name ?? '-' }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-800">
                                {{ optional($secondOpinion->preferred_date)->format('M d, Y') ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                @php
                                    $slots = $secondOpinion->preferred_time_slots ?? [];
                                    if (count($slots) >= 2) {
                                        $timeText = $slots[0] . ' - ' . end($slots);
                                    } elseif (count($slots) === 1) {
                                        $timeText = $slots[0];
                                    } else {
                                        $timeText = '-';
                                    }
                                @endphp
                                {{ $timeText }}
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            @php
                                $paymentLabel = $secondOpinion->payment_mode_label;
                                $paymentClass = match ($paymentLabel) {
                                    'Paid by online' => 'bg-blue-100 text-blue-700',
                                    'Pay by online' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $paymentClass }}">
                                {{ $paymentLabel }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $secondOpinion->status === 'confirmed'
                                    ? 'bg-green-100 text-green-700'
                                    : ($secondOpinion->status === 'cancelled'
                                        ? 'bg-red-100 text-red-700'
                                        : ($secondOpinion->status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-700'
                                            : ($secondOpinion->status === 'completed'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-gray-100 text-gray-700'))) }}">
                                {{ ucfirst($secondOpinion->status) }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'so-menu-{{ $secondOpinion->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="so-menu-{{ $secondOpinion->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <a href="{{ route('admin.second-opinion.appointment-details', $secondOpinion->id) }}"
                                                 onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                               wire:click="openDeleteBookingModal({{ $secondOpinion->id }})"
                                                class="inline-flex items-center w-full p-2 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                type="button"
                                                wire:click="openUpdateStatusModal({{ $secondOpinion->id }})"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fas fa-toggle-on mr-2 text-gray-700"></i> Update Status
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900">No second opinion bookings found</p>
                            <p class="text-sm text-gray-600">No bookings match your search criteria</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $secondOpinions->links() }}
        </div>
    </div>

    <flux:modal name="delete-second-opinion" class="p-0" wire:close="closeDeleteBookingModal" id="delete-org">
        <div x-data @click.outside="$wire.closeDeleteBookingModal()">
            <div>
                <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeDeleteBookingModal" />

                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Second Opinion
                </h2>

                <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                    Are you sure you want to delete this second opinion booking?
                </p>

                <div class="flex flex-col items-end gap-3">
                    <div class="flex justify-end gap-3 w-full">
                        <button type="button" wire:click="closeDeleteBookingModal"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                            Cancel
                        </button>
                        <button type="button" wire:click="deleteBooking" wire:loading.attr="disabled"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                            <span wire:loading.remove wire:target="deleteBooking">Delete Booking</span>
                            <span wire:loading wire:target="deleteBooking">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
