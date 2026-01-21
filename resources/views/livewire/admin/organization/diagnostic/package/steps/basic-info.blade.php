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
                placeholder="Auto-generated if left empty">
            @error('code')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="upload-wrapper">
            <label class="block text-sm font-medium mb-2">Upload Package Image</label>

            <div onclick="document.getElementById('packageImage').click()"
                class="image-box border-2 border-dashed border-gray-300 flex items-center justify-center cursor-pointer"
                x-show="!$wire.image">

                <div class="text-center">
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500">or drag and drop</p>
                </div>

                <input type="file" id="packageImage" wire:model="image" class="hidden" accept="image/*">
            </div>

            @error('image')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror

            <div class="preview-box border relative" x-show="$wire.image">
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" class="preview-img">
                    <button type="button"
                        wire:click="removeImage"
                        class="absolute top-1 right-1 bg-red-600 text-white w-6 h-6 flex items-center justify-center 
                        rounded-full text-sm font-bold shadow hover:bg-red-700">
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

