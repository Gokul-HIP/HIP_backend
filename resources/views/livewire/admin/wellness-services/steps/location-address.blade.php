<div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">3. Location & Address details</h3>
    
    <div class="space-y-6">
        <!-- Address Line 1 -->
        <div>
            <label for="address_line_1" class="block text-sm font-medium text-gray-700 mb-2">
                Address Line 1 <span class="text-red-500">*</span>
            </label>
            <input 
                type="text"
                id="address_line_1"
                wire:model="address_line_1"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Address Line 1">
            @error('address_line_1')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Address Line 2 -->
        <div>
            <label for="address_line_2" class="block text-sm font-medium text-gray-700 mb-2">
                Address Line 2 <span class="text-red-500">*</span>
            </label>
            <input 
                type="text"
                id="address_line_2"
                wire:model="address_line_2"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Address Line 2">
            @error('address_line_2')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- City -->
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                    City <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text"
                    id="city"
                    wire:model="city"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="City">
                @error('city')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- State -->
            <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                    State <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text"
                    id="state"
                    wire:model="state"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="State">
                @error('state')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Pincode -->
            <div>
                <label for="pincode" class="block text-sm font-medium text-gray-700 mb-2">
                    Pincode <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text"
                    id="pincode"
                    wire:model="pincode"
                    maxlength="10"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Pincode">
                @error('pincode')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Latitude -->
            <div>
                <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">
                    Latitude <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number"
                    step="any"
                    id="latitude"
                    wire:model="latitude"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Latitude">
                @error('latitude')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Longitude -->
            <div>
                <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">
                    Longitude <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number"
                    step="any"
                    id="longitude"
                    wire:model="longitude"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Longitude">
                @error('longitude')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
</div>

