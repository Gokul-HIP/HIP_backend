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

    <flux:modal name="edit-doctor" class="p-0" wire:close="closeModal">
        <div x-data="modalHandler()" @click.outside="$wire.closeModal()">
            <div class="relative max-w-5xl mx-auto">

                <flux:modal.close
                    class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10"
                    wire:click="closeModal" />

                <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Edit Doctor</h1>

        <form wire:submit.prevent="editDoctorProfile" enctype="multipart/form-data"
            x-data="{ imageUploading: false }"
            @image-uploading.window="imageUploading = $event.detail.uploading">

            <div class="grid grid-cols-2 gap-10">

                <!-- LEFT COLUMN -->
                <div class="space-y-6">

                    <!-- Doctor Name -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Doctor Name*</label>
                        <input type="text" wire:model="name"
                               class="w-full px-4 py-2 rounded border"
                               placeholder="Enter doctor name">
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Gender -->
                    <div 
                        x-data="genderSelect({
                            value: @entangle('gender')
                        })"
                        class="relative"
                        >
                        <label class="block text-sm font-medium mb-2">Gender</label>

                        <!-- BUTTON -->
                        <button type="button"
                            @click="toggle()"
                            class="w-full flex items-center justify-between px-4 py-2 
                                border border-gray-300 rounded-lg focus:ring-2
                                focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">

                            <span x-text="value || 'Select gender'"></span>

                            <!-- Protected Chevron Icon -->
                            <span class="pointer-events-none">
                                <i class="fa-solid fa-angle-down w-4 h-4"></i>
                            </span>
                        </button>

                        <!-- OPTIONS -->
                        <div x-show="open"
                            x-transition
                            @click.away="close()"
                            class="absolute left-0 w-full bg-white border rounded-lg mt-2 shadow-lg z-50">

                            <template x-for="item in options" :key="item">
                                <button type="button"
                                    @click="select(item)"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 border-b last:border-0"
                                    x-text="item">
                                </button>
                            </template>
                        </div>

                        <div class="mt-1 text-red-500 text-sm">
                            @error('gender') {{ $message }} @enderror
                        </div>
                    </div>

                    {{-- Organization/Hospital selection hidden as requested --}}

                    <!-- Organization -->
                    {{-- <div class="relative">
                        <label class="block text-sm font-medium mb-2">Organization</label>
                        <button type="button" 
                            onclick="toggleDropdown('editOrgMenu', this)" 
                            class="filter-btn w-full flex items-center justify-between px-4 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7] {{ $organization_id ? 'border-[#0da2e7]' : 'border-gray-300' }}">
                            <span class="selected-text">
                                @if($organization_id)
                                    {{ $organizations->firstWhere('id', $organization_id)?->name ?? 'Select Organization' }}
                                @else
                                    Select Organization
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <div id="editOrgMenu" class="filter-dropdown hidden absolute left-0 w-full bg-white border mt-2 rounded-lg shadow-lg z-30">
                            @foreach ($organizations as $option)
                                <button type="button" 
                                    wire:click="selectOrganization('{{ $option->id }}')"
                                    class="w-full px-4 py-2 text-sm text-left hover:bg-gray-100 border-b last:border-b-0">
                                    {{ $option->name }}
                                </button>
                            @endforeach
                        </div>
                        
                        @error('organization_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div> --}}

                    <!-- Hospital -->
                    <div class="relative"
                        x-data="hospitalMultiSelect({
                            organizationId: @entangle('organization_id'),
                            hospitalIds: @entangle('hospital_ids').live,
                            hospitals: @entangle('hospitals')
                        })"
                         wire:key="edit-hospital-{{ $organization_id }}">
                        <label class="block text-sm font-medium mb-2">Hospital <span class="text-gray-400 font-normal">(optional)</span></label>

                        <button type="button"
                            @click="toggleDropdown()"
                            :disabled="!organizationId || hospitals.length === 0"
                            class="filter-btn w-full flex justify-between items-center px-4 py-2 border rounded-lg"
                            :class="{
                                'opacity-50 cursor-not-allowed': !organizationId || hospitals.length === 0
                            }">

                            <span x-text="getDisplayText()"></span>

                            <svg class="w-4 h-4 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-transition @click.away="open=false"
                            class="absolute w-full bg-white border mt-2 rounded shadow max-h-60 overflow-y-auto z-20">

                            <template x-for="hospital in hospitals" :key="hospital.id">
                                <button type="button"
                                    @click="toggleHospital(hospital.id)"
                                    class="w-full px-4 py-2 flex justify-between items-center hover:bg-gray-100 border-b">
                                    <span x-text="hospital.name"></span>
                                    <template x-if="isSelected(hospital.id)">
                                        <i class="fa-solid fa-check text-blue-500"></i>
                                    </template>
                                </button>
                            </template>
                        </div>

                        @error('hospital_ids')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Mobile Number -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile Number</label>
                        <input type="text" wire:model="mobile_number"
                               class="w-full px-4 py-2 rounded border"
                               placeholder="Enter mobile number">
                        @error('mobile_number')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Working Since -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Working since</label>
                        <input type="text" wire:model="working_since"
                               class="w-full px-4 py-2 rounded border"
                               placeholder="Enter Year eg 2015">
                        @error('working_since')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" wire:model="email"
                               class="w-full px-4 py-2 rounded border"
                               placeholder="doctor.name@hospital.com">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Qualification -->

                    <div
                        x-data="pillbox({
                            options: @js($qualificationOptions),
                            selected: @entangle('qualifications')
                        })"
                        class="relative" >
                    
                        <label class="block text-sm font-medium mb-1">Qualifications</label>
                    
                        <!-- Pills Input -->
                        <div
                            class="min-h-[44px] flex flex-wrap items-center gap-2 px-3 py-2 border rounded-lg bg-white mb-3" >
                            <template x-for="id in selected" :key="id">
                                <span
                                    class="flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs" >
                                    <span x-text="getLabel(id)"></span>
                                
                                    <button
                                        type="button"
                                        class="ml-1 hover:text-red-600"
                                        @click="remove(id)" >
                                        ✕
                                    </button>
                                </span>
                            </template>
                    
                            <span x-show="selected.length === 0" class="text-gray-400 text-sm">
                                Select qualifications...
                            </span>
                        </div>
                    
                        <!-- Checkbox List -->
                        <div class="border rounded-lg h-48 overflow-y-auto p-3 space-y-2">
                            <template x-for="option in options" :key="option.id">
                                <label class="flex items-center gap-3 text-sm cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :value="String(option.id)"
                                        x-model="selected"
                                    >
                                    <span x-text="option.name"></span>
                                </label>
                            </template>
                    
                            <template x-if="options.length === 0">
                                <p class="text-sm text-gray-500 text-center">
                                    No qualifications found
                                </p>
                            </template>
                        </div>
                    
                        @error('qualifications')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <!-- RIGHT COLUMN -->
                <div class="space-y-6">

                    <!-- Publications -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Publications</label>
                        <input type="text" wire:model="publications"
                               class="w-full px-4 py-2 rounded border"
                               placeholder="Title - Journal - Year">
                        @error('publications')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Achievements -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Achievements</label>
                        <input type="text" wire:model="achievements"
                               class="w-full px-4 py-2 rounded border"
                               placeholder="Award name/ Fellowship">
                        @error('achievements')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- IMAGE UPLOAD (ADVANCED) -->
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
                                
                                // Dispatch event to parent form
                                window.dispatchEvent(new CustomEvent('image-uploading', { 
                                    detail: { uploading: true } 
                                }));
                                
                                const input = event.target;
                                
                                @this.upload('doctor_image', input.files[0], 
                                    (uploadedFilename) => {
                                        this.isUploading = false;
                                        this.uploadProgress = 100;
                                        window.dispatchEvent(new CustomEvent('image-uploading', { 
                                            detail: { uploading: false } 
                                        }));
                                    },
                                    (error) => {
                                        this.isUploading = false;
                                        this.uploadProgress = 0;
                                        window.dispatchEvent(new CustomEvent('image-uploading', { 
                                            detail: { uploading: false } 
                                        }));
                                        console.error('Upload error:', error);
                                        alert('File upload failed. Please check the file size and type.');
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
                            this.showExisting = !!this.$wire.old_doctor_image && !this.$wire.remove_image;
                            const fileInput = document.getElementById('doctorImageEdit');
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
                            // Initialize showExisting based on old_doctor_image
                            // Use $nextTick to ensure Livewire values are available
                            this.$nextTick(() => {
                                this.showExisting = !!this.$wire.old_doctor_image && !this.$wire.remove_image && !this.previewUrl;
                            });
                            
                            this.$watch('$wire.old_doctor_image', (value) => {
                                if (value && !this.$wire.remove_image && !this.previewUrl) {
                                    this.showExisting = true;
                                } else if (!value) {
                                    this.showExisting = false;
                                }
                            });
                            
                            this.$watch('$wire.doctor_image', (value) => {
                                if (!value && this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                    this.previewUrl = null;
                                    const fileInput = document.getElementById('doctorImageEdit');
                                    if (fileInput) fileInput.value = '';
                                }
                                // If doctor_image is cleared and we have old image, show existing
                                if (!value && this.$wire.old_doctor_image && !this.$wire.remove_image) {
                                    this.showExisting = true;
                                }
                            });
                            
                            this.$watch('$wire.remove_image', (value) => {
                                if (!value && this.$wire.old_doctor_image && !this.previewUrl) {
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
                                this.showExisting = !!this.$wire.old_doctor_image && !this.$wire.remove_image;
                                const fileInput = document.getElementById('doctorImageEdit');
                                if (fileInput) fileInput.value = '';
                            });
                        }
                    }">
                        <label class="block text-sm font-medium mb-2">Doctor Image</label>

                        <!-- Existing Image Display -->
                        <div x-show="showExisting && !previewUrl && $wire.old_doctor_image && !$wire.remove_image">
                            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                                <img src="{{ $old_doctor_image ? asset('storage/doctor/' . $old_doctor_image) : '' }}" 
                                    alt="Current doctor image" 
                                    class="preview-img">
                                <button type="button"
                                    @click.stop="removeExisting()"
                                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                    rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                    title="Remove image">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="button"
                                @click="document.getElementById('doctorImageEdit').click()"
                                class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                <i class="fas fa-edit mr-2"></i>Change Image
                            </button>
                        </div>

                        <!-- Image Removal Message -->
                        <div x-show="$wire.remove_image && !$wire.doctor_image && !previewUrl" 
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
                        <div @click="document.getElementById('doctorImageEdit').click()"
                            class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                            x-show="!previewUrl && !$wire.doctor_image && !showExisting && !$wire.remove_image">

                            <div class="text-center p-4">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-700">Click to upload</p>
                                <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                            </div>

                            <input type="file" 
                                id="doctorImageEdit" 
                                class="hidden" 
                                accept="image/*"
                                @change="handleFileChange($event)">
                        </div>

                        @error('doctor_image')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror

                        <!-- New Image Preview -->
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                            x-show="previewUrl"
                            x-cloak>
                            <img :src="previewUrl" class="preview-img" alt="New preview">
                            
                            <!-- Upload Progress Overlay -->
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


                    <!-- SPECIALTIES -->
                  
                    <div
                        x-data="pillbox({
                            options: @js($speciality_data),
                            selected: @entangle('speciality')
                        })"
                        class="relative" >
                    
                        <label class="block text-sm font-medium mb-1">Specialties</label>
                    
                        <!-- Pills Input -->
                        <div
                            class="min-h-[44px] flex flex-wrap items-center gap-2 px-3 py-2 border rounded-lg bg-white mb-3" >
                            <template x-for="id in selected" :key="id">
                                <span
                                    class="flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs" >
                                    <span x-text="getLabel(id)"></span>
                                
                                    <button
                                        type="button"
                                        class="ml-1 hover:text-red-600"
                                        @click="remove(id)" >
                                        ✕
                                    </button>
                                </span>
                            </template>
                    
                            <span x-show="selected.length === 0" class="text-gray-400 text-sm">
                                Select specialties...
                            </span>
                        </div>
                    
                        <!-- Checkbox List -->
                        <div class="border rounded-lg h-48 overflow-y-auto p-3 space-y-2">
                            <template x-for="option in options" :key="option.id">
                                <label class="flex items-center gap-3 text-sm cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :value="String(option.id)"
                                        x-model="selected"
                                    >
                                    <span x-text="option.name"></span>
                                </label>
                            </template>
                    
                            <template x-if="options.length === 0">
                                <p class="text-sm text-gray-500 text-center">
                                    No specialties found
                                </p>
                            </template>
                        </div>
                    
                        @error('speciality')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- About Doctor -->
                    <div>
                        <label class="block text-sm font-medium mb-1">About Doctor</label>
                        <textarea wire:model="about_doctor" class="w-full px-4 py-2 rounded border" placeholder="Enter about doctor"></textarea>
                        @error('about_doctor')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <div class="pt-2">
                        <label class="block text-sm font-medium mb-2">Status</label>

                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700">Inactive</span>

                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="status" class="sr-only">
                                <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all duration-300 
                                    {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                    <span class="dot w-5 h-5 bg-white rounded-full transition-all duration-300 shadow-sm
                                        {{ $status ? 'translate-x-6' : 'translate-x-0' }}">
                                    </span>
                                </span>
                            </label>

                            <span class="text-sm text-gray-800 font-medium">Active</span>
                        </div>
                    </div>

                </div>

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
                                     style="background:#0da2e7; color:#ffffff !important;"
                                     x-bind:disabled="imageUploading"
                                     x-bind:class="{ 'opacity-50 cursor-not-allowed': imageUploading }">
                            <i class="fa-solid fa-spinner fa-spin mr-2" style="color:#ffffff !important;" x-show="imageUploading"></i>
                            <i class="fa-solid fa-check mr-2" style="color:#ffffff !important;" x-show="!imageUploading"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update Doctor'"></span>
                            <span class="sm:hidden" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update'"></span>
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

    function pillbox({ options, selected }) {
        return {
            options: options,
            selected: selected,

            init() {
                console.log('=== PILLBOX INIT ===');
                console.log('Initial selected:', this.selected);
                
                // Ensure selected is always an array of strings
                if (!Array.isArray(this.selected)) {
                    this.selected = [];
                }
                this.selected = this.selected.map(String);
                
                console.log('Normalized selected:', this.selected);
            },

            toggleOption(id, checked) {
                id = String(id);
                
                console.log('=== TOGGLE ===', { id, checked, before: [...this.selected] });

                if (checked) {
                    // Add only if not already present
                    if (!this.selected.includes(id)) {
                        this.selected = [...this.selected, id];
                    }
                } else {
                    // Remove the item
                    this.selected = this.selected.filter(i => i !== id);
                }
                
                console.log('After toggle:', this.selected);
            },

            remove(id) {
                id = String(id);
                console.log('=== REMOVE ===', id);
                this.selected = this.selected.filter(i => i !== id);
            },

            getLabel(id) {
                id = String(id);
                const opt = this.options.find(o => String(o.id) === id);
                return opt ? opt.name : `Unknown (${id})`;
            }
        }
    }

    function genderSelect({ value }) {
        return {
            open: false,
            value: value,
            options: ["Male", "Female", "Other"],

            toggle() {
                this.open = !this.open;
                this.$nextTick(() => lucide.createIcons());
            },

            close() {
                this.open = false;
                this.$nextTick(() => lucide.createIcons());
            },

            select(item) {
                this.value = item;
                this.open = false;
                this.$wire.set('gender', item);
                this.$nextTick(() => lucide.createIcons());
            }
        }
    }

    function toggleDropdown(menuId, button) {
        if (button && button.hasAttribute('disabled')) return;

        const menu = document.getElementById(menuId);
        if (!menu) return;

        document.querySelectorAll('.filter-dropdown').forEach(el => {
            if (el.id !== menuId) el.classList.add('hidden');
        });

        menu.classList.toggle('hidden');
        setTimeout(() => lucide.createIcons(), 10);
    }

    function hospitalMultiSelect({ organizationId, hospitalIds, hospitals }) {
        return {
            open: false,
            organizationId,
            hospitalIds,
            hospitals,

            isSelected(id) {
                const target = Number(id);
                return (this.hospitalIds ?? []).some(h => Number(h) === target);
            },

            toggleDropdown() {
                if (this.organizationId && this.hospitals.length) {
                    this.open = !this.open;
                }
            },

            toggleHospital(id) {
                const target = Number(id);
                const current = (this.hospitalIds ?? []).map(Number);

                if (current.includes(target)) {
                    this.hospitalIds = current.filter(h => h !== target);
                } else {
                    this.hospitalIds = [...current, target];
                }

                this.$wire.set('hospital_ids', this.hospitalIds);
            },

            getDisplayText() {
                if (!this.organizationId) {
                    return 'No organization selected';
                }

                if (this.hospitalIds?.length) {
                    return this.hospitals
                        .filter(h => this.isSelected(h.id))
                        .map(h => h.name)
                        .join(', ');
                }

                return this.hospitals.length
                    ? 'Select Hospitals (optional)'
                    : 'No hospitals available';
            }
        }
    }

    function organizationSelect({ organizationId, organizations }) {
        return {
            open: false,
            organizationId: organizationId || null,
            organizations: organizations || [],

            init() {
                console.log('Organization Select Init:', {
                    organizationId: this.organizationId,
                    wireOrgId: this.$wire.organization_id
                });
                
                // Sync with Livewire on init
                this.$nextTick(() => {
                    if (this.$wire.organization_id) {
                        this.organizationId = this.$wire.organization_id;
                        console.log('Set organizationId from wire:', this.organizationId);
                    }
                });

                // Watch for changes from both directions
                this.$watch('organizationId', (value) => {
                    console.log('Alpine organizationId changed to:', value);
                });

                // Watch Livewire property
                this.$watch('$wire.organization_id', (value) => {
                    console.log('Livewire organization_id changed to:', value);
                    if (value && value !== this.organizationId) {
                        this.organizationId = value;
                    }
                });
            },

            toggle() {
                this.open = !this.open;
            },

            select(id) {
                console.log('Selected organization:', id);
                this.organizationId = id;
                this.open = false;
                this.$wire.set('organization_id', id);
            },

            getLabel() {
                console.log('Getting label for organizationId:', this.organizationId);
                
                if (!this.organizationId) {
                    return 'Select Organization';
                }

                const org = this.organizations.find(o => o.id == this.organizationId);
                console.log('Found organization:', org);
                
                return org ? org.name : 'Select Organization';
            }
        }
    }

    document.addEventListener('livewire:init', () => {
        Livewire.on('organization-selected', () => {
            document.getElementById('orgMenu')?.classList.add('hidden');
            setTimeout(() => lucide.createIcons(), 100);
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
    </script>
    @endscript
</div>
