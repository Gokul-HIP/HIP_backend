<div>
    <style>
    .image-box {
        min-height: 150px;
    }

    .preview-box {
        min-height: 200px;
    }

    .preview-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    [x-cloak] { display: none !important; }
    </style>

    <flux:modal name="add-pharmacy" class="p-0" wire:close="closeModal">
        <div x-data="{ modalReady: false }" 
             x-init="
                $el.closest('dialog').addEventListener('click', (e) => {
                    if (e.target === e.currentTarget && modalReady) {
                        $wire.closeModal();
                    }
                });
             "
             @modal-show.window="
                if ($event.detail.name === 'add-pharmacy') {
                    modalReady = false;
                    $wire.resetInput().then(() => {
                        setTimeout(() => modalReady = true, 300);
                    });
                }
             ">
            
        <button type="button"
            wire:click="closeModal"
            class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-50 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

            <div class="relative max-w-5xl mx-auto" @click.stop>

        <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Add New Pharmacy</h1>

            <form wire:submit.prevent='addPharmacy' method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-2 gap-10">

                    <!-- LEFT COLUMN -->
                    <div class="space-y-6">

                        <h2 class="text-lg font-semibold">Pharmacy Details</h2>
                        
                        <div>
                            <input type="text" name="pharmacy_name" wire:model='pharmacy_name'
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Enter Pharmacy Name">
                                @error('pharmacy_name')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                        <div>
                            <input type="text" name="pharmacy_id" readonly wire:model='pharmacy_id'
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Pharmacy ID">
                                @error('pharmacy_id')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                        <div>
                            <input type="text" name="address" wire:model='pharmacy_address'
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Enter Full Address">
                                @error('pharmacy_address')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                        <div>
                            <input type="text" name="license_number" wire:model='pharmacy_license_number'
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Enter License Number">
                                @error('pharmacy_license_number')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                        <div>
                            <input type="text" name="gst_number" wire:model='pharmacy_gst_num'
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Enter GST Number (Optional)">
                                @error('pharmacy_gst_num')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="space-y-6">

                        <h2 class="text-lg font-semibold">Contact Details</h2>

                        <div>
                            <input type="text" name="contact_person_name" wire:model='pharmacy_contact_person_name'
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Contact Person Name">
                                @error('pharmacy_contact_person_name')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                        <div>
                            <input type="text" name="contact_number" wire:model='pharmacy_contact_person_number'
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Contact Number">
                                @error('pharmacy_contact_person_number')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                        <div>
                            <input type="email" name="email_address" wire:model='pharmacy_contact_person_email'
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Email Address">
                                @error('pharmacy_contact_person_email')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                        </div>

                        <!-- TIME -->
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-600">Opening Hours</label>

                            <div class="flex items-start gap-3">

                                <div class="w-full">
                                    <input type="time" wire:model="pharmacy_opening_time"
                                        class="w-full px-4 py-2 rounded-lg border glass-input">
                                    @error('pharmacy_opening_time')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="pt-2 text-gray-600 font-medium">to</div>

                                <div class="w-full">
                                    <input type="time" wire:model="pharmacy_closing_time"
                                        class="w-full px-4 py-2 rounded-lg border glass-input">
                                    @error('pharmacy_closing_time')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <!-- IMAGE UPLOAD -->
                        <div x-data="{ 
                            previewUrl: null,
                            handleFileChange(event) {
                                const file = event.target.files[0];
                                if (file) {
                                    this.previewUrl = URL.createObjectURL(file);
                                }
                            },
                            clearPreview() {
                                if (this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                }
                                this.previewUrl = null;
                                const fileInput = document.getElementById('pharmacyLogo');
                                if (fileInput) fileInput.value = '';
                                $wire.removeImage();
                            }
                        }"
                        @reset-file-input.window="
                            if (previewUrl) {
                                URL.revokeObjectURL(previewUrl);
                            }
                            previewUrl = null;
                            const fileInput = document.getElementById('pharmacyLogo');
                            if (fileInput) fileInput.value = '';
                        ">
                            <label class="block text-sm font-medium mb-2">Upload Pharmacy Image</label>

                            <!-- Upload Box -->
                            <div onclick="document.getElementById('pharmacyLogo').click()"
                                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                       cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                                x-show="!previewUrl">

                                <div class="text-center p-4">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-700">Click to upload</p>
                                    <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                    <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                                </div>

                                <input type="file" 
                                       id="pharmacyLogo" 
                                       wire:model="pharmacy_logo" 
                                       class="hidden"
                                       accept="image/*"
                                       @change="handleFileChange($event)">
                            </div>

                            @error('pharmacy_logo')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                            <!-- Preview Box -->
                            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                                 x-show="previewUrl"
                                 x-cloak>
                                <img :src="previewUrl" class="preview-img" alt="Preview">
                                <button type="button"
                                    @click.stop="clearPreview()"
                                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                        rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STATUS -->
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

                <!-- BUTTONS -->
                <div class="flex justify-end gap-4 mt-10">
                        {{-- <button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition w-full sm:w-auto" 
                            type="button" wire:click="closeModal">
                            Cancel
                        </button> --}}
    
                    <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" 
                                 style="background:#f14336" 
                                 wire:click="closeModal" 
                                 type="button">
                        <i class="fa-solid fa-times mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Cancel</span>
                        <span class="sm:hidden text-white">Cancel</span>
                    </flux:button>

                    <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" 
                                 style="background:#6b7280" 
                                 wire:click='resetInput' 
                                 type="button">
                        <i class="fa-solid fa-rotate-right mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Reset</span>
                        <span class="sm:hidden text-white">Reset</span>
                    </flux:button>

                    <flux:button variant="primary" type="submit" class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" style="background:#0da2e7">
                        <i class="fa-solid fa-check mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Save Pharmacy</span>
                        <span class="sm:hidden text-white">Save</span>
                    </flux:button>
                </div>

            </form>
            </div>
        </div>
    </flux:modal>

    @script
    <script>
        lucide.createIcons();
    </script>
    @endscript
</div>
