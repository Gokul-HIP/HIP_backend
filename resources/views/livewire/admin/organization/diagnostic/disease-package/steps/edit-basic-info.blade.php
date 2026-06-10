<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">

    <div class="flex items-center gap-2 mb-1">
        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center">
            <i class="fas fa-file-medical text-[#0da2e7] text-xs"></i>
        </div>
        <h3 class="text-base font-semibold text-gray-800">Basic Information</h3>
    </div>

    {{-- ── Disease Name ── --}}
    <div class="relative" x-data @click.outside="$wire.hideDiseaseDropdown()">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Disease Name <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fas fa-disease text-xs"></i>
            </span>
            <input type="text"
                wire:model.live="name"
                wire:focus="$set('show_disease_dropdown', true)"
                class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-gray-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#0da2e7] transition"
                placeholder="Select from list or type disease name"
                autocomplete="off">
        </div>
        @error('name')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fas fa-circle-exclamation text-xs"></i> {{ $message }}
            </p>
        @enderror

        @if($show_disease_dropdown && $this->filteredDiseases->isNotEmpty())
            <ul class="absolute z-50 mt-1 w-full max-h-48 overflow-y-auto bg-white border border-gray-200
                       rounded-lg shadow-lg divide-y divide-gray-50">
                @foreach($this->filteredDiseases as $disease)
                    <li wire:key="edit-disease-option-{{ $disease->id }}">
                        <button type="button"
                            wire:click="selectDisease({{ $disease->id }}, @js($disease->name))"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-blue-50 hover:text-[#0da2e7] transition">
                            {{ $disease->name }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @elseif($show_disease_dropdown && trim($name) !== '' && $this->filteredDiseases->isEmpty())
            <div class="absolute z-50 mt-1 w-full px-4 py-3 text-sm text-gray-500 bg-white
                        border border-gray-200 rounded-lg shadow flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-400"></i>
                No matching disease — custom name will be saved.
            </div>
        @endif
    </div>

    {{-- ── Package Code ── --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Package Code</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fas fa-barcode text-xs"></i>
            </span>
            <input type="text" wire:model="code"
                class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-gray-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#0da2e7] transition"
                placeholder="e.g. PKG-001">
        </div>
        @error('code')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fas fa-circle-exclamation text-xs"></i> {{ $message }}
            </p>
        @enderror
    </div>

    {{-- ── Package Image — mirrors EditOrganization pattern exactly ── --}}
    <div x-data="{
            previewUrl: null,
            showExisting: false,
            isUploading: false,
            uploadProgress: 0,

            handleFileChange(event) {
                const file = event.target.files[0];
                if (!file) return;

                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = URL.createObjectURL(file);
                this.showExisting = false;
                this.isUploading = true;
                this.uploadProgress = 0;

                @this.upload('image', file,
                    () => {
                        this.isUploading = false;
                        this.uploadProgress = 100;
                    },
                    (error) => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        this.previewUrl = null;
                        console.error('Upload error:', error);
                        alert('File upload failed. Please check the file size and type.');
                    },
                    (event) => {
                        this.uploadProgress = Math.round(event.detail.progress || 0);
                    }
                );
            },

            clearPreview() {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
                this.isUploading = false;
                this.uploadProgress = 0;
                this.showExisting = !!$wire.old_image && !$wire.remove_image;
                const inp = document.getElementById('editDiseasePackageImage');
                if (inp) inp.value = '';
                $wire.removeImage();
            },

            removeExisting() {
                this.showExisting = false;
                $wire.removeImage();
            },

            restoreExisting() {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
                this.showExisting = true;
                $wire.restoreImage();
            },

            init() {
                this.$nextTick(() => {
                    this.showExisting = !!$wire.old_image && !$wire.remove_image && !this.previewUrl;
                });

                this.$watch('$wire.old_image', (value) => {
                    if (value && !$wire.remove_image && !this.previewUrl) {
                        this.showExisting = true;
                    } else if (!value) {
                        this.showExisting = false;
                    }
                });

                this.$watch('$wire.image', (value) => {
                    if (!value && this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                        const inp = document.getElementById('editDiseasePackageImage');
                        if (inp) inp.value = '';
                    }
                    if (!value && $wire.old_image && !$wire.remove_image) {
                        this.showExisting = true;
                    }
                });

                this.$watch('$wire.remove_image', (value) => {
                    if (!value && $wire.old_image && !this.previewUrl) {
                        this.showExisting = true;
                    } else if (value) {
                        this.showExisting = false;
                    }
                });

                Livewire.on('reset-disease-package-file-input', () => {
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = null;
                    this.isUploading = false;
                    this.uploadProgress = 0;
                    this.showExisting = !!$wire.old_image && !$wire.remove_image;
                    const inp = document.getElementById('editDiseasePackageImage');
                    if (inp) inp.value = '';
                });
            }
        }">

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Package Image</label>

        {{-- Always-present hidden file input — NOT wire:model, use @this.upload() instead --}}
        <input type="file"
               id="editDiseasePackageImage"
               class="hidden"
               accept="image/*"
               @change="handleFileChange($event)">

        {{-- 1. Existing image --}}
        <div x-show="showExisting && !previewUrl && $wire.old_image && !$wire.remove_image">
            <div class="relative rounded-lg overflow-hidden border border-gray-200 shadow-sm mb-2" style="height:200px;">
                @if(!empty($old_image))
                    <img src="{{ asset('storage/disease-packages/' . $old_image) }}"
                         class="w-full h-full object-cover"
                         alt="Current package image"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-full h-full hidden items-center justify-center bg-gray-100 text-gray-400 text-sm">
                        Image not found
                    </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent
                            flex items-end justify-between px-3 py-2.5">
                    <span class="text-white text-xs font-medium flex items-center gap-1.5">
                        <i class="fas fa-image"></i> Current image
                    </span>
                    <div class="flex gap-2">
                        <button type="button"
                            @click="document.getElementById('editDiseasePackageImage').click()"
                            class="px-2.5 py-1 bg-white/90 hover:bg-white text-gray-700 text-xs
                                   font-medium rounded-md transition flex items-center gap-1">
                            <i class="fas fa-pen text-xs"></i> Change
                        </button>
                        <button type="button"
                            @click="removeExisting()"
                            class="px-2.5 py-1 bg-red-500/90 hover:bg-red-600 text-white text-xs
                                   font-medium rounded-md transition flex items-center gap-1" style="background-color: #ef4444;">
                            <i class="fas fa-trash text-xs"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. New image preview with upload progress --}}
        <div x-show="previewUrl" style="display:none;">
            <div class="relative rounded-lg overflow-hidden border border-gray-200 shadow-sm mb-2" style="height:200px;">
                <img :src="previewUrl" class="w-full h-full object-cover" alt="New image preview">

                {{-- Upload progress overlay --}}
                <div x-show="isUploading"
                     class="absolute inset-0 bg-black/50 flex items-center justify-center">
                    <div class="text-center text-white">
                        <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                        <p class="text-sm font-medium">Uploading...</p>
                        <p class="text-xs mt-1" x-text="uploadProgress + '%'"></p>
                    </div>
                </div>

                <div x-show="!isUploading"
                     class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent
                            flex items-end justify-between px-3 py-2.5">
                    <span class="text-white text-xs font-medium flex items-center gap-1.5">
                        <i class="fas fa-image"></i> New image
                    </span>
                    <button type="button"
                        @click="clearPreview()"
                        class="px-2.5 py-1 bg-red-500/90 hover:bg-red-600 text-white text-xs
                               font-medium rounded-md transition flex items-center gap-1">
                        <i class="fas fa-trash text-xs"></i> Remove
                    </button>
                </div>
            </div>
        </div>

        {{-- 3. Removed notice --}}
        <div x-show="$wire.remove_image && !$wire.image && !previewUrl"
             class="mb-3 flex items-center justify-between px-4 py-3 bg-amber-50
                    border border-amber-200 rounded-lg text-sm">
            <span class="text-amber-700 flex items-center gap-1.5">
                <i class="fas fa-triangle-exclamation text-xs"></i> Image will be removed on save
            </span>
            <button type="button"
                wire:click="restoreImage"
                @click="restoreExisting()"
                class="text-xs text-blue-600 hover:underline font-medium flex items-center gap-1">
                <i class="fas fa-rotate-left text-xs"></i> Undo
            </button>
        </div>

        {{-- 4. Upload dropzone --}}
        <div x-show="!previewUrl && !showExisting && !$wire.remove_image"
             @click="document.getElementById('editDiseasePackageImage').click()"
             class="border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center
                    justify-center cursor-pointer hover:border-[#0da2e7] hover:bg-blue-50/30
                    transition-all group"
             style="min-height:150px;">
            <div class="text-center p-6">
                <div class="w-12 h-12 rounded-xl bg-gray-100 group-hover:bg-blue-100 flex items-center
                            justify-center mx-auto mb-3 transition">
                    <i class="fas fa-cloud-arrow-up text-xl text-gray-400 group-hover:text-[#0da2e7] transition"></i>
                </div>
                <p class="text-sm font-medium text-gray-600 group-hover:text-[#0da2e7] transition">
                    Click to upload image
                </p>
                <p class="text-xs text-gray-400 mt-1">PNG, JPG — max 2MB</p>
            </div>
        </div>

        @error('image')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fas fa-circle-exclamation text-xs"></i> {{ $message }}
            </p>
        @enderror

    </div>

    {{-- ── Description ── --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
        <textarea rows="3" wire:model="description"
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm resize-none
                   focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#0da2e7] transition"
            placeholder="Enter a short description of this package..."></textarea>
        @error('description')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fas fa-circle-exclamation text-xs"></i> {{ $message }}
            </p>
        @enderror
    </div>

</div>