<div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">5. Legal Compliance</h3>
    
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Business Registration Type -->
            <div>
                <label for="business_registration_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Business Registration Type
                </label>
                <div
                    x-data="{
                        open:false,
                        top:0,
                        left:0,
                        width:0,
                        toggle(e){
                            const rect = e.target.closest('button').getBoundingClientRect();
                            this.top = rect.bottom + window.scrollY + 6;
                            this.left = rect.left + window.scrollX;
                            this.width = rect.width;
                            this.open = !this.open;
                        }
                    }"
                    class="w-full">
                    <button
                        type="button"
                        @click="toggle($event)"
                        class="w-full flex justify-between items-center border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50">
                        <span>{{ $business_registration_type ?: 'Select Registration Type' }}</span>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="m19 9-7 7-7-7"/>
                        </svg>
                    </button>
                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open=false"
                        :style="`top:${top}px; left:${left}px; width:${width}px`"
                        class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto"
                    >
                        <button
                            wire:click="$set('business_registration_type', '')"
                            @click="open=false"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                            {{ !$business_registration_type ? 'bg-blue-50 text-blue-600' : '' }}"
                        >
                            Select Registration Type
                        </button>
                        @foreach(['Sole Proprietorship', 'LLP', 'Partnership', 'Private Limited', 'Public Limited'] as $item)
                            <button
                                wire:click="$set('business_registration_type', '{{ $item }}')"
                                @click="open=false"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                                {{ $business_registration_type === $item ? 'bg-blue-50 text-blue-600' : '' }}"
                            >
                                {{ $item }}
                            </button>
                        @endforeach
                    </div>
                </div>
                @error('business_registration_type')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- GST Number -->
            <div>
                <label for="gst_number" class="block text-sm font-medium text-gray-700 mb-2">
                    GST Number
                </label>
                <input 
                    type="text"
                    id="gst_number"
                    wire:model="gst_number"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="GST Number">
                @error('gst_number')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        @php
            $fileFields = [
                'registration_certificate' => ['label' => 'Centre Registration Certificate', 'existing' => 'existing_registration_certificate'],
                'ownership_proof' => ['label' => 'Ownership Proof', 'existing' => 'existing_ownership_proof', 'note' => '(Upload Trust deed / Partnership deed / Incorporation certificate)'],
                'accreditation_certificate' => ['label' => 'Accreditation Certificate', 'existing' => 'existing_accreditation_certificate', 'note' => '(NABH / ISO / None)'],
                'fire_safety_certificate' => ['label' => 'Fire Safety Certificate', 'existing' => 'existing_fire_safety_certificate'],
            ];
        @endphp

        @foreach($fileFields as $fieldName => $fieldConfig)
        @php
            $existingFileValue = $this->{$fieldConfig['existing']};
            $existingFileUrl = $existingFileValue ? asset('storage/wellness-centers/documents/' . $existingFileValue) : null;
            $existingIsPdf = $existingFileValue && str_ends_with(strtolower($existingFileValue), '.pdf');
        @endphp
        <!-- {{ $fieldConfig['label'] }} -->
        <div x-data="{ 
            previewUrl: null,
            showExisting: {{ $existingFileValue ? 'true' : 'false' }},
            isUploading: false,
            uploadProgress: 0,
            isPdf: false,
            existingFile: @js($existingFileUrl),
            existingIsPdf: {{ $existingIsPdf ? 'true' : 'false' }},
            
            handleFileChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                    
                    if (this.previewUrl && this.previewUrl !== 'pdf' && this.previewUrl.startsWith('blob:')) {
                        URL.revokeObjectURL(this.previewUrl);
                    }
                    
                    if (!this.isPdf) {
                        this.previewUrl = URL.createObjectURL(file);
                    } else {
                        this.previewUrl = 'pdf';
                    }
                    
                    this.showExisting = false;
                    this.isUploading = true;
                    this.uploadProgress = 0;
                    
                    const input = event.target;
                    
                    @this.upload('{{ $fieldName }}', input.files[0], 
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
                if (this.previewUrl && this.previewUrl !== 'pdf' && this.previewUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(this.previewUrl);
                }
                this.previewUrl = null;
                this.isPdf = false;
                this.isUploading = false;
                this.uploadProgress = 0;
                this.showExisting = !!this.existingFile;
                const fileInput = document.getElementById('{{ $fieldName }}Input');
                if (fileInput) fileInput.value = '';
                @this.set('{{ $fieldName }}', null);
            }
        }">
            <label class="block text-sm font-medium mb-2">
                {{ $fieldConfig['label'] }}
                @if(isset($fieldConfig['note']))
                    <span class="text-gray-500 text-xs">{{ $fieldConfig['note'] }}</span>
                @endif
            </label>
        
            <!-- Upload Box -->
            <div @click="document.getElementById('{{ $fieldName }}Input').click()"
                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                       cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                x-show="!previewUrl && !showExisting && !$wire.{{ $fieldName }}">
                <div class="text-center p-4">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                    <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG up to 5MB</p>
                </div>
                <input type="file" 
                       id="{{ $fieldName }}Input" 
                       class="hidden" 
                       accept=".pdf,.jpg,.jpeg,.png"
                       @change="handleFileChange($event)">
            </div>
        
            @error($fieldName)
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        
            <!-- Existing File Preview -->
            <div x-show="showExisting && existingFile && !previewUrl" x-cloak>
                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                    @if($existingFileValue && !$existingIsPdf)
                        <img src="{{ $existingFileUrl }}" 
                             alt="Existing File" 
                             class="preview-img"
                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="preview-img flex items-center justify-center bg-gray-100 text-gray-400" style="display: none;">
                            <span>Image not found</span>
                        </div>
                    @elseif($existingFileValue && $existingIsPdf)
                        <div class="flex items-center justify-center h-full min-h-[200px]">
                            <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                        </div>
                    @endif
                    <button type="button"
                        @click.stop="showExisting = false; document.getElementById('{{ $fieldName }}Input').click()"
                        class="absolute top-2 right-2 bg-blue-600 text-white w-8 h-8 flex items-center justify-center 
                            rounded-full text-sm font-bold shadow hover:bg-blue-700 transition">
                        <i class="fas fa-edit"></i>
                    </button>   
                </div>
                <p class="text-xs text-gray-500 mb-2">Current file</p>
            </div>
        
            <!-- New File Preview -->
            <div x-show="previewUrl" x-cloak>
                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                    <img :src="previewUrl" class="preview-img" alt="Preview" x-show="previewUrl && !isPdf">
                    <div x-show="previewUrl && isPdf" class="flex items-center justify-center h-full">
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
                    @click="document.getElementById('{{ $fieldName }}Input').click()"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                    <i class="fas fa-edit mr-2"></i>Change File
                </button>
            </div>
        </div>
        @endforeach

        <!-- Insurance Coverage -->
        <div>
            <label for="insurance_coverage" class="block text-sm font-medium text-gray-700 mb-2">
                Insurance Coverage
            </label>
            <div
                x-data="{
                    open:false,
                    bottom:0,
                    left:0,
                    width:0,
                    toggle(e){
                        const rect = e.target.closest('button').getBoundingClientRect();
                        // Always open upward - position dropdown above the button
                        this.bottom = window.innerHeight - rect.top + 6;
                        this.left = rect.left;
                        this.width = rect.width;
                        this.open = !this.open;
                    }
                }"
                class="w-full">
                <button
                    type="button"
                    @click="toggle($event)"
                    class="w-full flex justify-between items-center border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50">
                    <span>{{ $insurance_coverage ?: 'Select from Dropdown' }}</span>
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="m19 9-7 7-7-7"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition
                    @click.outside="open=false"
                    :style="`bottom:${bottom}px; left:${left}px; width:${width}px`"
                    class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-xl"
                    style="max-height: 280px; overflow-y: auto; scrollbar-width: thin;"
                >
                    <button
                        wire:click="$set('insurance_coverage', '')"
                        @click="open=false"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                        {{ !$insurance_coverage ? 'bg-blue-50 text-blue-600' : '' }}"
                    >
                        Select from Dropdown
                    </button>
                    @foreach(['Public Liability Insurance', 'Professional Liability Insurance', 'Property Insurance', 'Equipment Insurance', 'Employee / Workers Insurance', 'Cyber & Data Insurance', 'Product Liability Insurance', 'Business Interruption Insurance', 'Medical Malpractice Insurance', 'Directors & Officers Insurance', 'Personal Accident Insurance', 'Fire Insurance', 'Theft & Burglary Insurance', 'Transit Insurance'] as $item)
                        <button
                            wire:click="$set('insurance_coverage', '{{ $item }}')"
                            @click="open=false"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                            {{ $insurance_coverage === $item ? 'bg-blue-50 text-blue-600' : '' }}"
                        >
                            {{ $item }}
                        </button>
                    @endforeach
                </div>
            </div>
            @error('insurance_coverage')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Note -->
        <div>
            <p class="text-sm text-gray-600">* Upload valid and readable certificates. Leave files unchanged if you don't want to update them.</p>
        </div>
    </div>
</div>

