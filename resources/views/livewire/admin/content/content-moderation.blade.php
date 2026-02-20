<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="{{ asset('assets/org.jpg') }}"
                class="w-full h-full object-cover rounded-xl object-center"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        Content Moderation & Posting
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage Content Moderation & Posting
                    </p>
                </div>

                <!-- ACTION BUTTON -->
                <div class="flex space-x-1">
                    <button class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-3 py-2 shadow-lg">
                        <i class="fas fa-edit text-white"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-lg font-semibold mb-4 text-gray-900">Content Moderation & Posting</h2>

        {{-- <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Total Content</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $content->count() }}</p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Active Content</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $content->where('status', 'active')->count() }}</p>
            </div>
        </div> --}}
    </div>

   <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md p-6 border">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3">

                <!-- SEARCH -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input
                        type="text"
                        placeholder="Search title, description, organization, hospital, doctor..."
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
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','draft')">
                                    <i class="fas fa-file-lines mr-2 text-gray-700"></i> Draft
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-yellow-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','scheduled')">
                                    <i class="fas fa-clock mr-2 text-gray-700"></i> Scheduled
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- CATEGORY DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('categoryFilter')" class="filter-btn">
                        <i class="fas fa-tags mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $category === 'all' ? 'All Categories' : ucfirst($category) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="categoryFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'categoryFilter')"
                                    wire:click="$set('category','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Categories
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-blue-50 rounded"
                                    onclick="selectFilter(this,'categoryFilter')"
                                    wire:click="$set('category','new')">
                                    <i class="fas fa-star mr-2 text-gray-700"></i> New
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-purple-50 rounded"
                                    onclick="selectFilter(this,'categoryFilter')"
                                    wire:click="$set('category','patient guide')">
                                    <i class="fas fa-book mr-2 text-gray-700"></i> Patient Guide
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- ADD CONTENT MODERATION -->

            <a href="{{ route('admin.content.create-content') }}" class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                <i class="fa-solid fa-plus w-4 mr-2"></i>
                Add New Content
            </a>

        </div>

        <!-- TABLE -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse table-fixed shadow-md rounded-lg">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Title</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Speciality</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Areas</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Organization</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Published</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Schedule</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse ($contents as $content)
                        <tr class="hover:bg-gray-50">
                            <!-- Title -->
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-gray-900">{{ Str::limit($content->title, 30) }}</div>
                                @if($content->description)
                                    <div class="text-xs text-gray-500 mt-1">{{ Str::limit($content->description, 40) }}</div>
                                @endif
                            </td>

                            <!-- Category -->
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $content->category === 'new' 
                                        ? 'bg-blue-100 text-blue-700' 
                                        : 'bg-purple-100 text-purple-700' }}">
                                    {{ ucfirst($content->category ?? '-') }}
                                </span>
                            </td>

                            <!-- Speciality -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $content->speciality ? $content->speciality->name : '-' }}
                            </td>

                            <!-- Areas -->
                            <td class="px-6 py-4 text-sm">
                                @if($content->area_ids && count($content->area_ids) > 0)
                                    @php
                                        $selectedAreas = \App\Models\LocationMaster::whereIn('id', $content->area_ids)->get();
                                    @endphp
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($selectedAreas->take(2) as $area)
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
                                                {{ $area->area }}
                                            </span>
                                        @endforeach
                                        @if(count($selectedAreas) > 2)
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
                                                +{{ count($selectedAreas) - 2 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- Organization -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $content->organization ? $content->organization->name : '-' }}
                            </td>

                            <!-- Hospital -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($content->doctor_id != null)
                                  Dr. {{ $content->doctor ? $content->doctor->name : '-' }} <br>
                                    <span class="text-gray-400">Doctor - {{ $content->hospital ? $content->hospital->name : '-' }}</span>
                                @elseif($content->hospital_id != null)
                                    {{ $content->hospital ? $content->hospital->name : '-' }} <br>
                                    <span class="text-gray-400">Hospital</span>
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
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'scheduled' => 'bg-yellow-100 text-yellow-700',
                                    ];
                                    $statusColor = $statusColors[$content->status] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($content->status) }}
                                </span>
                            </td>

                            <!-- Published -->
                            <td class="px-6 py-4">
                                @if($content->is_published)
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-check-circle mr-1"></i> Published
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                        <i class="fas fa-times-circle mr-1"></i> Not Published
                                    </span>
                                @endif
                            </td>

                            <!-- Schedule -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($content->schedule_time_data && isset($content->schedule_time_data['date']))
                                    <div class="text-xs">
                                        <div>{{ \Carbon\Carbon::parse($content->schedule_time_data['date'])->format('M d, Y') }}</div>
                                        @if(isset($content->schedule_time_data['time']))
                                            <div class="text-gray-500">{{ \Carbon\Carbon::parse($content->schedule_time_data['time'])->format('h:i A') }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="action-menu-wrapper">
                                    <button
                                        class="action-btn"
                                        onclick="toggleActionMenu(event,'menu-{{ $content->id }}')">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                    <div id="menu-{{ $content->id }}" class="action-menu hidden">
                                        <ul class="p-2 text-sm text-gray-700 font-medium">
                                            <li>
                                                <button
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                                </button>
                                            </li>
                                            <li>
                                                <button
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                                </button>
                                            </li>
                                            <li>
                                                <button
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 text-red-600 rounded">
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
                            <td colspan="11" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                                <p class="text-lg font-medium text-gray-900">No content found</p>
                                <p class="text-sm text-gray-600">Start by adding your first content</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $contents->links() }}
        </div>
    </div>

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
    const menuHeight = menu.offsetHeight;
    menu.style.display = "";
    menu.style.visibility = "";

    // Check available space
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    let top;

    // If menu goes off-screen bottom → flip up
    if (spaceBelow < menuHeight) {
        top = rect.top - menuHeight - 10;
    } else {
        top = rect.bottom + 10;
    }

    // Position menu
    menu.style.position = "fixed";
    menu.style.top  = top + "px";
    menu.style.left = (rect.left - menu.offsetWidth + rect.width) + "px";
    menu.style.zIndex = "1000";
}
</script>
@endpush

