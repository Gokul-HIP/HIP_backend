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

        <div x-data="{ 
            previewUrl: null,
            handleFileChange(event) {
                const file = event.target.files[0];
                if (file) {
                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                    }
                    this.previewUrl = URL.createObjectURL(file);
                }
            },
            clearPreview() {
                if (this.previewUrl) {
                    URL.revokeObjectURL(this.previewUrl);
                }
                this.previewUrl = null;
                const fileInput = document.getElementById('packageImage');
                if (fileInput) fileInput.value = '';
                $wire.removeImage();
            }
        }"
        @reset-file-input.window="
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            previewUrl = null;
            const fileInput = document.getElementById('packageImage');
            if (fileInput) fileInput.value = '';
        ">
            <label class="block text-sm font-medium mb-2">Upload Package Image</label>

            <!-- Upload Box -->
            <div onclick="document.getElementById('packageImage').click()"
                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                       cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                x-show="!previewUrl">

                <div class="text-center p-4">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                    <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                </div>

                <input type="file" 
                       id="packageImage" 
                       wire:model="image" 
                       class="hidden"
                       accept="image/*"
                       @change="handleFileChange($event)">
            </div>

            @error('image')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror

            <!-- Preview Box -->
            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                 x-show="previewUrl"
                 x-cloak>
                <img :src="previewUrl" class="preview-img" alt="Preview">
                <button type="button"
                    @click.stop="clearPreview()"
                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                        rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
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

        <div>
            <label class="block text-sm font-medium mb-2">Preparation Instructions</label>
            <textarea rows="4" wire:model="preparation_instruction"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter one instruction per line (e.g. fasting requirements, documents to carry)"></textarea>
            <p class="text-xs text-gray-500 mt-1">Each line is shown as a separate instruction in the app.</p>
            @error('preparation_instruction')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Terms &amp; Conditions</label>
            <textarea rows="4" wire:model="terms_and_conditions"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter one term per line"></textarea>
            <p class="text-xs text-gray-500 mt-1">Each line is shown as a separate term in the app.</p>
            @error('terms_and_conditions')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

