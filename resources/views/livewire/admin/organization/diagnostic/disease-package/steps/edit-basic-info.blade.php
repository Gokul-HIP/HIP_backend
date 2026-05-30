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
                        <li wire:key="edit-disease-option-{{ $disease->id }}">
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
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Package Code</label>
            <input type="text" wire:model="code"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Package Code">
            @error('code')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div x-data="{
            previewUrl: null,
            showExisting: @js(!$remove_image && !empty($old_image)),
            clearPreview() {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
                this.showExisting = false;
                document.getElementById('editDiseasePackageImage')?.value && (document.getElementById('editDiseasePackageImage').value = '');
                $wire.removeImage();
            },
            restoreExisting() {
                this.previewUrl = null;
                this.showExisting = true;
                $wire.restoreImage();
            }
        }"
        @reset-disease-package-file-input.window="
            previewUrl = null;
            showExisting = @js(!$remove_image && !empty($old_image));
        ">
            <label class="block text-sm font-medium mb-2">Package Image</label>

            @if($old_image && !$remove_image)
                <div class="mb-3 border border-gray-300 rounded-lg relative overflow-hidden" x-show="showExisting">
                    <img src="{{ asset('storage/disease-packages/' . $old_image) }}" class="preview-img" alt="Current image">
                    <button type="button" @click="clearPreview()"
                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if($remove_image && !$image)
                <button type="button" wire:click="restoreImage" @click="restoreExisting()"
                    class="mb-3 text-sm text-blue-600 hover:underline">
                    Restore previous image
                </button>
            @endif

            <div onclick="document.getElementById('editDiseasePackageImage').click()"
                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gray-400 bg-gray-50"
                x-show="!previewUrl && (!showExisting || {{ $remove_image ? 'true' : 'false' }})">
                <div class="text-center p-4">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-700">Click to upload new image</p>
                </div>
                <input type="file" id="editDiseasePackageImage" wire:model="image" class="hidden" accept="image/*"
                    @change="previewUrl = URL.createObjectURL($event.target.files[0]); showExisting = false">
            </div>

            @error('image')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
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
