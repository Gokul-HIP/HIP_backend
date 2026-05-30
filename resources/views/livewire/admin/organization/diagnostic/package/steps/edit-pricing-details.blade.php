<div class="bg-white rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Pricing & Details</h3>

    <div class="space-y-6">
        <div>
            <label class="block text-sm font-medium mb-2">Price (₹)</label>
            <input type="number" step="0.01" wire:model="price"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter Price">
            @error('price')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Discount (%)</label>
            <input type="number" step="0.01" min="0" max="100" wire:model="discount"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter Discount Percentage">
            @error('discount')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Weight</label>
            <input type="number" step="0.01" min="0" wire:model="weight"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter Weight">
            @error('weight')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Home Service -->
        <div class="pt-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="is_home_service"
                    class="w-4 h-4 rounded border-gray-300 text-[#0da2e7] focus:ring-[#0da2e7]">
                <span class="text-sm font-medium text-gray-800">Home Service</span>
            </label>
            @error('is_home_service')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Status -->
        <div class="pt-2">
            <label class="block text-sm font-medium mb-2">Status</label>

            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">Inactive</span>

                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="status" class="sr-only">
                    <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all
                        {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                        <span class="dot w-5 h-5 bg-white rounded-full transition-all
                            {{ $status ? 'translate-x-6' : 'translate-x-0' }}"></span>
                    </span>
                </label>

                <span class="text-sm text-gray-800">Active</span>
            </div>
        </div>
    </div>
</div>

