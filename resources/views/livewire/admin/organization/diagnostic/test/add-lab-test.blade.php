<flux:modal name="add-lab-test" class="p-0" x-on:close="$wire.resetInput()">

    <div class="max-w-6xl mx-auto">
        <div>

            <!-- TITLE -->
            <h1 class="text-xl font-semibold text-black-600 mb-1">
                Add New Lab Test
            </h1>
            <p class="text-sm text-gray-500 mb-6">
                Add details for a new test available at {{ $diagnosticName ?? 'Diagnostic Center' }}
            </p>

            <form wire:submit.prevent="saveLabTest" enctype="multipart/form-data">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

                    <!-- LEFT COLUMN : BASIC TEST INFORMATION -->
                    <div class="space-y-6">

                        <h2 class="text-sm font-semibold text-gray-700 border-b pb-2">
                            Basic Test Information
                        </h2>

                        <!-- Test Name -->
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Test Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model.defer="test_name"
                                class="w-full px-4 py-2 rounded border focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g., Complete Blood Count (CBC)">
                            @error('test_name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select
                                wire:model.defer="test_category"
                                class="w-full px-4 py-2 rounded border focus:ring-2 focus:ring-blue-400">
                                <option value="">Select category</option>
                                <option value="Blood Test">Blood Test</option>
                                <option value="Urine Test">Urine Test</option>
                                <option value="Imaging">Imaging</option>
                                <option value="Pathology">Pathology</option>
                            </select>
                            @error('test_category')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Test Code -->
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Test Code / ID (optional)
                            </label>
                            <input
                                type="text"
                                wire:model.defer="test_code"
                                class="w-full px-4 py-2 rounded border"
                                placeholder="e.g., LFT01">
                            @error('test_code')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Description
                            </label>
                            <textarea
                                rows="4"
                                wire:model.defer="test_description"
                                class="w-full px-4 py-2 rounded border"
                                placeholder="Enter a detailed description of the test..."></textarea>
                            @error('test_description')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <!-- RIGHT COLUMN : PRICING, STATUS, UPLOAD -->
                    <div class="space-y-6">

                        <h2 class="text-sm font-semibold text-gray-700 border-b pb-2">
                            Pricing & Availability
                        </h2>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Price (₹) <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                wire:model.defer="test_price"
                                class="w-full px-4 py-2 rounded border"
                                placeholder="₹ 500.00">
                            @error('test_price')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Discount -->
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Discount (optional)
                            </label>
                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model.defer="test_discount"
                                    class="w-full px-4 py-2 rounded border pr-10"
                                    placeholder="10">
                                <span class="absolute right-3 top-2.5 text-gray-400 text-sm">%</span>
                            </div>
                            @error('test_discount')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Upload -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Upload Test Image <span class="text-red-500">*</span></label>

                            <div onclick="document.getElementById('testImage').click()"
                                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                    cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                                x-show="!$wire.test_image">

                                <div class="text-center p-4">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-700">Click to upload</p>
                                    <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                    <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                                </div>

                                <input type="file" id="testImage" wire:model="test_image" class="hidden"
                                    accept="image/*">
                            </div>

                            @error('test_image')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" x-show="$wire.test_image">
                                @if ($test_image)
                                    <img src="{{ $test_image->temporaryUrl() }}" class="preview-img">
                                    <button type="button"
                                        wire:click="removeImage"
                                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                            rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
     
                        <!-- Status -->
                        <div class="pt-2">
                            <label class="block text-sm font-medium mb-2">Status</label>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-600">Inactive</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model.live="status" class="sr-only">
                                    <span class="w-12 h-6 rounded-full px-1 flex items-center transition
                                        {{ $status ? 'bg-blue-500' : 'bg-gray-400' }}">
                                        <span class="w-5 h-5 bg-white rounded-full transition
                                            {{ $status ? 'translate-x-6' : '' }}"></span>
                                    </span>
                                </label>
                                <span class="text-sm text-gray-800">Active</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex justify-end gap-4 mt-10 border-t pt-5">
                        {{-- <button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition w-full sm:w-auto" 
                            type="button" wire:click="closeModal">
                            Cancel
                        </button> --}}
    
                        <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium  transition w-full sm:w-auto" style="background:#f14336" wire:click="closeModal" type="button">
                            <i class="fa-solid fa-times mr-2 text-white"></i>
                            <span class="hidden sm:inline text-white">Cancel</span>
                            <span class="sm:hidden text-white">Cancel</span>
                        </flux:button>
                    
                        {{-- <button type="button" wire:click='resetInput'
                            class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium hover:bg-gray-400 transition w-full sm:w-auto">
                            Reset
                        </button> --}}
    
                        <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280" wire:click='resetInput' type="button">
                            <i class="fa-solid fa-rotate-right mr-2 text-white"></i>
                            <span class="hidden sm:inline text-white">Reset</span>
                            <span class="sm:hidden text-white">Reset</span>
                        </flux:button>
    
                        <flux:button variant="primary" type="submit" class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" style="background:#0da2e7">
                            <i class="fa-solid fa-check mr-2 text-white"></i>
                            <span class="hidden sm:inline text-white">Save Lab Test</span>
                            <span class="sm:hidden text-white">Save</span>
                        </flux:button>
                </div>

            </form>
        </div>
    </div>

</flux:modal>
