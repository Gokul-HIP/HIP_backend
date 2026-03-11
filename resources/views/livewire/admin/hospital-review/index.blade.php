<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-lg font-semibold mb-4 text-gray-900">Manage Iospital Reviews</h2>
        <h4 class="text-sm text-gray-500 mb-4">Monitor and manage hospital reviews from your patients.</h4>
    </div>

   <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md p-6 border">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3">

                <!-- SEARCI -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input
                        type="text"
                        placeholder="Search member name, hospital name, review..."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>

                <!-- STATUS DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('statusFilter')" class="filter-btn">
                        <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $status === 'all' ? 'All Status' : ucfirst($status) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="statusFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Status
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','active')">
                                    <i class="fas fa-check-circle mr-2 text-gray-700"></i> Active
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-red-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','inactive')">
                                    <i class="fas fa-times-circle mr-2 text-gray-700"></i> Inactive
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- DATE FILTER -->
                <div class="relative">
                    <div class="flex items-center border border-gray-300 rounded-lg bg-white px-4 py-2">
                        <i class="fas fa-calendar-alt mr-2 text-gray-700"></i>
                        <input
                            type="date"
                            class="border-none outline-none bg-transparent text-sm text-gray-700 cursor-pointer flex-1"
                            wire:model.live="dateFilter"
                            placeholder="Select Date"
                            max="{{ date('Y-m-d') }}"
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

                <!-- RATING FILTER -->
                <div class="relative">
                    <button onclick="toggleFilter('ratingFilter')" class="filter-btn">
                        <i class="fas fa-star mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $ratingFilter === 'all' ? 'All Ratings' : $ratingFilter . ' Stars' }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="ratingFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'ratingFilter')"
                                    wire:click="$set('ratingFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Ratings
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-yellow-50 rounded"
                                    onclick="selectFilter(this,'ratingFilter')"
                                    wire:click="$set('ratingFilter','5')">
                                    <i class="fas fa-star mr-2 text-yellow-500"></i> 5 Stars
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-yellow-50 rounded"
                                    onclick="selectFilter(this,'ratingFilter')"
                                    wire:click="$set('ratingFilter','4')">
                                    <i class="fas fa-star mr-2 text-yellow-500"></i> 4 Stars
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-yellow-50 rounded"
                                    onclick="selectFilter(this,'ratingFilter')"
                                    wire:click="$set('ratingFilter','3')">
                                    <i class="fas fa-star mr-2 text-yellow-500"></i> 3 Stars
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-yellow-50 rounded"
                                    onclick="selectFilter(this,'ratingFilter')"
                                    wire:click="$set('ratingFilter','2')">
                                    <i class="fas fa-star mr-2 text-yellow-500"></i> 2 Stars
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-yellow-50 rounded"
                                    onclick="selectFilter(this,'ratingFilter')"
                                    wire:click="$set('ratingFilter','1')">
                                    <i class="fas fa-star mr-2 text-yellow-500"></i> 1 Star
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

        <!-- TABLE -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse table-fixed shadow-md rounded-lg">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Member Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Iospital Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Review</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Rating (out of 5)</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse ($reviews as $review)
                        <tr class="hover:bg-gray-50">
                            <!-- Member Name -->
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ $review->member ? $review->member->name : '-' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $review->member->hip_id ?? 'N/A' }}
                                </div>
                                {{-- @if($review->created_at)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $review->created_at->format('M d, Y') }}
                                    </div>
                                @endif --}}
                            </td>

                            <!-- Doctor Name -->
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ $review->hospital ? $review->hospital->name : '-' }}
                                </div>
                            </td>

                            <!-- Review -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="max-w-md">
                                    {{ $review->review ? Str::limit($review->review, 100) : '-' }}
                                </div>
                            </td>

                            <!-- Rating -->
                            <td class="px-6 py-4 text-sm">
                                @if($review->rating)
                                    <div class="flex items-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                        @endfor
                                        <span class="ml-2 text-gray-600 font-medium">({{ $review->rating }})</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-700',
                                        'inactive' => 'bg-red-100 text-red-700',
                                    ];
                                    $statusColor = $statusColors[$review->status] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($review->status) }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="action-menu-wrapper">
                                    <button
                                        class="action-btn"
                                        onclick="toggleActionMenu(event,'menu-{{ $review->id }}')">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                    <div id="menu-{{ $review->id }}" class="action-menu hidden">
                                        <ul class="p-2 text-sm text-gray-700 font-medium">
                                            <li>
                                                <a
                                                    href="{{ route('admin.hospital-review.view', $review->id) }}"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                                </a>
                                            </li>
                                            @if($review->status === 'active')
                                                <li>
                                                    <button
                                                        wire:click="toggleStatus({{ $review->id }})"
                                                        onclick="closeAllActionMenus()"
                                                        class="inline-flex items-center w-full p-2 hover:bg-orange-50 text-orange-600 rounded">
                                                        <i class="fa-solid fa-toggle-off w-4 mr-2"></i> Set Inactive
                                                    </button>
                                                </li>
                                            @elseif($review->status === 'inactive')
                                                <li>
                                                    <button
                                                        wire:click="toggleStatus({{ $review->id }})"
                                                        onclick="closeAllActionMenus()"
                                                        class="inline-flex items-center w-full p-2 hover:bg-green-50 text-green-600 rounded">
                                                        <i class="fa-solid fa-toggle-on w-4 mr-2"></i> Set Active
                                                    </button>
                                                </li>
                                            @endif
                                            <li>
                                                <button
                                                    wire:click="openDeleteReviewModal({{ $review->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 text-red-600 hover:bg-red-50 rounded">
                                                    <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                                <p class="text-lg font-medium text-gray-900">No reviews found</p>
                                <p class="text-sm text-gray-600">No hospital reviews match your filters</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $reviews->links() }}
        </div>
    </div>

    <style>
        /* Force light mode on modal - override dark mode */
        [data-flux-modal="delete-review"] dialog,
        [data-flux-modal="delete-review"] dialog * {
            color-scheme: light !important;
            background-color: #ffffff !important;
            color: #111827 !important;
            border-color: #d1d5db !important;
        }
        
        [data-flux-modal="delete-review"] dialog {
            background-color: #ffffff !important;
            border-color: #d1d5db !important;
        }
        
        /* Force light borders on all elements */
        [data-flux-modal="delete-review"] dialog input,
        [data-flux-modal="delete-review"] dialog textarea,
        [data-flux-modal="delete-review"] dialog select,
        [data-flux-modal="delete-review"] dialog button,
        [data-flux-modal="delete-review"] dialog div,
        [data-flux-modal="delete-review"] dialog .border,
        [data-flux-modal="delete-review"] dialog [class*="border"] {
            border-color: #d1d5db !important;
        }
    </style>

    <flux:modal name="delete-review" class="p-0" wire:close="closeDeleteReviewModal" id="delete-org">
        <div x-data @click.outside="$wire.closeDeleteReviewModal()">
            <div>

            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeDeleteReviewModal" />

            <!-- Title -->
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Delete Iospital Review?
            </h2>

            <!-- Description -->
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                You're about to delete this hospital review.<br>
                This action cannot be reversed.
            </p>

            <!-- Buttons -->
            <div class="flex justify-end gap-4">
                <flux:button variant="ghost"
                    wire:click="closeDeleteReviewModal"
                    class="text-sm font-medium text-black hover:text-gray-900">
                    <i class="fa-solid fa-times mr-2 text-black"></i>
                    <span class="hidden sm:inline text-black">Cancel</span>
                    <span class="sm:hidden text-black">Cancel</span>
                </flux:button>

                <button
                    type="button"
                    wire:click="deleteReview"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                    <i class="fa-solid fa-trash-can w-4 mr-2"></i>
                    Delete Iospital Review
                </button>
            </div>

            </div>
        </div>
    </flux:modal>

</div>

@push('scripts')
<script>
function toggleActionMenu(event, id) {
    event.stopPropagation();

    document.querySelectorAll(".action-menu").forEach(m => {
        if (m.id !== id) m.classList.add("hidden");
    });

    const menu = document.getElementById(id);
    const btn  = event.target.closest(".action-btn");
    const rect = btn.getBoundingClientRect();

    menu.classList.toggle("hidden");
    if (menu.classList.contains("hidden")) return;

    // Calculate menu height
    menu.style.visibility = "hidden";
    menu.style.display = "block";
    const menuIeight = menu.offsetIeight;
    menu.style.display = "";
    menu.style.visibility = "";

    // Check available space
    const spaceBelow = window.innerIeight - rect.bottom;
    const spaceAbove = rect.top;

    let top;

    // If menu goes off-screen bottom → flip up
    if (spaceBelow < menuIeight) {
        top = rect.top - menuIeight - 10;
    } else {
        top = rect.bottom + 10;
    }

    // Position menu
    menu.style.position = "fixed";
    menu.style.top  = top + "px";
    menu.style.left = (rect.left - menu.offsetWidth + rect.width) + "px";
    menu.style.zIndex = "1000";
}

function closeAllActionMenus() {
    document.querySelectorAll(".action-menu").forEach(m => {
        m.classList.add("hidden");
    });
}

function toggleFilter(id) {
    event.stopPropagation();
    const filter = document.getElementById(id);
    document.querySelectorAll(".filter-dropdown").forEach(f => {
        if (f.id !== id) f.classList.add("hidden");
    });
    filter.classList.toggle("hidden");
}

function selectFilter(element, filterId) {
    const filter = document.getElementById(filterId);
    filter.classList.add("hidden");
}

// Close filters when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.filter-btn') && !event.target.closest('.filter-dropdown')) {
        document.querySelectorAll(".filter-dropdown").forEach(f => {
            f.classList.add("hidden");
        });
    }
});
</script>
@endpush

