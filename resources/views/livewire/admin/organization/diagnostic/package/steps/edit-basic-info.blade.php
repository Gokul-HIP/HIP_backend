<div class="bg-white rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Basic Information</h3>

    <div class="space-y-6">
        <div>
            <label class="block text-sm font-medium mb-2">Package Name <span class="text-red-500">*</span></label>
            <input type="text" wire:model="name"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter Package Name">
            @error('name')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
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

        <div class="upload-wrapper">
            <label class="block text-sm font-medium mb-2">Package Image</label>

            @if($old_image && !$image && !$remove_image)
                <div class="preview-box border relative mb-2">
                    <img src="{{ asset('storage/diagnostic-packages/' . $old_image) }}" alt="Current package image" class="preview-img">
                    <button type="button"
                        wire:click="removeImage"
                        class="absolute top-1 right-1 bg-red-600 text-white w-6 h-6 flex items-center justify-center 
                        rounded-full text-sm font-bold shadow hover:bg-red-700"
                        title="Remove image">
                        ×
                    </button>
                </div>
                <button type="button"
                    onclick="document.getElementById('editPackageImage').click()"
                    class="mt-2 px-4 py-2 text-white rounded-lg hover:bg-blue-600 text-sm" style="background:#0da2e7;">
                    <i class="fas fa-edit mr-2"></i>Change Image
                </button>
            @endif

            @if($remove_image && !$image)
                <div class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                    <p class="text-sm text-gray-600 mb-2">Image will be removed</p>
                    <button type="button"
                        wire:click="restoreImage"
                        class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">
                        Cancel Removal
                    </button>
                </div>
            @endif

            <div onclick="document.getElementById('editPackageImage').click()"
                class="image-box border-2 border-dashed border-gray-300 flex items-center justify-center cursor-pointer"
                x-show="!$wire.image && !$wire.old_image">

                <div class="text-center">
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500">or drag and drop</p>
                </div>

                <input type="file" id="editPackageImage" wire:model="image" class="hidden" accept="image/*">
            </div>

            @error('image')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror

            <div class="preview-box border relative" x-show="$wire.image">
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" alt="New package image preview" class="preview-img">
                    <button type="button"
                        wire:click="removeImage"
                        class="absolute top-1 right-1 bg-red-600 text-white w-6 h-6 flex items-center justify-center 
                        rounded-full text-sm font-bold shadow hover:bg-red-700"
                        title="Remove image">
                        ×
                    </button>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea rows="3" wire:model="description"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter Package Description"></textarea>
            @error('description')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

