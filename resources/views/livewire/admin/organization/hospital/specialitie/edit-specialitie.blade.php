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

    <flux:modal name="edit-specialitie" class="p-0" wire:close="closeModal">
        <div x-data="modalHandler()" @click.outside="$wire.closeModal()">
            <div class="relative max-w-5xl mx-auto">

                <flux:modal.close
                    class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10"
                    wire:click="closeModal" />

                <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">
                    Edit Speciality
                </h1>

                <form wire:submit.prevent="updateSpeciality" enctype="multipart/form-data"
                      x-data="{ imageUploading: false }"
                      @image-uploading.window="imageUploading = $event.detail.uploading">

                    <div class="grid grid-cols-2 gap-10 mt-4">

                        <!-- LEFT -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Basic Information</h2>

                            <div>
                                <input type="text" wire:model="speciality_name"
                                       class="glass-input w-full px-4 py-2 rounded-lg"
                                       placeholder="Enter Speciality Name">
                                @error('speciality_name')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="relative" x-data="{ open: false, selected: @entangle('department_category') }">
                                <label class="block text-sm font-medium mb-2">
                                    Department Category <span class="text-red-500">*</span>
                                </label>

                                <button type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center justify-between w-full bg-white border rounded-lg px-4 py-2.5">
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

                            <div>
                                <input type="text" wire:model="speciality_code"
                                       class="glass-input w-full px-4 py-2 rounded-lg bg-gray-50"
                                       readonly>
                            </div>

                            <!-- IMAGE UPLOAD (DOCTOR STYLE) -->
                            <div x-data="{ 
                                previewUrl: null,
                                showExisting: true,
                                isUploading: false,
                                uploadProgress: 0,
                                
                                handleFileChange(event) {
                                    const file = event.target.files[0];
                                    if (file) {
                                        if (this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                        }
                                        this.previewUrl = URL.createObjectURL(file);
                                        this.showExisting = false;
                                        this.isUploading = true;
                                        this.uploadProgress = 0;
                                        
                                        window.dispatchEvent(new CustomEvent('image-uploading', { 
                                            detail: { uploading: true } 
                                        }));
                                        
                                        const input = event.target;
                                        
                                        @this.upload('speciality_logo', input.files[0], 
                                            () => {
                                                this.isUploading = false;
                                                this.uploadProgress = 100;
                                                window.dispatchEvent(new CustomEvent('image-uploading', { 
                                                    detail: { uploading: false } 
                                                }));
                                            },
                                            () => {
                                                this.isUploading = false;
                                                this.uploadProgress = 0;
                                                window.dispatchEvent(new CustomEvent('image-uploading', { 
                                                    detail: { uploading: false } 
                                                }));
                                                alert('File upload failed.');
                                            },
                                            (event) => {
                                                this.uploadProgress = Math.round(event.detail.progress || 0);
                                            }
                                        );
                                    }
                                },
                                
                                clearPreview() {
                                    if (this.previewUrl) {
                                        URL.revokeObjectURL(this.previewUrl);
                                    }
                                    this.previewUrl = null;
                                    this.isUploading = false;
                                    this.uploadProgress = 0;
                                    this.showExisting = !!this.$wire.existing_logo && !this.$wire.remove_image;
                                    const fileInput = document.getElementById('specialityLogoEdit');
                                    if (fileInput) fileInput.value = '';
                                    @this.removeImage();
                                },
                                
                                removeExisting() {
                                    this.showExisting = false;
                                    @this.removeImage();
                                },
                                
                                restoreExisting() {
                                    this.showExisting = true;
                                    @this.restoreImage();
                                },
                                
                                init() {
                                    this.$nextTick(() => {
                                        this.showExisting = !!this.$wire.existing_logo && !this.$wire.remove_image && !this.previewUrl;
                                    });
                                    
                                    this.$watch('$wire.existing_logo', (value) => {
                                        if (value && !this.$wire.remove_image && !this.previewUrl) {
                                            this.showExisting = true;
                                        } else if (!value) {
                                            this.showExisting = false;
                                        }
                                    });
                                    
                                    this.$watch('$wire.speciality_logo', (value) => {
                                        if (!value && this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                            this.previewUrl = null;
                                            const fileInput = document.getElementById('specialityLogoEdit');
                                            if (fileInput) fileInput.value = '';
                                        }
                                        if (!value && this.$wire.existing_logo && !this.$wire.remove_image) {
                                            this.showExisting = true;
                                        }
                                    });
                                    
                                    this.$watch('$wire.remove_image', (value) => {
                                        if (!value && this.$wire.existing_logo && !this.previewUrl) {
                                            this.showExisting = true;
                                        } else if (value) {
                                            this.showExisting = false;
                                        }
                                    });
                                    
                                    Livewire.on('reset-file-input', () => {
                                        if (this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                        }
                                        this.previewUrl = null;
                                        this.isUploading = false;
                                        this.uploadProgress = 0;
                                        this.showExisting = !!this.$wire.existing_logo && !this.$wire.remove_image;
                                        const fileInput = document.getElementById('specialityLogoEdit');
                                        if (fileInput) fileInput.value = '';
                                    });
                                }
                            }">
                                <label class="block text-sm font-medium mb-2">Speciality Image</label>

                                <!-- Existing Image -->
                                <div x-show="showExisting && !previewUrl && $wire.existing_logo && !$wire.remove_image">
                                    <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                                        <img src="{{ $existing_logo ? asset('storage/speciality/' . $existing_logo) : '' }}" 
                                            class="preview-img">
                                        <button type="button"
                                            @click.stop="removeExisting()"
                                            class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                            rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <button type="button"
                                        @click="document.getElementById('specialityLogoEdit').click()"
                                        class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                        <i class="fas fa-edit mr-2"></i>Change Image
                                    </button>
                                </div>

                                <!-- Image Removal Message -->
                                <div x-show="$wire.remove_image && !$wire.speciality_logo && !previewUrl" 
                                    class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-info-circle mr-1"></i>Image will be removed
                                    </p>
                                    <button type="button"
                                        @click="restoreExisting()"
                                        class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                        <i class="fas fa-undo mr-1"></i>Cancel Removal
                                    </button>
                                </div>

                                <!-- Upload Box -->
                                <div @click="document.getElementById('specialityLogoEdit').click()"
                                    class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                        cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                                    x-show="!previewUrl && !$wire.speciality_logo && !showExisting && !$wire.remove_image">

                                    <div class="text-center p-4">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-700">Click to upload</p>
                                        <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                                    </div>

                                    <input type="file" 
                                        id="specialityLogoEdit" 
                                        class="hidden" 
                                        accept="image/*"
                                        @change="handleFileChange($event)">
                                </div>

                                @error('speciality_logo')
                                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                                @enderror

                                <!-- New Image Preview -->
                                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                                    x-show="previewUrl"
                                    x-cloak>
                                    <img :src="previewUrl" class="preview-img">
                                    
                                    <div x-show="isUploading" 
                                        class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                        <div class="text-center text-white">
                                            <div class="mb-2">
                                                <i class="fas fa-spinner fa-spin text-3xl"></i>
                                            </div>
                                            <p class="text-sm font-medium">Uploading...</p>
                                            <p class="text-xs mt-1" x-text="uploadProgress + '%'"></p>
                                        </div>
                                    </div>
                                    
                                    <button type="button"
                                        @click.stop="clearPreview()"
                                        x-show="!isUploading"
                                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                            rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                        <i class="fas fa-times"></i>
                                    </button>   
                                </div>
                            </div>


                            <!-- STATUS -->
                            <div>
                                <label class="block text-sm font-medium mb-3">Status</label>
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
                                    <span class="text-sm font-medium">Active</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mt-6">
                        <textarea wire:model="speciality_description"
                                  rows="4"
                                  class="glass-input w-full px-4 py-2 rounded-lg"
                                  placeholder="Describe the speciality..."></textarea>
                        @error('speciality_description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- BUTTONS -->
                    <div class="flex justify-end gap-4 pt-6 border-t mt-10">
                        <flux:button wire:click="closeModal" type="button"
                                     class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#f14336">
                            <i class="fa-solid fa-times mr-2 text-white"></i>
                            <span class="hidden sm:inline text-white">Cancel</span>
                            <span class="sm:hidden text-white">Cancel</span>
                        </flux:button>

                        <flux:button wire:click="resetInput" type="button"
                                     class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280">
                            <i class="fa-solid fa-rotate-right mr-2 text-white"></i>
                            <span class="hidden sm:inline text-white">Reset</span>
                            <span class="sm:hidden text-white">Reset</span>
                        </flux:button>

                        <flux:button type="submit"
                                     class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" style="background:#0da2e7"
                                     x-bind:disabled="imageUploading"
                                     x-bind:class="{ 'opacity-50 cursor-not-allowed': imageUploading }">
                            <i class="fa-solid fa-spinner fa-spin mr-2 text-white" x-show="imageUploading"></i>
                            <i class="fa-solid fa-check mr-2 text-white" x-show="!imageUploading"></i>
                            <span class="text-white" x-text="imageUploading ? 'Uploading...' : 'Update Speciality'"></span>
                        </flux:button>
                    </div>

                </form>
            </div>
        </div>
    </flux:modal>

    @script
    <script>
        function modalHandler() {
            return {}
        }
    </script>
    @endscript
</div>
