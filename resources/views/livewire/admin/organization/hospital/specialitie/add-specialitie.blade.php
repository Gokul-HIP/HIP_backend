<div>
    <style>
        .image-box { min-height: 150px; }
        .preview-box { min-height: 200px; }
        .preview-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        [x-cloak] { display: none !important; }
    </style>

    <flux:modal name="add-specialitie" class="p-0" wire:close="closeModal">
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
                if ($event.detail.name === 'add-specialitie') {
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
                    Add New Speciality
                </h1>

                <form wire:submit.prevent="addSpeciality" enctype="multipart/form-data">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-10 mt-4">

                        <!-- LEFT -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Basic Information</h2>

                            <!-- Speciality Name -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Speciality Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="speciality_name"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="Enter speciality name">
                                @error('speciality_name')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Department Category -->
                            <div class="relative" x-data="{ open: false, selected: @entangle('department_category') }">
                                <label class="block text-sm font-medium mb-2">
                                    Department Category <span class="text-red-500">*</span>
                                </label>

                                <button type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center justify-between w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5">
                                    <span x-text="selected || 'Select department category'"></span>
                                    <i class="fas fa-chevron-down ml-2"></i>
                                </button>

                                <div x-show="open" @click.away="open = false" x-transition
                                    class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <ul class="p-2 text-sm">
                                        @foreach($specialities as $speciality)
                                            <li>
                                                <button type="button"
                                                    wire:click="$set('department_category','{{ $speciality['name'] }}')"
                                                    @click="selected='{{ $speciality['name'] }}'; open=false"
                                                    class="w-full text-left p-2 hover:bg-gray-100 rounded">
                                                    {{ $speciality['name'] }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                @error('department_category')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Additional Details</h2>

                            <!-- Speciality Code -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Speciality Code</label>
                                <input type="text" wire:model="speciality_code"
                                    class="w-full px-4 py-2 rounded-lg border bg-gray-50"
                                    readonly>
                            </div>

                            <!-- IMAGE UPLOAD (Pharmacy style) -->
                            <div 
                                x-data="{ 
                                    previewUrl: null,
                                    handleFileChange(event) {
                                        const file = event.target.files[0];
                                        if (file) {
                                            if (this.previewUrl) {
                                                URL.revokeObjectURL(this.previewUrl);
                                            }
                                            this.previewUrl = URL.createObjectURL(file);
                                        }
                                    },
                                    clearPreview() {
                                        if (this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                        }
                                        this.previewUrl = null;
                                        const fileInput = document.getElementById('specialityLogo');
                                        if (fileInput) fileInput.value = '';
                                        $wire.removeImage();
                                    }
                                }"
                                @reset-file-input.window="
                                    if (previewUrl) {
                                        URL.revokeObjectURL(previewUrl);
                                    }
                                    previewUrl = null;
                                    const fileInput = document.getElementById('specialityLogo');
                                    if (fileInput) fileInput.value = '';
                                "
                            >
                                <label class="block text-sm font-medium mb-2">
                                    Upload Speciality Logo/Image <span class="text-red-500">*</span>
                                </label>

                                <!-- Upload Box -->
                                <div @click="document.getElementById('specialityLogo').click()"
                                    class="image-box border-2 border-dashed rounded-lg flex items-center justify-center cursor-pointer bg-gray-50"
                                    x-show="!previewUrl">

                                    <div class="text-center p-4">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-sm">Click to upload</p>
                                        <p class="text-xs text-gray-400">PNG, JPG up to 2MB</p>
                                    </div>

                                    <input type="file"
                                           id="specialityLogo"
                                           wire:model="speciality_logo"
                                           class="hidden"
                                           accept="image/*"
                                           @change="handleFileChange($event)">
                                </div>

                                @error('speciality_logo')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror

                                <!-- Preview -->
                                <div class="preview-box border rounded-lg relative overflow-hidden"
                                     x-show="previewUrl" x-cloak>
                                    <img :src="previewUrl" class="preview-img">
                                    <button type="button"
                                        @click.stop="clearPreview()"
                                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 rounded-full">
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <!-- STATUS -->
                            <div class="pt-2">
                                <label class="block text-sm font-medium mb-2">Status</label>

                                <div class="flex items-center space-x-4">
                                    <span class="text-sm">Inactive</span>

                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="status" class="sr-only">
                                        <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all
                                            {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                            <span class="w-5 h-5 bg-white rounded-full transition-all
                                                {{ $status ? 'translate-x-6' : 'translate-x-0' }}"></span>
                                        </span>
                                    </label>

                                    <span class="text-sm">Active</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="speciality_description" rows="4"
                            class="w-full px-4 py-2.5 rounded-lg border resize-none"></textarea>
                        @error('speciality_description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

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
                                     style="background:var(--button-color); color:#ffffff !important;">
                            <i class="fa-solid fa-check mr-2" style="color:#ffffff !important;"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;">Save Speciality</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Save</span>
                        </flux:button>
                    </div>

                </form>
            </div>
        </div>
    </flux:modal>
</div>
