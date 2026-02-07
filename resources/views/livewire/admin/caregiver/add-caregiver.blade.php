<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-800">Add New Caregiver</h2>
        </div>

        <!-- Form -->
        <form wire:submit.prevent="save" class="p-8">
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
                    <div x-data="{ newQual: '' }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Qualification
                        </label>
                        <input 
                            type="text" 
                            x-model="newQual"
                            placeholder="ANM,GNM, B.Sc Nursing etc ( Can add Multiple)"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            @keydown.enter.prevent="if(newQual.trim()) { $wire.addQualification(newQual); newQual = ''; }"
                        >
                        
                        <!-- Qualification Tags -->
                        @if(!empty($qualification))
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach($qualification as $index => $qual)
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-md text-sm border border-blue-200">
                                        {{ $qual }}
                                        <button 
                                            type="button"
                                            wire:click="removeQualification({{ $index }})"
                                            class="text-blue-500 hover:text-blue-700"
                                        >
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        
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
                        handleFileChange(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.previewUrl = URL.createObjectURL(file);
                            }
                        },
                        clearPreview() {
                            if (this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                            }
                            this.previewUrl = null;
                            const fileInput = document.getElementById('profilePhoto');
                            if (fileInput) fileInput.value = '';
                            $wire.removeProfilePhoto();
                        }
                    }"
                    @reset-profile-photo.window="
                        if (previewUrl) {
                            URL.revokeObjectURL(previewUrl);
                        }
                        previewUrl = null;
                        const fileInput = document.getElementById('profilePhoto');
                        if (fileInput) fileInput.value = '';
                    ">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Profile Photo<span class="text-red-500">*</span>
                        </label>

                        <!-- Upload Box -->
                        <div onclick="document.getElementById('profilePhoto').click()"
                            class="upload-wrapper">
                            
                            <div class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                   cursor-pointer hover:border-blue-400 transition-colors bg-gray-50"
                                x-show="!previewUrl">
                                <div class="text-center p-6">
                                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-image text-blue-500 text-2xl"></i>
                                    </div>
                                    <p class="text-gray-700 font-medium mb-1">Upload Photo</p>
                                    <p class="text-sm text-gray-500 mb-3">or drag and drop</p>
                                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors">
                                        <i class="fas fa-camera"></i>
                                        <span>Take a photo</span>
                                    </button>
                                </div>
                            </div>

                            <input type="file" 
                                   id="profilePhoto" 
                                   wire:model="profile_photo" 
                                   class="hidden"
                                   accept="image/*"
                                   @change="handleFileChange($event)">

                            <!-- Preview Box -->
                            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                                 x-show="previewUrl"
                                 x-cloak>
                                <img :src="previewUrl" class="preview-img" alt="Profile Preview">
                                <button type="button"
                                    @click.stop="clearPreview()"
                                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                        rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
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
                        <div onclick="document.getElementById('galleryPhotos').click()"
                            class="upload-wrapper">
                            
                            <div class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                   cursor-pointer hover:border-blue-400 transition-colors bg-gray-50">
                                <div class="text-center p-6">
                                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-images text-blue-500 text-2xl"></i>
                                    </div>
                                    <p class="text-gray-700 font-medium mb-1">Upload Photo</p>
                                    <p class="text-sm text-gray-500 mb-3">or drag and drop</p>
                                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors">
                                        <i class="fas fa-camera"></i>
                                        <span>Take a photo</span>
                                    </button>
                                </div>
                            </div>

                            <input type="file" 
                                   id="galleryPhotos" 
                                   wire:model="gallery_photos" 
                                   class="hidden"
                                   accept="image/*"
                                   multiple
                                   @change="handleGalleryFiles($event)">
                        </div>

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
                                onclick="document.getElementById('galleryPhotos').click()"
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
                            Address line 1
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