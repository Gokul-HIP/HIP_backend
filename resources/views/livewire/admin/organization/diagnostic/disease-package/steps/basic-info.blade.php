<div class="bg-white rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Basic Information</h3>

    <div class="space-y-6">
        <div class="relative" x-data @click.outside="$wire.hideDiseaseDropdown()">
            <label class="block text-sm font-medium mb-2">
                Disease Name <span class="text-red-500">*</span>
            </label>
            <input type="text"
                wire:model.live="name"
                wire:focus="$set('show_disease_dropdown', true)"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Select from list or type disease name"
                autocomplete="off">
            @error('name')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror

            @if($show_disease_dropdown && $this->filteredDiseases->isNotEmpty())
                <ul class="absolute z-50 mt-1 w-full max-h-48 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                    @foreach($this->filteredDiseases as $disease)
                        <li wire:key="disease-option-{{ $disease->id }}">
                            <button type="button"
                                wire:click="selectDisease({{ $disease->id }}, @js($disease->name))"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-700">
                                {{ $disease->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            @elseif($show_disease_dropdown && trim($name) !== '' && $this->filteredDiseases->isEmpty())
                <p class="absolute z-50 mt-1 w-full px-4 py-2 text-sm text-gray-500 bg-white border border-gray-200 rounded-lg shadow">
                    No matching disease — custom name will be saved.
                </p>
            @endif

            @if($disease_id)
                <p class="text-xs text-green-600 mt-1">
                    <i class="fas fa-check-circle mr-1"></i> Linked to disease catalog
                </p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Package Code</label>
            <input type="text" wire:model="code"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Auto-generated if left empty">
            @error('code')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div x-data="{
            previewUrl: null,
            handleFileChange(event) {
                const file = event.target.files[0];
                if (file) {
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = URL.createObjectURL(file);
                }
            },
            clearPreview() {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
                const fileInput = document.getElementById('diseasePackageImage');
                if (fileInput) fileInput.value = '';
                $wire.removeImage();
            }
        }"
        @reset-disease-package-file-input.window="
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            const fileInput = document.getElementById('diseasePackageImage');
            if (fileInput) fileInput.value = '';
        ">
            <label class="block text-sm font-medium mb-2">Upload Package Image</label>

            <div onclick="document.getElementById('diseasePackageImage').click()"
                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                x-show="!previewUrl">
                <div class="text-center p-4">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 2MB</p>
                </div>
                <input type="file" id="diseasePackageImage" wire:model="image" class="hidden" accept="image/*"
                    @change="handleFileChange($event)">
            </div>

            @error('image')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror

            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" x-show="previewUrl" x-cloak>
                <img :src="previewUrl" class="preview-img" alt="Preview">
                <button type="button" @click.stop="clearPreview()"
                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea rows="3" wire:model="description"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter package description"></textarea>
            @error('description')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
