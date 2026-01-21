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

    <flux:modal name="add-hospital" class="p-0" wire:close="closeModal">
        <div x-data="{ modalReady: false }" 
             x-init="
                $el.closest('dialog').addEventListener('click', (e) => {
                    if (e.target === e.currentTarget && modalReady) {
                        $wire.closeModal();
                    }
                });
             "
             @modal-show.window="
                if ($event.detail.name === 'add-hospital') {
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
            <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Add New Hospital</h1>
            <form wire:submit.prevent="addHospital" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-2 gap-10 mt-4">

                    <div class="space-y-6">

                        <h2 class="text-lg font-semibold">Basic Information</h2>

                        <div>
                            <label class="block text-sm font-medium mb-2">Hospital Name</label>
                            <input type="text" wire:model="hospital_name"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter Hospital Name">
                            @error('hospital_name')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Subtitle</label>
                            <input type="text" wire:model="hospital_subtitle"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter Subtitle">
                            @error('hospital_subtitle')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

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
                                const fileInput = document.getElementById('hospitalLogo');
                                if (fileInput) fileInput.value = '';
                                $wire.removeImage();
                            }
                        }"
                        @reset-file-input.window="
                            if (previewUrl) {
                                URL.revokeObjectURL(previewUrl);
                            }
                            previewUrl = null;
                            const fileInput = document.getElementById('hospitalLogo');
                            if (fileInput) fileInput.value = '';
                        ">
                            <label class="block text-sm font-medium mb-2">Upload Hospital Logo/Image</label>

                            <!-- Upload Box -->
                            <div onclick="document.getElementById('hospitalLogo').click()"
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
                                       id="hospitalLogo" 
                                       wire:model="hospital_logo" 
                                       class="hidden"
                                       accept="image/*"
                                       @change="handleFileChange($event)">
                            </div>

                            @error('hospital_logo')
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

                        <div>
                            <label class="block text-sm font-medium mb-2">About Hospital</label>
                            <textarea rows="3" wire:model="hospital_about"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter About Hospital"></textarea>
                            @error('hospital_about')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <label class="block text-sm font-medium mb-2">
                                Pharmacy <span class="text-red-500">*</span>
                            </label>
                        
                            <button type="button"
                                @click="open = !open"
                                class="w-full border rounded-lg px-4 py-2.5 bg-white flex justify-between">
                                <span>
                                    @if(count($selected_pharmacy_ids))
                                        {{ collect($pharmacies)
                                            ->whereIn('id', $selected_pharmacy_ids)
                                            ->pluck('pharmacy_name')
                                            ->join(', ') }}
                                    @else
                                        Select Pharmacy
                                    @endif
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        
                            <div x-show="open" @click.away="open=false"
                                 class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow">
                                <ul class="p-2 text-sm">
                                    @foreach($pharmacies as $pharmacy)
                                        <li>
                                            <label class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded cursor-pointer">
                                                <input type="checkbox"
                                                       wire:model="selected_pharmacy_ids"
                                                       value="{{ $pharmacy->id }}">
                                                {{ $pharmacy->pharmacy_name }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        
                            @error('selected_pharmacy_ids')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        

                        <div class="relative" x-data="{ open: false }">
                            <label class="block text-sm font-medium mb-2">
                                Diagnostic Center <span class="text-red-500">*</span>
                            </label>
                        
                            <button type="button"
                                @click="open = !open"
                                class="w-full border rounded-lg px-4 py-2.5 bg-white flex justify-between">
                                <span>
                                    {{ optional(
                                        collect($diagnosticCenters)->firstWhere('id', $selected_diagnostic_id)
                                    )->diagnostic_center_name ?? 'Select Diagnostic Center' }}
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        
                            <div x-show="open" @click.away="open=false"
                                 class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow">
                                <ul class="p-2 text-sm">
                                    @foreach($diagnosticCenters as $center)
                                        <li>
                                            <button type="button"
                                                wire:click="$set('selected_diagnostic_id', {{ $center->id }})"
                                                @click="open=false"
                                                class="w-full text-left p-2 hover:bg-gray-100 rounded">
                                                {{ $center->diagnostic_center_name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        
                            @error('selected_diagnostic_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        
                    </div>

                    <div class="space-y-6">
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Address</label>
                            <textarea rows="3" wire:model="hospital_address"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter Address"></textarea>
                            @error('hospital_address')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <h2 class="text-lg font-semibold">Contact Person</h2>
                        
                        <input type="text" wire:model="hospital_admin_name"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Admin Name">
                             @error('hospital_admin_name')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="text" wire:model="hospital_admin_contact"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Contact Details">
                             @error('hospital_admin_contact')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="email" wire:model="hospital_admin_email"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Email">
                             @error('hospital_admin_email')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="text" wire:model="hospital_admin_longitude"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Longitude">
                             @error('hospital_admin_longitude')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="text" wire:model="hospital_admin_latitude"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Latitude">
                             @error('hospital_admin_latitude')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <textarea rows="2" wire:model="hospital_admin_address"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Enter Your Address..."></textarea>
                             @error('hospital_admin_address')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

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

                <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 border-t">  
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
                        <span class="hidden sm:inline text-white">Save Hospital</span>
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