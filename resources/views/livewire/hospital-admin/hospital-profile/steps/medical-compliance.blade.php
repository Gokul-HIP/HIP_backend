<div class="p-10 mx-auto">
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

    <!-- Progress Card -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Overall Progress</h2>
            <span class="text-2xl font-bold text-[#0DA2E7]">{{ $progressPercentage }}%</span>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
            <div class="bg-[#0DA2E7] h-3 rounded-full transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
        </div>

        <!-- Continue Button -->
        <div class="flex justify-end">
            <button 
                class="px-6 py-2.5 bg-[#0da2e7] hover:bg-[#0b8dc7] text-white rounded-lg font-medium transition-colors">
                Continue
            </button>
        </div>
    </div>

    <!-- Step Indicator -->
    <div class="mb-6">
        <p class="text-sm text-gray-600 mb-3">Step 4 out of 5</p>
        <div class="flex items-center gap-4">
            <!-- Step 1 - Completed -->
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
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    1
                    </div>
                </a>
            </div>
            @endif

            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-[#0da2e7]"></div>
            
            <!-- Step 2 - Completed -->
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
            <div class="flex-1 h-0.5 bg-[#0da2e7]"></div>
            
            <!-- Step 3 - Completed -->
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
            <div class="flex-1 h-0.5 bg-[#0da2e7]"></div>
            
            <!-- Step 4 - Active -->
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
                    <div class="w-12 h-12 rounded-full bg-[#0DA2E7] text-white flex items-center justify-center font-semibold text-base">
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
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    5
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
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Medical Compliance</h3>
        
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Hospital Registration Certificate -->
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
                            
                            const input = event.target;
                            
                            @this.upload('registration_certificate', input.files[0], 
                                (uploadedFilename) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 0;
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
                        const fileInput = document.getElementById('registrationCertificateInput');
                        if (fileInput) fileInput.value = '';
                        // Clear the Livewire property
                        @this.set('registration_certificate', null);
                        // If there's an old file and we're not removing it, show existing
                        this.$nextTick(() => {
                            if (this.$wire.old_registration_certificate && !this.$wire.remove_registration_certificate) {
                                this.showExisting = true;
                            } else {
                                this.showExisting = false;
                            }
                        });
                    },
                    
                    removeExisting() {
                        this.showExisting = false;
                        @this.removeRegistrationCertificate();
                    },
                    
                    restoreExisting() {
                        this.showExisting = true;
                        @this.restoreRegistrationCertificate();
                    },
                    
                    init() {
                        this.$nextTick(() => {
                            this.showExisting = !!this.$wire.old_registration_certificate && !this.$wire.remove_registration_certificate && !this.previewUrl;
                        });
                        
                        this.$watch('$wire.old_registration_certificate', (value) => {
                            if (value && !this.$wire.remove_registration_certificate && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (!value) {
                                this.showExisting = false;
                            }
                        });
                        
                        this.$watch('$wire.registration_certificate', (value) => {
                            if (!value && this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                                const fileInput = document.getElementById('registrationCertificateInput');
                                if (fileInput) fileInput.value = '';
                            }
                            if (!value && this.$wire.old_registration_certificate && !this.$wire.remove_registration_certificate) {
                                this.showExisting = true;
                            }
                        });
                        
                        this.$watch('$wire.remove_registration_certificate', (value) => {
                            if (!value && this.$wire.old_registration_certificate && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (value) {
                                this.showExisting = false;
                            }
                        });
                    }
                }">
                    <label class="block text-sm font-medium mb-2">Hospital Registration Certificate<span class="text-red-500">*</span></label>
                
                    <!-- Existing File Display -->
                    <div x-show="showExisting && !previewUrl && $wire.old_registration_certificate && !$wire.remove_registration_certificate">
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            @if($old_registration_certificate)
                                @if(pathinfo($old_registration_certificate, PATHINFO_EXTENSION) === 'pdf')
                                    <div class="flex items-center justify-center h-full">
                                        <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                                    </div>
                                @else
                                    <img src="{{ asset('storage/hospital/documents/' . $old_registration_certificate) }}" 
                                         alt="Current certificate" 
                                 class="preview-img">
                                @endif
                            @endif
                            <button type="button"
                                @click.stop="removeExisting()"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button type="button"
                            @click="document.getElementById('registrationCertificateInput').click()"
                            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                    </div>
                
                    <!-- File Removal Message -->
                    <div x-show="$wire.remove_registration_certificate && !$wire.registration_certificate && !previewUrl && $wire.old_registration_certificate" 
                         class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>File will be removed
                        </p>
                        <div class="flex gap-2">
                            <button type="button"
                                @click="restoreExisting()"
                                class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-undo mr-1"></i>Cancel Removal
                            </button>
                            <button type="button"
                                @click="document.getElementById('registrationCertificateInput').click()"
                                class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition">
                                <i class="fas fa-edit mr-1"></i>Change File
                            </button>
                        </div>
                    </div>
                
                    <!-- Upload Box -->
                    <div @click="document.getElementById('registrationCertificateInput').click()"
                        class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                               cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                        x-show="!previewUrl && !$wire.registration_certificate && !showExisting">
                
                        <div class="text-center p-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-700">Click to upload</p>
                            <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG up to 5MB</p>
                        </div>
                
                        <input type="file" 
                               id="registrationCertificateInput" 
                               class="hidden" 
                               accept=".pdf,.jpg,.jpeg,.png"
                               @change="handleFileChange($event)">
                    </div>
                
                    @error('registration_certificate')
                        <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                
                    <!-- New File Preview -->
                    <div x-show="previewUrl" x-cloak>
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            <img :src="previewUrl" class="preview-img" alt="New preview" x-show="previewUrl && previewUrl.includes('blob:')">
                            <div x-show="previewUrl && !previewUrl.includes('blob:')" class="flex items-center justify-center h-full">
                                <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                            
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
                            @click="document.getElementById('registrationCertificateInput').click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                    </div>
                </div>

                <!-- Ownership Proof -->
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
                            
                            const input = event.target;
                            
                            @this.upload('ownership_proof', input.files[0], 
                                (uploadedFilename) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 0;
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
                        const fileInput = document.getElementById('ownershipProofInput');
                            if (fileInput) fileInput.value = '';
                        // Clear the Livewire property
                        @this.set('ownership_proof', null);
                        // If there's an old file and we're not removing it, show existing
                        this.$nextTick(() => {
                            if (this.$wire.old_ownership_proof && !this.$wire.remove_ownership_proof) {
                                this.showExisting = true;
                            } else {
                                this.showExisting = false;
                            }
                        });
                    },
                    
                    removeExisting() {
                        this.showExisting = false;
                        @this.removeOwnershipProof();
                    },
                    
                    restoreExisting() {
                        this.showExisting = true;
                        @this.restoreOwnershipProof();
                    },
                    
                    init() {
                        this.$nextTick(() => {
                            this.showExisting = !!this.$wire.old_ownership_proof && !this.$wire.remove_ownership_proof && !this.previewUrl;
                        });
                        
                        this.$watch('$wire.old_ownership_proof', (value) => {
                            if (value && !this.$wire.remove_ownership_proof && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (!value) {
                                this.showExisting = false;
                            }
                        });
                        
                        this.$watch('$wire.ownership_proof', (value) => {
                            if (!value && this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                                const fileInput = document.getElementById('ownershipProofInput');
                                if (fileInput) fileInput.value = '';
                            }
                            if (!value && this.$wire.old_ownership_proof && !this.$wire.remove_ownership_proof) {
                                this.showExisting = true;
                            }
                        });
                        
                        this.$watch('$wire.remove_ownership_proof', (value) => {
                            if (!value && this.$wire.old_ownership_proof && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (value) {
                                this.showExisting = false;
                            }
                        });
                    }
                }">
                    <label class="block text-sm font-medium mb-2">Ownership Proof<span class="text-red-500">*</span></label>
                
                    <!-- Existing File Display -->
                    <div x-show="showExisting && !previewUrl && $wire.old_ownership_proof && !$wire.remove_ownership_proof">
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            @if($old_ownership_proof)
                                @if(pathinfo($old_ownership_proof, PATHINFO_EXTENSION) === 'pdf')
                                    <div class="flex items-center justify-center h-full">
                                        <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                                    </div>
                                @else
                                    <img src="{{ asset('storage/hospital/documents/' . $old_ownership_proof) }}" 
                                         alt="Current file" 
                                 class="preview-img">
                                @endif
                            @endif
                            <button type="button"
                                @click.stop="removeExisting()"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button type="button"
                            @click="document.getElementById('ownershipProofInput').click()"
                            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                    </div>
                
                    <!-- File Removal Message -->
                    <div x-show="$wire.remove_ownership_proof && !$wire.ownership_proof && !previewUrl && $wire.old_ownership_proof" 
                         class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>File will be removed
                        </p>
                        <div class="flex gap-2">
                        <button type="button"
                            @click="restoreExisting()"
                            class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                            <i class="fas fa-undo mr-1"></i>Cancel Removal
                        </button>
                            <button type="button"
                                @click="document.getElementById('ownershipProofInput').click()"
                                class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition">
                                <i class="fas fa-edit mr-1"></i>Change File
                            </button>
                        </div>
                    </div>
                
                    <!-- Upload Box -->
                    <div @click="document.getElementById('ownershipProofInput').click()"
                        class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                               cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                        x-show="!previewUrl && !$wire.ownership_proof && !showExisting">
                
                        <div class="text-center p-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-700">Click to upload</p>
                            <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG up to 5MB</p>
                        </div>
                
                        <input type="file" 
                               id="ownershipProofInput" 
                               class="hidden" 
                            accept=".pdf,.jpg,.jpeg,.png"
                               @change="handleFileChange($event)">
                    </div>
                
                    @error('ownership_proof')
                        <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                
                    <!-- New File Preview -->
                    <div x-show="previewUrl" x-cloak>
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            <img :src="previewUrl" class="preview-img" alt="New preview" x-show="previewUrl && previewUrl.includes('blob:')">
                            <div x-show="previewUrl && !previewUrl.includes('blob:')" class="flex items-center justify-center h-full">
                                <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                        
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
                            @click="document.getElementById('ownershipProofInput').click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>   
                    </div>
                </div>

                <!-- Accreditation Certificate (NABH / ISO / None) -->
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
                            
                            const input = event.target;
                            
                            @this.upload('accreditation_certificate', input.files[0], 
                                (uploadedFilename) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 0;
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
                        const fileInput = document.getElementById('accreditationCertificateInput');
                        if (fileInput) fileInput.value = '';
                        // Clear the Livewire property
                        @this.set('accreditation_certificate', null);
                        // If there's an old file and we're not removing it, show existing
                        this.$nextTick(() => {
                            if (this.$wire.old_accreditation_certificate && !this.$wire.remove_accreditation_certificate) {
                                this.showExisting = true;
                            } else {
                                this.showExisting = false;
                            }
                        });
                    },
                    
                    removeExisting() {
                        this.showExisting = false;
                        @this.removeAccreditationCertificate();
                    },
                    
                    restoreExisting() {
                        this.showExisting = true;
                        @this.restoreAccreditationCertificate();
                    },
                    
                    init() {
                        this.$nextTick(() => {
                            this.showExisting = !!this.$wire.old_accreditation_certificate && !this.$wire.remove_accreditation_certificate && !this.previewUrl;
                        });
                        
                        this.$watch('$wire.old_accreditation_certificate', (value) => {
                            if (value && !this.$wire.remove_accreditation_certificate && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (!value) {
                                this.showExisting = false;
                            }
                        });
                        
                        this.$watch('$wire.accreditation_certificate', (value) => {
                            if (!value && this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                                const fileInput = document.getElementById('accreditationCertificateInput');
                                if (fileInput) fileInput.value = '';
                            }
                            if (!value && this.$wire.old_accreditation_certificate && !this.$wire.remove_accreditation_certificate) {
                                this.showExisting = true;
                            }
                        });
                        
                        this.$watch('$wire.remove_accreditation_certificate', (value) => {
                            if (!value && this.$wire.old_accreditation_certificate && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (value) {
                                this.showExisting = false;
                            }
                        });
                    }
                }">
                    <label class="block text-sm font-medium mb-2">Accreditation Certificate<span class="text-red-500">*</span> (NABH / ISO / None)</label>
                
                    <!-- Existing File Display -->
                    <div x-show="showExisting && !previewUrl && $wire.old_accreditation_certificate && !$wire.remove_accreditation_certificate">
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            @if($old_accreditation_certificate)
                                @if(pathinfo($old_accreditation_certificate, PATHINFO_EXTENSION) === 'pdf')
                                    <div class="flex items-center justify-center h-full">
                                        <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                        @else
                                    <img src="{{ asset('storage/hospital/documents/' . $old_accreditation_certificate) }}" 
                                         alt="Current certificate" 
                                         class="preview-img">
                        @endif
                            @endif
                            <button type="button"
                                @click.stop="removeExisting()"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                            </div>
                        <button type="button"
                            @click="document.getElementById('accreditationCertificateInput').click()"
                            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                        </div>
                
                    <!-- File Removal Message -->
                    <div x-show="$wire.remove_accreditation_certificate && !$wire.accreditation_certificate && !previewUrl && $wire.old_accreditation_certificate" 
                         class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>File will be removed
                        </p>
                        <div class="flex gap-2">
                            <button type="button"
                                @click="restoreExisting()"
                                class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-undo mr-1"></i>Cancel Removal
                            </button>
                            <button type="button"
                                @click="document.getElementById('accreditationCertificateInput').click()"
                                class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition">
                                <i class="fas fa-edit mr-1"></i>Change File
                            </button>
                    </div>
                </div>

                    <!-- Upload Box -->
                    <div @click="document.getElementById('accreditationCertificateInput').click()"
                        class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                               cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                        x-show="!previewUrl && !$wire.accreditation_certificate && !showExisting">
                
                        <div class="text-center p-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-700">Click to upload</p>
                            <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG up to 5MB</p>
                            </div>
                
                        <input type="file" 
                               id="accreditationCertificateInput" 
                            class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png"
                               @change="handleFileChange($event)">
                    </div>
                
                    @error('accreditation_certificate')
                        <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                
                    <!-- New File Preview -->
                    <div x-show="previewUrl" x-cloak>
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            <img :src="previewUrl" class="preview-img" alt="New preview" x-show="previewUrl && previewUrl.includes('blob:')">
                            <div x-show="previewUrl && !previewUrl.includes('blob:')" class="flex items-center justify-center h-full">
                                <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                            
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
                            @click="document.getElementById('accreditationCertificateInput').click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                            </div>
                </div>

                <!-- Fire Safety Certificate -->
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
                            
                            const input = event.target;
                            
                            @this.upload('fire_safety_certificate', input.files[0], 
                                (uploadedFilename) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 0;
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
                        const fileInput = document.getElementById('fireSafetyCertificateInput');
                        if (fileInput) fileInput.value = '';
                        // Clear the Livewire property
                        @this.set('fire_safety_certificate', null);
                        // If there's an old file and we're not removing it, show existing
                        this.$nextTick(() => {
                            if (this.$wire.old_fire_safety_certificate && !this.$wire.remove_fire_safety_certificate) {
                                this.showExisting = true;
                            } else {
                                this.showExisting = false;
                            }
                        });
                    },
                    
                    removeExisting() {
                        this.showExisting = false;
                        @this.removeFireSafetyCertificate();
                    },
                    
                    restoreExisting() {
                        this.showExisting = true;
                        @this.restoreFireSafetyCertificate();
                    },
                    
                    init() {
                        this.$nextTick(() => {
                            this.showExisting = !!this.$wire.old_fire_safety_certificate && !this.$wire.remove_fire_safety_certificate && !this.previewUrl;
                        });
                        
                        this.$watch('$wire.old_fire_safety_certificate', (value) => {
                            if (value && !this.$wire.remove_fire_safety_certificate && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (!value) {
                                this.showExisting = false;
                            }
                        });
                        
                        this.$watch('$wire.fire_safety_certificate', (value) => {
                            if (!value && this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                                const fileInput = document.getElementById('fireSafetyCertificateInput');
                                if (fileInput) fileInput.value = '';
                            }
                            if (!value && this.$wire.old_fire_safety_certificate && !this.$wire.remove_fire_safety_certificate) {
                                this.showExisting = true;
                            }
                        });
                        
                        this.$watch('$wire.remove_fire_safety_certificate', (value) => {
                            if (!value && this.$wire.old_fire_safety_certificate && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (value) {
                                this.showExisting = false;
                            }
                        });
                    }
                }">
                    <label class="block text-sm font-medium mb-2">Fire Safety Certificate<span class="text-red-500">*</span></label>
                
                    <!-- Existing File Display -->
                    <div x-show="showExisting && !previewUrl && $wire.old_fire_safety_certificate && !$wire.remove_fire_safety_certificate">
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            @if($old_fire_safety_certificate)
                                @if(pathinfo($old_fire_safety_certificate, PATHINFO_EXTENSION) === 'pdf')
                                    <div class="flex items-center justify-center h-full">
                                        <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                        @else
                                    <img src="{{ asset('storage/hospital/documents/' . $old_fire_safety_certificate) }}" 
                                         alt="Current certificate" 
                                         class="preview-img">
                        @endif
                            @endif
                            <button type="button"
                                @click.stop="removeExisting()"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button type="button"
                            @click="document.getElementById('fireSafetyCertificateInput').click()"
                            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                    </div>
                
                    <!-- File Removal Message -->
                    <div x-show="$wire.remove_fire_safety_certificate && !$wire.fire_safety_certificate && !previewUrl && $wire.old_fire_safety_certificate" 
                         class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>File will be removed
                        </p>
                        <div class="flex gap-2">
                            <button type="button"
                                @click="restoreExisting()"
                                class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-undo mr-1"></i>Cancel Removal
                            </button>
                            <button type="button"
                                @click="document.getElementById('fireSafetyCertificateInput').click()"
                                class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition">
                                <i class="fas fa-edit mr-1"></i>Change File
                            </button>
                        </div>
                    </div>
                
                    <!-- Upload Box -->
                    <div @click="document.getElementById('fireSafetyCertificateInput').click()"
                        class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                               cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                        x-show="!previewUrl && !$wire.fire_safety_certificate && !showExisting">
                
                        <div class="text-center p-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-700">Click to upload</p>
                            <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG up to 5MB</p>
                            </div>
                
                        <input type="file" 
                               id="fireSafetyCertificateInput" 
                            class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png"
                               @change="handleFileChange($event)">
                    </div>
                
                    @error('fire_safety_certificate')
                        <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                
                    <!-- New File Preview -->
                    <div x-show="previewUrl" x-cloak>
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            <img :src="previewUrl" class="preview-img" alt="New preview" x-show="previewUrl && previewUrl.includes('blob:')">
                            <div x-show="previewUrl && !previewUrl.includes('blob:')" class="flex items-center justify-center h-full">
                                <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                            
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
                            @click="document.getElementById('fireSafetyCertificateInput').click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                    </div>
                </div>
            </div>

            <!-- Full Width Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Hospital Insurance Policy Number (Optional) -->
                <div>
                    <label for="insurance_policy_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Hospital Insurance Policy Number (Optional)
                    </label>
                    <input 
                        type="text" 
                        id="insurance_policy_number"
                        wire:model="insurance_policy_number"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder=""
                    >
                    @error('insurance_policy_number') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Ownership Proof Upload (Trust deed / Partnership deed / Incorporation certificate) -->
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
                            
                            const input = event.target;
                            
                            @this.upload('ownership_proof_doc', input.files[0], 
                                (uploadedFilename) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 0;
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
                        const fileInput = document.getElementById('ownershipProofDocInput');
                        if (fileInput) fileInput.value = '';
                        // Clear the Livewire property
                        @this.set('ownership_proof_doc', null);
                        // If there's an old file and we're not removing it, show existing
                        this.$nextTick(() => {
                            if (this.$wire.old_ownership_proof_doc && !this.$wire.remove_ownership_proof_doc) {
                                this.showExisting = true;
                            } else {
                                this.showExisting = false;
                            }
                        });
                    },
                    
                    removeExisting() {
                        this.showExisting = false;
                        @this.removeOwnershipProofDoc();
                    },
                    
                    restoreExisting() {
                        this.showExisting = true;
                        @this.restoreOwnershipProofDoc();
                    },
                    
                    init() {
                        this.$nextTick(() => {
                            this.showExisting = !!this.$wire.old_ownership_proof_doc && !this.$wire.remove_ownership_proof_doc && !this.previewUrl;
                        });
                        
                        this.$watch('$wire.old_ownership_proof_doc', (value) => {
                            if (value && !this.$wire.remove_ownership_proof_doc && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (!value) {
                                this.showExisting = false;
                            }
                        });
                        
                        this.$watch('$wire.ownership_proof_doc', (value) => {
                            if (!value && this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                                const fileInput = document.getElementById('ownershipProofDocInput');
                                if (fileInput) fileInput.value = '';
                            }
                            if (!value && this.$wire.old_ownership_proof_doc && !this.$wire.remove_ownership_proof_doc) {
                                this.showExisting = true;
                            }
                        });
                        
                        this.$watch('$wire.remove_ownership_proof_doc', (value) => {
                            if (!value && this.$wire.old_ownership_proof_doc && !this.previewUrl) {
                                this.showExisting = true;
                            } else if (value) {
                                this.showExisting = false;
                            }
                        });
                    }
                }">
                    <label class="block text-sm font-medium mb-2">Ownership Proof<span class="text-red-500">*</span> (Upload Trust deed / Partnership deed / Incorporation certificate)</label>
                
                    <!-- Existing File Display -->
                    <div x-show="showExisting && !previewUrl && $wire.old_ownership_proof_doc && !$wire.remove_ownership_proof_doc">
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            @if($old_ownership_proof_doc)
                                @if(pathinfo($old_ownership_proof_doc, PATHINFO_EXTENSION) === 'pdf')
                                    <div class="flex items-center justify-center h-full">
                                        <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                        @else
                                    <img src="{{ asset('storage/hospital/documents/' . $old_ownership_proof_doc) }}" 
                                         alt="Current file" 
                                         class="preview-img">
                        @endif
                            @endif
                            <button type="button"
                                @click.stop="removeExisting()"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button type="button"
                            @click="document.getElementById('ownershipProofDocInput').click()"
                            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                    </div>
                
                    <!-- File Removal Message -->
                    <div x-show="$wire.remove_ownership_proof_doc && !$wire.ownership_proof_doc && !previewUrl && $wire.old_ownership_proof_doc" 
                         class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>File will be removed
                        </p>
                        <div class="flex gap-2">
                            <button type="button"
                                @click="restoreExisting()"
                                class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-undo mr-1"></i>Cancel Removal
                            </button>
                            <button type="button"
                                @click="document.getElementById('ownershipProofDocInput').click()"
                                class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition">
                                <i class="fas fa-edit mr-1"></i>Change File
                            </button>
                        </div>
                    </div>
                
                    <!-- Upload Box -->
                    <div @click="document.getElementById('ownershipProofDocInput').click()"
                        class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                               cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                        x-show="!previewUrl && !$wire.ownership_proof_doc && !showExisting">
                
                        <div class="text-center p-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-700">Click to upload</p>
                            <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG up to 5MB</p>
                        </div>
                
                        <input type="file" 
                               id="ownershipProofDocInput" 
                            class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png"
                               @change="handleFileChange($event)">
                    </div>
                
                    @error('ownership_proof_doc')
                        <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                
                    <!-- New File Preview -->
                    <div x-show="previewUrl" x-cloak>
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                            <img :src="previewUrl" class="preview-img" alt="New preview" x-show="previewUrl && previewUrl.includes('blob:')">
                            <div x-show="previewUrl && !previewUrl.includes('blob:')" class="flex items-center justify-center h-full">
                                <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                            </div>
                            
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
                            @click="document.getElementById('ownershipProofDocInput').click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                            <i class="fas fa-edit mr-2"></i>Change File
                        </button>
                    </div>
                </div>

                <!-- Note -->
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600">* Upload valid and readable certificates</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-6 mt-6 border-t">
                <a 
                    href="{{ route('hospital.hospital-profile.hospital_capacity') }}"
                    class="px-8 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                    Back
                </a>
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