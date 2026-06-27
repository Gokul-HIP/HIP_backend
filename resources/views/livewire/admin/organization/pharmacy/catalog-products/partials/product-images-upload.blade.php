@php
    $inputId = $inputId ?? 'catalogProductImages';
    $hasExisting = ! empty($existingImages) && count($existingImages) > 0;
@endphp

<div class="space-y-3"
     x-data="{
        localPreviews: [],
        addLocalPreviews(event) {
            Array.from(event.target.files || []).forEach((file) => {
                this.localPreviews.push({
                    id: crypto.randomUUID(),
                    url: URL.createObjectURL(file),
                    name: file.name,
                });
            });
        },
        removeLocalPreview(id) {
            const item = this.localPreviews.find(p => p.id === id);
            if (item) URL.revokeObjectURL(item.url);
            this.localPreviews = this.localPreviews.filter(p => p.id !== id);
        },
        clearLocalPreviews() {
            this.localPreviews.forEach(p => URL.revokeObjectURL(p.url));
            this.localPreviews = [];
            const input = document.getElementById('{{ $inputId }}');
            if (input) input.value = '';
        },
        init() {
            this.$watch('$wire.newImages.length', (length) => {
                if (length > 0) {
                    this.localPreviews.forEach(p => URL.revokeObjectURL(p.url));
                    this.localPreviews = [];
                }
            });
        }
     }"
     @reset-catalog-images.window="clearLocalPreviews()">

    <label class="block text-sm font-medium mb-1">Product Images</label>
    <p class="text-xs text-gray-500 mb-2">Upload one or more images. PNG, JPG up to 4MB each.</p>

    {{-- Existing images (edit) --}}
    @if($hasExisting)
        <div class="mb-2">
            <p class="text-xs font-medium text-gray-600 mb-2 uppercase tracking-wide">Current Images</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($existingImages as $index => $image)
                    <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden bg-gray-50"
                         wire:key="existing-image-{{ $index }}-{{ $image }}">
                        <img src="{{ \App\Services\CatalogProductService::imageUrl($image) }}"
                             alt="Product image {{ $index + 1 }}"
                             class="preview-img-thumb">
                        <button type="button"
                                wire:click="removeExistingImage({{ $index }})"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                title="Remove image">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Upload box --}}
    <div onclick="document.getElementById('{{ $inputId }}').click()"
         class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-[#0da2e7] hover:bg-blue-50/30 transition-colors bg-gray-50">
        <div class="text-center p-4">
            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
            <p class="text-sm text-gray-700 font-medium">Click to upload images</p>
            <p class="text-xs text-gray-500 mt-1">or drag and drop multiple files</p>
            <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 4MB</p>
        </div>
        <input type="file"
               id="{{ $inputId }}"
               wire:model="newImages"
               class="hidden"
               accept="image/*"
               multiple
               @change="addLocalPreviews($event)">
    </div>

    @error('newImages.*')
        <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
    @enderror

    {{-- Uploading indicator --}}
    <div wire:loading wire:target="newImages" class="flex items-center gap-2 text-sm text-[#0da2e7] mt-2">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Uploading images...</span>
    </div>

    {{-- Livewire uploaded previews --}}
    @if(count($newImages))
        <div class="mt-2">
            <p class="text-xs font-medium text-gray-600 mb-2 uppercase tracking-wide">New Uploads</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($newImages as $index => $image)
                    <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden bg-gray-50"
                         wire:key="lw-new-image-{{ $index }}">
                        <img src="{{ $image->temporaryUrl() }}"
                             alt="New upload {{ $index + 1 }}"
                             class="preview-img-thumb">
                        <button type="button"
                                wire:click="removeNewImage({{ $index }})"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                title="Remove image">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Instant Alpine previews (before Livewire finishes) --}}
    <div x-show="localPreviews.length && !$wire.newImages.length" x-cloak class="mt-2">
        <p class="text-xs font-medium text-gray-600 mb-2 uppercase tracking-wide">Preview</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <template x-for="item in localPreviews" :key="item.id">
                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden bg-gray-50">
                    <img :src="item.url" :alt="item.name" class="preview-img-thumb">
                    <button type="button"
                            @click.stop="removeLocalPreview(item.id)"
                            class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
