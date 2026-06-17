<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Membership Packages</h2>
            <p class="text-sm text-gray-500 mt-1">Manage family subscription plans</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.membership-packages.subscriptions') }}"
                class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium text-gray-700 hover:bg-gray-50">
                View Subscriptions
            </a>
            <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-white text-sm font-medium"
                style="background:#0da2e7;">
                <i class="fas fa-plus mr-2"></i> New Package
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($packages as $package)
            @php
                $packageBranchNames = collect($package->branch_ids ?? [])
                    ->map(fn ($id) => $branchNames[$id] ?? null)
                    ->filter()
                    ->values();
                $benefits = $package->benefits ?? [];
                $visibleBenefits = array_slice($benefits, 0, 3);
                $moreBenefits = max(0, count($benefits) - 3);
                $durationLabel = (int) $package->duration_days === 365 ? 'year' : $package->duration_days.' days';
            @endphp
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="h-1.5" style="background:#0da2e7;"></div>
                <div class="p-5 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h3>
                            <span class="inline-flex mt-2 px-2.5 py-1 rounded-full text-xs font-medium {{ $package->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $package->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="openEditModal({{ $package->id }})"
                                class="text-gray-500 hover:text-[#0da2e7]">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" wire:click="deletePackage({{ $package->id }})"
                                wire:confirm="Delete this package?"
                                class="text-gray-500 hover:text-red-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-3xl font-bold text-gray-900">₹{{ number_format((float) $package->price, 2) }}</p>
                        <p class="text-sm text-gray-500">/ {{ $durationLabel }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <span class="px-3 py-2 rounded-lg bg-gray-50">👥 {{ $package->max_members }} Members</span>
                        <span class="px-3 py-2 rounded-lg bg-gray-50">🩺 {{ $package->max_consultations ?: 'Unlimited' }} Consultations</span>
                        <span class="px-3 py-2 rounded-lg bg-gray-50">🧪 {{ $package->max_lab_tests ?: 'Unlimited' }} Lab Tests</span>
                        <span class="px-3 py-2 rounded-lg bg-gray-50">🪙 {{ $package->max_hip_coins }} HIP Coins</span>
                    </div>

                    @if($packageBranchNames->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($packageBranchNames as $branchName)
                                <span class="px-2.5 py-1 rounded-full text-xs bg-blue-50 text-blue-700">{{ $branchName }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($benefits))
                        <ul class="space-y-1 text-sm text-gray-600">
                            @foreach($visibleBenefits as $benefit)
                                <li class="flex items-start gap-2"><i class="fas fa-check text-green-600 mt-1"></i><span>{{ $benefit }}</span></li>
                            @endforeach
                            @if($moreBenefits > 0)
                                <li class="text-xs text-gray-500">+{{ $moreBenefits }} more</li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500 bg-white border rounded-xl">
                <p class="text-lg font-medium text-gray-900">No membership packages found</p>
                <p class="text-sm mt-1">Create your first family subscription plan.</p>
            </div>
        @endforelse
    </div>

    <flux:modal name="family-package-form" class="p-0 max-w-4xl" wire:close="closeModal">
        <div class="p-6 max-h-[85vh] overflow-y-auto">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ $packageId ? 'Edit Package' : 'New Package' }}</h2>

            <div class="space-y-6">
                <div class="bg-white border rounded-xl p-5 space-y-4">
                    <h3 class="font-semibold text-gray-900">Basic Info</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium mb-1">Name</label>
                            <input type="text" wire:model="name" class="w-full border rounded-lg px-3 py-2">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" class="w-full border rounded-lg px-3 py-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div><label class="block text-sm font-medium mb-1">Price</label><input type="number" step="0.01" wire:model="price" class="w-full border rounded-lg px-3 py-2">@error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror</div>
                        <div><label class="block text-sm font-medium mb-1">Duration Days</label><input type="number" wire:model="duration_days" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm font-medium mb-1">Max Members</label><input type="number" wire:model="max_members" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm font-medium mb-1">HIP Coins</label><input type="number" wire:model="max_hip_coins" class="w-full border rounded-lg px-3 py-2"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-1">Max Consultations (0 = unlimited)</label><input type="number" wire:model="max_consultations" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm font-medium mb-1">Max Lab Tests (0 = unlimited)</label><input type="number" wire:model="max_lab_tests" class="w-full border rounded-lg px-3 py-2"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="is_active" class="form-checkbox">
                        <span>Is Active</span>
                    </label>
                </div>

                <div class="bg-white border rounded-xl p-5 space-y-4">
                    <h3 class="font-semibold text-gray-900">Branch Access</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                        @foreach($hospitals as $hospital)
                            <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox"
                                    @checked(in_array($hospital->id, $branch_ids))
                                    wire:click="toggleBranch({{ $hospital->id }})">
                                <span class="text-sm">{{ $hospital->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 space-y-4">
                    <h3 class="font-semibold text-gray-900">Plan Benefits</h3>
                    @foreach($benefits as $index => $benefit)
                        <div class="flex items-center gap-2">
                            <input type="text" wire:model="benefits.{{ $index }}" class="flex-1 border rounded-lg px-3 py-2" placeholder="Benefit description">
                            <button type="button" wire:click="removeBenefitRow({{ $index }})" class="text-red-500 px-2">×</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addBenefitRow" class="text-sm text-[#0da2e7]">+ Add Benefit</button>
                </div>

                <div class="bg-white border rounded-xl p-5 space-y-4">
                    <h3 class="font-semibold text-gray-900">Terms &amp; Conditions</h3>
                    <p class="text-xs text-gray-500">Shown to users on the family plan screen (title + expandable description).</p>
                    @foreach($terms_conditions as $index => $term)
                        <div class="border rounded-lg p-3 space-y-2">
                            <div class="flex items-start gap-2">
                                <div class="flex-1 space-y-2">
                                    <input type="text" wire:model="terms_conditions.{{ $index }}.title"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        placeholder="Term title (e.g. The Family Health Plan is valid only for the selected plan period.)">
                                    <textarea wire:model="terms_conditions.{{ $index }}.description" rows="2"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        placeholder="Term details (shown when expanded)"></textarea>
                                </div>
                                <button type="button" wire:click="removeTermsRow({{ $index }})" class="text-red-500 px-2 mt-1">×</button>
                            </div>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addTermsRow" class="text-sm text-[#0da2e7]">+ Add Term</button>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                <button type="button" wire:click="closeModal" class="px-4 py-2 rounded-lg bg-gray-500 text-white">Cancel</button>
                <button type="button" wire:click="savePackage" class="px-4 py-2 rounded-lg text-white" style="background:#0da2e7;">Save Package</button>
            </div>
        </div>
    </flux:modal>
</div>
