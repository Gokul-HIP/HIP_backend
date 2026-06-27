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

    <flux:modal name="add-procedure" class="p-0" wire:close="closeModal">
        <div 
            x-data="{ modalReady: false }"
            x-init="
                $el.closest('dialog').addEventListener('click', (e) => {
                    if (e.target === e.currentTarget && modalReady) {
                        $wire.closeModal();
                    }
                });
            "
            @modal-show.window="
                if ($event.detail.name === 'add-procedure') {
                    modalReady = false;
                    $wire.resetInput().then(() => {
                        setTimeout(() => modalReady = true, 300);
                    });
                }
            "
        >

            <!-- CLOSE BUTTON -->
            <button type="button"
                wire:click="closeModal"
                class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-50 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                ✕
            </button>

            <div class="relative max-w-5xl mx-auto" @click.stop>

                <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">
                    Add New Procedure
                </h1>

                <form wire:submit.prevent="addProcedure" enctype="multipart/form-data">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-10 mt-4">

                        <!-- LEFT -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Basic Information</h2>

                            <!-- Procedure Name -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Procedure Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="procedure_name"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="Enter procedure name">
                                @error('procedure_name')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Speciality -->
                            <div class="relative" x-data="{ open: false }">
                                <label class="block text-sm font-medium mb-2">
                                    Speciality <span class="text-red-500">*</span>
                                </label>
                            
                                <button type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center justify-between w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5">
                                    <span>
                                        {{ $speciality_id
                                            ? $specialities->firstWhere('id', $speciality_id)?->speciality_name
                                            : 'Select speciality'
                                        }}
                                    </span>
                                    <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                            
                                <div x-show="open" @click.away="open=false" x-transition
                                    class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <ul class="p-2 text-sm">
                                        @foreach($specialities as $speciality)
                                            <li>
                                                <button type="button"
                                                    wire:click="$set('speciality_id', {{ $speciality->id }})"
                                                    @click="open=false"
                                                    class="w-full text-left p-2 hover:bg-gray-100 rounded">
                                                    {{ $speciality->speciality_name }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            
                                @error('speciality_id')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Estimated Time -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Estimated Time <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="estimated_time"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="e.g., 2-3 hours">
                                @error('estimated_time')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Recovery Time -->
                            <div class="relative" 
                                x-data="{ open: false }">

                                <label class="block text-sm font-medium mb-2">
                                    Recovery Time <span class="text-red-500">*</span>
                                </label>

                                <!-- INPUT ROW -->
                                <div class="flex gap-2">

                                    <!-- FROM -->
                                    <input type="number"
                                        min="0"
                                        wire:model.defer="recovery_from"
                                        placeholder="From"
                                        class="w-1/3 border border-gray-300 rounded-lg px-3 py-2.5
                                                focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">

                                    <!-- TO -->
                                    <input type="number"
                                        min="0"
                                        wire:model.defer="recovery_to"
                                        placeholder="To"
                                        class="w-1/3 border border-gray-300 rounded-lg px-3 py-2.5
                                                focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">

                                    <!-- UNIT DROPDOWN -->
                                    <div class="relative w-1/3">

                                        <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center justify-between w-full bg-white 
                                                border border-gray-300 rounded-lg px-3 py-2.5
                                                focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">

                                            <span>
                                                {{ $recovery_unit ? ucfirst($recovery_unit) : 'Unit' }}
                                            </span>

                                            <i class="fas fa-chevron-down ml-2 text-gray-500"></i>
                                        </button>

                                        <!-- DROPDOWN -->
                                        <div x-show="open"
                                            x-cloak
                                            x-transition
                                            @click.away="open = false"
                                            class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow-lg">

                                            <ul class="p-1 text-sm">
                                                @foreach(['days', 'weeks', 'months'] as $unit)
                                                    <li>
                                                        <button type="button"
                                                            wire:click="$set('recovery_unit', '{{ $unit }}')"
                                                            @click="open = false"
                                                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-100">
                                                            {{ ucfirst($unit) }}
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                    </div>
                                </div>

                                <!-- ERRORS -->
                                @error('recovery_from')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror
                                @error('recovery_to')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror
                                @error('recovery_unit')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror

                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Success Rate (%) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="success_rate" step="0.01" min="0" max="100"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="e.g., 90">
                                @error('success_rate')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Hospitalization Days <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="hospitalization_days" min="0"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="e.g., 2">
                                @error('hospitalization_days')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Additional Details</h2>

                            <!-- Procedure Code -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Procedure Code</label>
                                <input type="text" wire:model="procedure_code"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50"
                                    placeholder="Procedure Code" readonly>
                            </div>

                            <!-- Cost -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Cost (₹) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="cost" step="0.01" min="0"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="Enter cost in rupees">
                                @error('cost')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Discount Price (₹) <span class="text-gray-400 text-xs">(optional)</span>
                                </label>
                                <input type="number" wire:model="discount" step="0.01" min="0"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="e.g. 1500 (leave empty for full price)">
                                @error('discount')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Upload Procedure Image -->
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
                                    const fileInput = document.getElementById('procedureImage');
                                    if (fileInput) fileInput.value = '';
                                    $wire.removeImage();
                                }
                            }"
                            @reset-file-input.window="
                                if (previewUrl) {
                                    URL.revokeObjectURL(previewUrl);
                                }
                                previewUrl = null;
                                const fileInput = document.getElementById('procedureImage');
                                if (fileInput) fileInput.value = '';
                            ">
                                <label class="block text-sm font-medium mb-2">
                                    Upload Procedure Image <span class="text-red-500">*</span>
                                </label>

                                <!-- Upload Box -->
                                <div onclick="document.getElementById('procedureImage').click()"
                                    class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                        cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                                    x-show="!previewUrl"
                                    style="min-height: 150px;">

                                    <div class="text-center p-4">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-700">Click to upload</p>
                                        <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                                    </div>

                                    <input type="file" 
                                        id="procedureImage" 
                                        wire:model="procedure_image" 
                                        class="hidden"
                                        accept="image/*"
                                        @change="handleFileChange($event)">
                                </div>

                                @error('procedure_image')
                                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                                @enderror

                                <!-- Preview Box -->
                                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                                    x-show="previewUrl"
                                    x-cloak
                                    style="min-height: 200px;">
                                    <img :src="previewUrl" 
                                        style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;" 
                                        alt="Preview">
                                    <button type="button"
                                        @click.stop="clearPreview()"
                                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                            rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
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
                                            <span class="w-5 h-5 bg-white rounded-full transition-all
                                                {{ $status ? 'translate-x-6' : 'translate-x-0' }}"></span>
                                        </span>
                                    </label>

                                    <span class="text-sm text-gray-800">Active</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="description" rows="4"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition resize-none"
                            placeholder="Describe the procedure in detail..."></textarea>
                        @error('description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    @include('livewire.admin.organization.hospital.procedure.partials.common-questions-form')

                    <!-- BUTTONS -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 border-t">
                        <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" 
                        style="background:#f14336; color:#ffffff !important;" wire:click="closeModal" type="button">
                            <i class="fa-solid fa-times mr-2 text-white"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;">Cancel</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Cancel</span>
                        </flux:button>

                        <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280" wire:click='resetInput' type="button">
                            <i class="fa-solid fa-rotate-right mr-2" style="color:#ffffff !important;"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;">Reset</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Reset</span>
                        </flux:button>

                        <flux:button variant="primary" type="submit" 
                                     class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" 
                                     style="background:#0da2e7; color:#ffffff !important;">
                            <i class="fa-solid fa-check mr-2" style="color:#ffffff !important;"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;">Save Procedure</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Save</span>
                        </flux:button>
                    </div>

                </form>
            </div>
        </div>
    </flux:modal>
</div>