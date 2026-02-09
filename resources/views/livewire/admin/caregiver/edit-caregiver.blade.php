<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-800">Edit Caregiver</h2>
        </div>

        <!-- Form -->
        <form wire:submit.prevent="save" class="p-8" enctype="multipart/form-data">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Left Column -->
                <div class="space-y-5">
                    
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Name<span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            wire:model="name"
                            placeholder="Enter caregiver name"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Select Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Category
                        </label>
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button
                                type="button"
                                @click="open = !open"
                                class="w-full inline-flex items-center justify-between text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-normal rounded-lg text-sm px-4 py-2.5"
                            >
                                <span>{{ $category ?: 'Elder Care/Nurse/Both' }}</span>
                                <svg class="w-4 h-4 ms-1.5 -me-0.5" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div 
                                x-show="open"
                                x-cloak
                                x-transition
                                class="absolute z-10 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-lg"
                            >
                                <ul class="p-2 text-sm text-gray-700 font-medium">
                                    @foreach($categories as $cat)
                                        <li>
                                            <button
                                                type="button"
                                                wire:click="$set('category', '{{ $cat }}')"
                                                @click="open = false"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left"
                                            >
                                                {{ $cat }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('category')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Gender
                        </label>
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button
                                type="button"
                                @click="open = !open"
                                class="w-full inline-flex items-center justify-between text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-normal rounded-lg text-sm px-4 py-2.5"
                            >
                                <span>{{ $gender ?: 'Select gender' }}</span>
                                <svg class="w-4 h-4 ms-1.5 -me-0.5" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div 
                                x-show="open"
                                x-cloak
                                x-transition
                                class="absolute z-10 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-lg"
                            >
                                <ul class="p-2 text-sm text-gray-700 font-medium">
                                    @foreach($genders as $g)
                                        <li>
                                            <button
                                                type="button"
                                                wire:click="$set('gender', '{{ $g }}')"
                                                @click="open = false"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left"
                                            >
                                                {{ $g }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('gender')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Qualification -->
                    <div x-data="pillbox({
                            options: @js($qualificationOptions),
                            selected: @entangle('qualification')
                        })"
                        class="relative">

                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Qualification
                        </label>

                        <!-- Pills Input -->
                        <div class="min-h-[44px] flex flex-wrap items-center gap-2 px-3 py-2 border rounded-lg bg-white mb-3">
                            <template x-for="id in selected" :key="id">
                                <span class="flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs">
                                    <span x-text="getLabel(id)"></span>
                                    <button type="button" @click="remove(id)">✕</button>
                                </span>
                            </template>

                            <span x-show="selected.length === 0" class="text-gray-400 text-sm">
                                Select qualifications...
                            </span>
                        </div>

                        <div class="border rounded-lg h-48 overflow-y-auto p-3 space-y-2">
                            <template x-for="option in options" :key="option.id">
                                <label class="flex items-center gap-3 text-sm cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :value="option.id"
                                        @change="toggleOption(option.id)"
                                        :checked="selected.includes(option.id)"
                                    >
                                    <span x-text="option.name"></span>
                                </label>
                            </template>

                            <template x-if="options.length === 0">
                                <p class="text-sm text-gray-500 text-center">
                                    No qualifications found
                                </p>
                            </template>
                        </div>

                        @error('qualification')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Working since -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Working since
                        </label>
                        <input 
                            type="number" 
                            wire:model="working_since"
                            placeholder="Enter year eg 2015"
                            min="1950"
                            max="{{ date('Y') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('working_since')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Biography -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Biography
                        </label>
                        <textarea 
                            wire:model="about"
                            rows="4"
                            placeholder="about our nurse..."
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                        ></textarea>
                        @error('about')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Wellness Centre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Wellness Centre
                        </label>
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button
                                type="button"
                                @click="open = !open"
                                class="w-full inline-flex items-center justify-between text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-normal rounded-lg text-sm px-4 py-2.5"
                            >
                                <span>
                                    @if($wellness_center_id)
                                        {{ $wellness_centers->firstWhere('id', $wellness_center_id)?->centre_name ?? 'Enter Wellness Centre' }}
                                    @else
                                        Enter Wellness Centre
                                    @endif
                                </span>
                                <svg class="w-4 h-4 ms-1.5 -me-0.5" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div 
                                x-show="open"
                                x-cloak
                                x-transition
                                class="absolute z-10 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                            >
                                <ul class="p-2 text-sm text-gray-700 font-medium">
                                    @foreach($wellness_centers as $center)
                                        <li>
                                            <button
                                                type="button"
                                                wire:click="$set('wellness_center_id', {{ $center->id }})"
                                                @click="open = false"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left"
                                            >
                                                {{ $center->centre_name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('wellness_center_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Person Contact -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Person Contact
                        </label>
                        <input 
                            type="text" 
                            wire:model="mobile_number"
                            placeholder="eg 74622221991"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('mobile_number')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Whatsapp Number - Caregiver -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Whatsapp Number - Caregiver
                        </label>
                        <input 
                            type="text" 
                            wire:model="whatsapp_number"
                            placeholder="eg 74622221991"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('whatsapp_number')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Caregiver Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Caregiver Email
                        </label>
                        <input 
                            type="email" 
                            wire:model="email"
                            placeholder="eg@xymail.com"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <!-- Right Column -->
                <div class="space-y-5">
                    
                    <!-- Profile Photo Upload -->
                    <div x-data="{ 
                        previewUrl: null,
                        showExisting: true,
                        handleFileChange(event) {
                            const file = event.target.files[0];
                            if (file) {
                                if (this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                }
                                this.previewUrl = URL.createObjectURL(file);
                                this.showExisting = false;
                            }
                        },
                            clearPreview() {
                                if (this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                }
                                this.previewUrl = null;
                                this.showExisting = !!this.$wire.existing_profile_photo && !this.$wire.remove_profile_photo;
                                const fileInput = document.getElementById('profilePhoto');
                                if (fileInput) fileInput.value = '';
                                $wire.removeProfilePhoto();
                            },
                        removeExisting() {
                            this.showExisting = false;
                            $wire.removeProfilePhoto();
                        },
                            restoreExisting() {
                                this.showExisting = true;
                                $wire.restoreProfilePhoto();
                            },
                            init() {
                                this.$nextTick(() => {
                                    this.showExisting = !!this.$wire.existing_profile_photo && !this.$wire.remove_profile_photo && !this.previewUrl;
                                });
                            }
                        }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Profile Photo<span class="text-red-500">*</span>
                        </label>

                        <!-- Existing Image -->
                            <div x-show="showExisting && !previewUrl && $wire.existing_profile_photo && !$wire.remove_profile_photo" class="mb-2">
                                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden h-32">
                                    <img src="{{ $existing_profile_photo ? asset('storage/' . $existing_profile_photo) : '' }}" 
                                         alt="Current profile photo" 
                                         class="preview-img w-full h-full object-cover"
                                         onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="preview-img flex items-center justify-center bg-gray-100 text-gray-400" style="display: none;">
                                        <span>Image not found</span>
                                    </div>
                                <button type="button"
                                    @click.stop="removeExisting()"
                                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                    rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                    title="Remove image">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="button"
                                @click="document.getElementById('profilePhoto').click()"
                                class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                <i class="fas fa-edit mr-2"></i>Change Image
                            </button>
                        </div>

                        <!-- Image Removal Message -->
                            <div x-show="$wire.remove_profile_photo && !$wire.existing_profile_photo && !previewUrl" 
                                 class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                            <p class="text-sm text-gray-600 mb-2">
                                <i class="fas fa-info-circle mr-1"></i>Image will be removed
                            </p>
                            <button type="button"
                                @click="restoreExisting()"
                                class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-undo mr-1"></i>Cancel Removal
                            </button>
                        </div>

                        <!-- Upload Box - Only show when no existing image or preview -->
                        <div x-show="!previewUrl && !showExisting">
                            <div 
                                @click="document.getElementById('profilePhoto').click()"
                                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                   cursor-pointer hover:border-blue-400 transition-colors bg-gray-50">
                                <div class="text-center p-6">
                                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-image text-blue-500 text-2xl"></i>
                                    </div>
                                    <p class="text-gray-700 font-medium mb-1">Upload Photo</p>
                                    <p class="text-sm text-gray-500 mb-3">or drag and drop</p>
                                </div>
                            </div>
                        </div>

                        <input type="file" 
                               id="profilePhoto" 
                               wire:model="profile_photo" 
                               class="hidden"
                               accept="image/*"
                               @change="handleFileChange($event)">

                        <!-- Preview Box -->
                            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden h-32" 
                                 x-show="previewUrl"
                                 x-cloak>
                                <img :src="previewUrl" class="preview-img w-full h-full object-cover" alt="Profile Preview">
                            <button type="button"
                                @click.stop="clearPreview()"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                    rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-2">Square photos, High Res, max 5 MB, PNG or JPG</p>
                        
                        @error('profile_photo')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Gallery Photos Upload -->
                    <div x-data="{ 
                        galleryPreviews: [],
                        handleGalleryFiles(event) {
                            const files = Array.from(event.target.files);
                            files.forEach(file => {
                                const url = URL.createObjectURL(file);
                                this.galleryPreviews.push(url);
                            });
                        },
                        removeGalleryImage(index) {
                            if (this.galleryPreviews[index]) {
                                URL.revokeObjectURL(this.galleryPreviews[index]);
                            }
                            this.galleryPreviews.splice(index, 1);
                            $wire.removeGalleryPhoto(index);
                        },
                        clearAllGallery() {
                            this.galleryPreviews.forEach(url => URL.revokeObjectURL(url));
                            this.galleryPreviews = [];
                            const fileInput = document.getElementById('galleryPhotos');
                            if (fileInput) fileInput.value = '';
                        }
                    }"
                    @reset-gallery-photos.window="clearAllGallery()">
                        
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Add Gallery Photos<span class="text-red-500">*</span>
                        </label>

                        <!-- Upload Box -->
                        <div 
                            @click="document.getElementById('galleryPhotos').click()"
                            class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                               cursor-pointer hover:border-blue-400 transition-colors bg-gray-50">
                            <div class="text-center p-6">
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-images text-blue-500 text-2xl"></i>
                                </div>
                                <p class="text-gray-700 font-medium mb-1">Upload Photo</p>
                                <p class="text-sm text-gray-500 mb-3">or drag and drop</p>
                            </div>
                        </div>

                        <input type="file" 
                               id="galleryPhotos" 
                               wire:model="gallery_photos" 
                               class="hidden"
                               accept="image/*"
                               multiple
                               @change="handleGalleryFiles($event)">

                        <!-- Existing Gallery -->
                        @if(!empty($existing_gallery))
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach($existing_gallery as $index => $path)
                                    <div class="relative group">
                                        <div class="w-14 h-14 bg-white rounded-lg border-2 border-gray-200 overflow-hidden">
                                            <img src="{{ asset('storage/' . $path) }}" class="w-full h-full object-cover" alt="">
                                        </div>
                                        <button 
                                            type="button"
                                            wire:click="removeExistingGallery({{ $index }})"
                                            class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition shadow-md">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Gallery Preview Icons Below Upload -->
                        <div class="flex flex-wrap gap-2 mt-3" x-show="galleryPreviews.length > 0" x-cloak>
                            <template x-for="(preview, index) in galleryPreviews" :key="index">
                                <div class="relative group">
                                    <div class="w-14 h-14 bg-white rounded-lg border-2 border-gray-200 overflow-hidden">
                                        <img :src="preview" class="w-full h-full object-cover" alt="">
                                    </div>
                                    <button 
                                        type="button"
                                        @click.stop="removeGalleryImage(index)"
                                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition shadow-md">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                            
                            <!-- Add More Button -->
                            <button 
                                type="button"
                                @click="document.getElementById('galleryPhotos').click()"
                                class="w-14 h-14 border-2 border-dashed border-blue-400 rounded-lg flex items-center justify-center text-blue-600 hover:bg-blue-50 transition-colors">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        @error('gallery_photos.*')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Address line 1 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Address line 1<span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            wire:model="address_line_1"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('address_line_1')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address line 2 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Address line 2
                        </label>
                        <input 
                            type="text" 
                            wire:model="address_line_2"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('address_line_2')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- City -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            City
                        </label>
                        <input 
                            type="text" 
                            wire:model="city"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                        @error('city')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Status
                        </label>
                        <label class="inline-flex items-center gap-3 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="is_active"
                                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                            >
                            <span class="text-gray-700">{{ $is_active ? 'Active' : 'Inactive' }}</span>
                        </label>
                    </div>

                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <button 
                    type="button"
                    wire:click="cancel"
                    class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-8 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>
                        <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                    </span>
                </button>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
function pillbox({ options, selected }) {
    return {
        options: options,
        selected: selected,

        toggleOption(id) {
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(i => i !== id);
            } else {
                this.selected.push(id);
            }
        },

        remove(id) {
            this.selected = this.selected.filter(i => i !== id);
        },

        getLabel(id) {
            const opt = this.options.find(o => o.id == id);
            return opt ? opt.name : '';
        }
    }
}
</script>
@endpush
