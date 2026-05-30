<div class="space-y-6"
    @disease-package-added.window="$wire.refreshPackages();"
    @disease-package-updated.window="$wire.refreshPackages();">
    @livewire('admin.organization.diagnostic.disease-package.add-disease-package')
    @livewire('admin.organization.diagnostic.disease-package.edit-disease-package')

    <div>
        <div class="relative h-32 rounded-xl overflow-hidden">
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-gray-200 border border-gray-300 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-gray-900 text-2xl font-bold">{{ ucfirst($diagnostic->name) }} - Disease Packages</h1>
                    <p class="text-sm text-gray-900/90 mt-1">Manage disease-based diagnostic packages</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 max-w-md">
        <div class="bg-white p-4 border rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Total Disease Packages</p>
            <p class="text-4xl font-bold mt-1">{{ $packages->count() }}</p>
        </div>
        <div class="bg-white p-4 border rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Active Packages</p>
            <p class="text-4xl font-bold mt-1">{{ $packages->where('status', 'active')->count() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 border">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input type="text" placeholder="Search disease package..."
                        class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg"
                        wire:model.live.debounce.300ms="search" />
                </div>

                <div class="relative">
                    <button onclick="toggleFilter('diseasePackageStatus')" class="filter-btn">
                        <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                        <span>{{ $status === 'all' ? 'All Status' : ucfirst($status) }}</span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>
                    <div id="diseasePackageStatus" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li><button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded" wire:click="$set('status','all')">All Status</button></li>
                            <li><button class="inline-flex items-center w-full p-2 hover:bg-green-50 rounded" wire:click="$set('status','active')">Active</button></li>
                            <li><button class="inline-flex items-center w-full p-2 hover:bg-red-50 rounded" wire:click="$set('status','inactive')">Inactive</button></li>
                        </ul>
                    </div>
                </div>
            </div>

            <button wire:click="$dispatch('open-add-disease-package', { diagnosticId: {{ $diagnosticId }} })"
                class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                <i class="fa-solid fa-plus w-4 mr-2 text-white"></i>
                Add Disease Package
            </button>
        </div>

        <table class="w-full border-collapse shadow-md rounded-lg">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Disease / Package Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Price</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Discount</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Home Service</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($packages as $package)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">{{ $package->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $package->description ? \Illuminate\Support\Str::limit($package->description, 50) : '-' }}</td>
                        <td class="px-6 py-4 text-sm">₹{{ number_format($package->price ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-sm">{{ $package->discount ?? 0 }}%</td>
                        <td class="px-6 py-4 text-sm">{{ $package->is_home_service ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $package->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($package->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 action-cell">
                            <button onclick="toggleActionMenu(event,'disease-menu-{{ $package->id }}')" class="action-btn">
                                <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                            </button>
                            <div id="disease-menu-{{ $package->id }}" class="action-menu hidden">
                                <ul class="p-2 text-sm text-gray-700 font-medium">
                                    <li>
                                        <button type="button" wire:click="edit({{ $package->id }})">
                                            <i class="fa-regular fa-edit"></i> Edit
                                        </button>
                                    </li>
                                    <li>
                                        <button class="delete" wire:click="delete({{ $package->id }})">
                                            <i class="fa-regular fa-trash-can"></i> Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-8 text-center text-gray-500" colspan="7">
                            <i class="fas fa-virus text-gray-400 mb-3" style="font-size: 3rem;"></i>
                            <p class="text-lg font-medium text-gray-900">No disease packages found</p>
                            <p class="text-sm text-gray-600">Start by adding your first disease package</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="delete-disease-package" class="p-0" wire:close="closeModal">
        <div x-data @click.outside="$wire.closeModal()">
            <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer" wire:click="closeModal" />
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Disease Package?</h2>
            <p class="text-sm text-gray-500 mb-6">This action cannot be reversed.</p>
            <div class="flex justify-end gap-4">
                <flux:button variant="ghost" wire:click="closeModal">Cancel</flux:button>
                <button type="button" wire:click="destroy" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">Delete</button>
            </div>
        </div>
    </flux:modal>
</div>
