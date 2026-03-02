<div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">1. Centre Type</h3>
    <p class="text-sm text-gray-500 mb-6">Select the type of wellness centre you're registering</p>
    
    <div class="space-y-6">
        <!-- Centre Type -->
        <div>
            <label for="centre_type" class="block text-sm font-medium text-gray-700 mb-2">
                Centre Type <span class="text-red-500">*</span>
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
                    <span>
                        @if($centre_type)
                            @php
                                $selectedCategory = $wellnessCategories->firstWhere('id', $centre_type);
                            @endphp
                            {{ $selectedCategory->parent_category ?? 'Select category' }}
                        @else
                            Select category
                        @endif
                    </span>
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
                        wire:click="$set('centre_type', '')"
                        @click="open=false"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                        {{ !$centre_type ? 'bg-blue-50 text-blue-600' : '' }}"
                    >
                        Select category
                    </button>
                    @foreach($wellnessCategories as $category)
                        <button
                            wire:click="$set('centre_type', '{{ $category->id }}')"
                            @click="open=false"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                            {{ $centre_type == $category->id ? 'bg-blue-50 text-blue-600' : '' }}"
                        >
                            {{ $category->parent_category }}
                        </button>
                    @endforeach
                </div>
            </div>
            @error('centre_type')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Operating Mode -->
        <div>
            <label for="operating_mode" class="block text-sm font-medium text-gray-700 mb-2">
                Operating Mode <span class="text-red-500">*</span>
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
                    <span>{{ $operating_mode ?: 'Select operating mode' }}</span>
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
                        wire:click="$set('operating_mode', '')"
                        @click="open=false"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                        {{ !$operating_mode ? 'bg-blue-50 text-blue-600' : '' }}"
                    >
                        Select operating mode
                    </button>
                    @foreach(['Online', 'Offline', 'Hybrid'] as $item)
                        <button
                            wire:click="$set('operating_mode', '{{ $item }}')"
                            @click="open=false"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                            {{ $operating_mode === $item ? 'bg-blue-50 text-blue-600' : '' }}"
                        >
                            {{ $item }}
                        </button>
                    @endforeach
                </div>
            </div>
            @error('operating_mode')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Centre Image Upload (add + edit) -->
        <div x-data="{ 
            previewUrl: null,
            showExisting: true,
            isUploading: false,
            uploadProgress: 0,
            handleFileChange(event) {
                const file = event.target.files[0];
                if (file) {
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = URL.createObjectURL(file);
                    this.showExisting = false;
                    this.isUploading = true;
                    this.uploadProgress = 0;
                    const input = event.target;
                    @this.upload('image', input.files[0], 
                        () => { this.isUploading = false; this.uploadProgress = 100; },
                        (err) => { this.isUploading = false; this.uploadProgress = 0; console.error(err); alert('Upload failed. Check file size and type.'); },
                        (e) => { this.uploadProgress = Math.round(e.detail.progress || 0); }
                    );
                }
            },
            clearPreview() {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
                this.isUploading = false;
                this.uploadProgress = 0;
                this.showExisting = !!this.$wire.old_image_path && !this.$wire.remove_image;
                const el = document.getElementById('wellnessCentreImage');
                if (el) el.value = '';
                @this.removeImage();
            },
            removeExisting() { this.showExisting = false; @this.removeImage(); },
            restoreExisting() { this.showExisting = true; @this.restoreImage(); },
            init() {
                this.$nextTick(() => {
                    this.showExisting = !!this.$wire.old_image_path && !this.$wire.remove_image && !this.previewUrl;
                });
                this.$watch('$wire.old_image_path', (v) => {
                    if (v && !this.$wire.remove_image && !this.previewUrl) this.showExisting = true;
                    else if (!v) this.showExisting = false;
                });
                this.$watch('$wire.image', (v) => {
                    if (!v && this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                        const el = document.getElementById('wellnessCentreImage');
                        if (el) el.value = '';
                    }
                    if (!v && this.$wire.old_image_path && !this.$wire.remove_image) this.showExisting = true;
                });
                this.$watch('$wire.remove_image', (v) => {
                    if (!v && this.$wire.old_image_path && !this.previewUrl) this.showExisting = true;
                    else if (v) this.showExisting = false;
                });
                Livewire.on('reset-file-input', () => {
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = null;
                    this.isUploading = false;
                    this.uploadProgress = 0;
                    this.showExisting = !!this.$wire.old_image_path && !this.$wire.remove_image;
                    const el = document.getElementById('wellnessCentreImage');
                    if (el) el.value = '';
                });
            }
        }">
            <label class="block text-sm font-medium text-gray-700 mb-2">Centre Image</label>

            <!-- Existing image (edit only) -->
            <div x-show="showExisting && !previewUrl && $wire.old_image_path && !$wire.remove_image">
                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2 min-h-[200px]">
                    @if(!empty($old_image_path))
                        <img src="{{ asset('storage/wellness-centers/images/' . $old_image_path) }}" 
                             alt="Current centre image" 
                             class="w-full h-[200px] object-cover rounded-lg"
                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-full h-[200px] flex items-center justify-center bg-gray-100 text-gray-400 rounded-lg" style="display: none;">
                            <span>Image not found</span>
                        </div>
                    @else
                        <div class="w-full h-[200px] flex items-center justify-center bg-gray-100 text-gray-400 rounded-lg">
                            <span>No image</span>
                        </div>
                    @endif
                    <button type="button"
                        @click.stop="removeExisting()"
                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                        title="Remove image">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button type="button"
                    @click="document.getElementById('wellnessCentreImage').click()"
                    class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                    <i class="fas fa-edit mr-2"></i>Change Image
                </button>
            </div>

            <!-- Image removal message -->
            <div x-show="$wire.remove_image && !$wire.image && !previewUrl" 
                 class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                <p class="text-sm text-gray-600 mb-2"><i class="fas fa-info-circle mr-1"></i>Image will be removed</p>
                <button type="button" @click="restoreExisting()"
                    class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                    <i class="fas fa-undo mr-1"></i>Cancel Removal
                </button>
            </div>

            <!-- Upload box (no image) -->
            <div @click="document.getElementById('wellnessCentreImage').click()"
                class="border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gray-400 transition-colors bg-gray-50 min-h-[150px]"
                x-show="!previewUrl && !$wire.image && !showExisting && !$wire.remove_image">
                <div class="text-center p-4">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 2MB</p>
                </div>
                <input type="file" id="wellnessCentreImage" class="hidden" accept="image/*" @change="handleFileChange($event)">
            </div>

            @error('image')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror

            <!-- New image preview -->
            <div class="border border-gray-300 rounded-lg relative overflow-hidden min-h-[200px]" x-show="previewUrl" x-cloak>
                <img :src="previewUrl" class="w-full h-[200px] object-cover rounded-lg" alt="New preview">
                <div x-show="isUploading" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center rounded-lg">
                    <div class="text-center text-white">
                        <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                        <p class="text-sm font-medium">Uploading...</p>
                        <p class="text-xs mt-1" x-text="uploadProgress + '%'"></p>
                    </div>
                </div>
                <button type="button" @click.stop="clearPreview()" x-show="!isUploading"
                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

