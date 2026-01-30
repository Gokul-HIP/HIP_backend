<div class="p-10 max-w-1xl mx-auto">
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
    <!-- Header -->
    <div class="mb-6 flex items-center space-x-3">
        <div class="bg-blue-500 text-white p-3 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Hospital Onboarding</h1>
    </div>

    <!-- Alert Banner -->
    @if($progressPercentage >= 100)
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-semibold text-green-800">Hospital Profile Submitted for Review</h3>
                <p class="mt-1 text-sm text-green-700">
                    Your hospital profile has been submitted for review. Our team will review your profile and get back to you soon.
                </p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-semibold text-yellow-800">Complete Hospital Setup</h3>
                <p class="mt-1 text-sm text-yellow-700">
                    Your hospital profile is {{ $progressPercentage }}% complete. Complete all onboarding steps to submit for review and activate your hospital on our platform
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Progress Card -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Overall Progress</h2>
            <span class="text-2xl font-bold text-[#0DA2E7]">{{ $this->progressPercentage }}%</span>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
            <div class="bg-[#0DA2E7] h-3 rounded-full transition-all duration-500" style="width: {{ $this->progressPercentage }}%"></div>
        </div>

        <!-- Continue Button -->
        {{-- <div class="flex justify-end">
            <button 
                class="px-6 py-2.5 bg-[#0da2e7] hover:bg-[#0b8dc7] text-white rounded-lg font-medium transition-colors">
                Continue
            </button>
        </div> --}}
    </div>

    <!-- Step Indicator -->
    <div class="mb-6">
        <p class="text-sm text-gray-600 mb-3">Step 1 out of 5</p>
        <div class="flex items-center gap-4">
            <!-- Step 1 - Active -->
            @if($basic_details_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.basic_details') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.basic_details') }}">
                    <div class="w-12 h-12 rounded-full bg-[#0DA2E7] text-white flex items-center justify-center font-semibold text-base">
                    1
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-gray-300"></div>
            
            <!-- Step 2 -->
            @if($location_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_location') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_location') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    2
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-gray-300"></div>
            
            <!-- Step 3 -->
            @if($capacity_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_capacity') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_capacity') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    3
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-gray-300"></div>
            
            <!-- Step 4 -->
            @if($medical_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.medical_compliance') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.medical_compliance') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    4
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-gray-300"></div>
            
            <!-- Step 5 -->
            @if($contact_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.contact-details') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.contact-details') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    5
                    </div>
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Basic Hospital Details</h3>
 
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Organization Name -->
                <div>
                    <label for="hospital_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Hospital Name
                    </label>
                    <input 
                        type="text" 
                        id="hospital_name"
                        wire:model="hospital_name"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Registered name of the hospital / clinic"
                        readonly
                    >
                    @error('hospital_name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Sub Title -->
                <div>
                    <label for="hospital_subtitle" class="block text-sm font-medium text-gray-700 mb-2">
                        Sub Title<span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="hospital_subtitle"
                        wire:model="hospital_subtitle"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter Sub Title"
                    >
                    @error('hospital_subtitle') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Nature of Ownership -->
                <div>
                    <label for="ownership" class="block text-sm font-medium text-gray-700 mb-2">
                        Nature of Ownership<span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="ownership"
                        wire:model="ownership"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Individual / Partnership / Trust / Society / Compan"
                    >
                    @error('ownership') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Establishment Type -->
                <div>
                    <label for="establishment_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Establishment Type<span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="establishment_type"
                        wire:model="establishment_type"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Hospital / Clinic / Nursing Home / Diagnostic Cer"
                    >
                    @error('establishment_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- About hospital -->
                <div>
                    <label for="hospital_about" class="block text-sm font-medium text-gray-700 mb-2">
                        About Hospital<span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="hospital_about"
                        wire:model="hospital_about"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter About Hospital"
                    ></textarea>
                    @error('hospital_about') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Hospital Logo -->
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
                            
                            @this.upload('hospital_logo', input.files[0], 
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
                        const fileInput = document.getElementById('hospitalLogoEdit');
                        if (fileInput) fileInput.value = '';
                        // Clear the Livewire property
                        @this.set('hospital_logo', null);
                        // If there's an old image and we're not removing it, show existing
                        this.$nextTick(() => {
                            if (this.$wire.old_hospital_logo && !this.$wire.remove_image) {
                                this.showExisting = true;
                            } else {
                                this.showExisting = false;
                            }
                        });
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
                        // Initialize showExisting based on old_hospital_logo
                        // Use $nextTick to ensure Livewire values are available
                        this.$nextTick(() => {
                            this.showExisting = !!this.$wire.old_hospital_logo && !this.$wire.remove_image && !this.previewUrl;
                        });
                        
                        // Watch for changes to old_hospital_logo
                        this.$watch('$wire.old_hospital_logo', (value) => {
                            if (value && !this.$wire.remove_image && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (!value) {
                                this.showExisting = false;
                            }
                        });
                        
                        // Watch for changes to hospital_logo from Livewire
                        this.$watch('$wire.hospital_logo', (value) => {
                            if (!value && this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                                const fileInput = document.getElementById('hospitalLogoEdit');
                                if (fileInput) fileInput.value = '';
                            }
                            // If hospital_logo is cleared and we have old image, show existing
                            if (!value && this.$wire.old_hospital_logo && !this.$wire.remove_image) {
                                this.showExisting = true;
                            }
                        });
                        
                        // Watch for remove_image flag changes
                        this.$watch('$wire.remove_image', (value) => {
                            if (!value && this.$wire.old_hospital_logo && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (value) {
                                this.showExisting = false;
                            }
                        });
                        
                        // Listen for reset event from Livewire
                        Livewire.on('reset-file-input', () => {
                            if (this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                            }
                            this.previewUrl = null;
                            this.isUploading = false;
                            this.uploadProgress = 0;
                            this.showExisting = !!this.$wire.old_hospital_logo && !this.$wire.remove_image;
                            const fileInput = document.getElementById('hospitalLogoEdit');
                            if (fileInput) fileInput.value = '';
                        });
                    }
                }">
                    <label class="block text-sm font-medium mb-2">Hospital Logo/Image</label>
                
                    <!-- Existing Image Display -->
                    <div x-show="showExisting && !previewUrl && $wire.old_hospital_logo && !$wire.remove_image">
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            <img src="{{ $old_hospital_logo ? asset('storage/hospital/' . $old_hospital_logo) : '' }}" 
                                 alt="Current hospital image" 
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
                            @click="document.getElementById('hospitalLogoEdit').click()"
                            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change Image
                        </button>
                    </div>
                
                    <!-- Image Removal Message -->
                    <div x-show="$wire.remove_image && !$wire.hospital_logo && !previewUrl && $wire.old_hospital_logo" 
                         class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>Image will be removed
                        </p>
                        <div class="flex gap-2">
                            <button type="button"
                                @click="restoreExisting()"
                                class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-undo mr-1"></i>Cancel Removal
                            </button>
                            <button type="button"
                                @click="document.getElementById('hospitalLogoEdit').click()"
                                class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition">
                                <i class="fas fa-edit mr-1"></i>Change Image
                            </button>
                        </div>
                    </div>
                
                    <!-- Upload Box -->
                    <div @click="document.getElementById('hospitalLogoEdit').click()"
                        class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                               cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                        x-show="!previewUrl && !$wire.hospital_logo && !showExisting">
                
                        <div class="text-center p-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-700">Click to upload</p>
                            <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                        </div>
                
                        <input type="file" 
                               id="hospitalLogoEdit" 
                               class="hidden" 
                               accept="image/*"
                               @change="handleFileChange($event)">
                    </div>
                
                    @error('hospital_logo')
                        <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                
                    <!-- New Image Preview -->
                    <div x-show="previewUrl" x-cloak>
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
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
                        <button type="button"
                            @click="document.getElementById('hospitalLogoEdit').click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change Image
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end items-center pt-6 mt-6 border-t">
                <button 
                    type="submit"
                    class="px-8 py-2.5 bg-gray-400 hover:bg-gray-500 text-white rounded-lg font-medium transition-colors"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Next</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>