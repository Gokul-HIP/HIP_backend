<div class="space-y-5">

    {{-- Disease Name --}}
    <div class="relative" x-data @click.outside="$wire.hideDiseaseDropdown()">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Disease Name <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <i class="fa-solid fa-disease absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
            <input
                type="text"
                wire:model.live="name"
                wire:focus="$set('show_disease_dropdown', true)"
                class="glass-input w-full pl-9 pr-4 py-2.5 rounded-lg text-sm"
                placeholder="Select from list or type disease name"
                autocomplete="off">
        </div>

        @error('name')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
            </p>
        @enderror

        @if($show_disease_dropdown && $this->filteredDiseases->isNotEmpty())
            <ul class="absolute z-50 mt-1 w-full max-h-48 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg divide-y divide-gray-50">
                @foreach($this->filteredDiseases as $disease)
                    <li wire:key="disease-option-{{ $disease->id }}">
                        <button type="button"
                            wire:click="selectDisease({{ $disease->id }}, @js($disease->name))"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-blue-50 hover:text-blue-700 transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-circle-dot text-[10px] text-gray-300"></i>
                            {{ $disease->name }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @elseif($show_disease_dropdown && trim($name) !== '' && $this->filteredDiseases->isEmpty())
            <div class="absolute z-50 mt-1 w-full px-4 py-3 text-sm text-gray-500 bg-white border border-gray-200 rounded-lg shadow flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-gray-400"></i>
                No matching disease — custom name will be saved.
            </div>
        @endif

        @if($disease_id)
            <p class="text-xs text-green-600 mt-1.5 flex items-center gap-1">
                <i class="fa-solid fa-circle-check"></i> Linked to disease catalog
            </p>
        @endif
    </div>

    {{-- Package Code --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Package Code</label>
        <div class="relative">
            <i class="fa-solid fa-barcode absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
            <input
                type="text"
                wire:model="code"
                class="glass-input w-full pl-9 pr-4 py-2.5 rounded-lg text-sm"
                placeholder="Auto-generated if left empty">
        </div>
        @error('code')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Package Image --}}
    <div x-data="{
        previewUrl: null,
        isDragging: false,
        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = URL.createObjectURL(file);
        },
        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = URL.createObjectURL(file);
            // pass file to Livewire file input
            const dt = new DataTransfer();
            dt.items.add(file);
            const input = document.getElementById('diseasePackageImage');
            if (input) {
                input.files = dt.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
        clearPreview() {
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
            const input = document.getElementById('diseasePackageImage');
            if (input) input.value = '';
            $wire.removeImage();
        }
    }"
    @reset-disease-package-file-input.window="
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        const input = document.getElementById('diseasePackageImage');
        if (input) input.value = '';
    ">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Package Image</label>

        {{-- Upload zone --}}
        <div x-show="!previewUrl"
            class="image-upload-zone"
            :class="{ 'drag-over': isDragging }"
            @click="$refs.fileInput.click()"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)">
            <input
                type="file"
                id="diseasePackageImage"
                x-ref="fileInput"
                wire:model="image"
                class="hidden"
                accept="image/*"
                @change="handleFileChange($event)">
            <div class="text-center px-4 py-6 pointer-events-none">
                <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-cloud-arrow-up text-xl text-[#0da2e7]"></i>
                </div>
                <p class="text-sm font-medium text-gray-700">Click or drag & drop to upload</p>
                <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2 MB</p>
            </div>
        </div>

        {{-- Preview --}}
        <div class="preview-box" x-show="previewUrl" x-cloak>
            <img :src="previewUrl" class="preview-img" alt="Preview">
            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent pointer-events-none rounded-xl"></div>
            <button type="button" @click.stop="clearPreview()"
                class="absolute top-2 right-2 w-8 h-8 flex items-center justify-center rounded-full bg-red-500 hover:bg-red-600 text-white shadow transition">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
            <p class="absolute bottom-2 left-3 text-xs text-white/80 pointer-events-none">
                <i class="fa-solid fa-image mr-1"></i> Image preview
            </p>
        </div>

        {{-- Upload loading indicator --}}
        <div wire:loading wire:target="image" class="mt-2 flex items-center gap-2 text-xs text-gray-500">
            <i class="fa-solid fa-circle-notch fa-spin text-[#0da2e7]"></i> Uploading...
        </div>

        @error('image')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Description --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
        <textarea
            rows="3"
            wire:model="description"
            class="glass-input w-full px-4 py-2.5 rounded-lg text-sm resize-none"
            placeholder="Enter package description"></textarea>
        @error('description')
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
            </p>
        @enderror
    </div>

</div>