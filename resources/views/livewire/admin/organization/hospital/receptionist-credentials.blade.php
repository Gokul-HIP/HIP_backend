<div class="space-y-6"  x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-hos.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <style>
        ui-modal#delete-org dialog {
        max-width: 600px !important;
    }
    </style>

    <!-- OVERVIEW -->
    <div class="">
        <h2 class="text-lg font-semibold mb-4">Receptionist List</h2>
    </div>

    <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md p-6">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">

                <!-- Search -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input type="text"
                        placeholder="Search admin email..."
                        class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        wire:model.live.debounce.300ms="search" />
                </div>

            </div>

            <!-- Add Organization -->
            <div class="ml-auto flex-shrink-0">
                <button 
                    wire:click="openAddModal"
                    variant="primary" 
                    class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" 
                    style="background:#0da2e7;">
                    <i class="fa-solid fa-plus w-4 mr-2 text-white"></i>
                    <span class="hidden sm:inline text-white">Add Receptionist</span>
                </button>
            </div>

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Admin Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Mobile Number</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($users as $hos)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            {{ $hos->first_name ? $hos->first_name . ' ' . $hos->last_name : $hos->email }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            {{ $hos->email }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $hos->mobile_num ? $hos->mobile_num : '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            Receptionist
                        </td>

                        <!-- ACTION MENU -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $hos->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                </button>

                                <div id="menu-{{ $hos->id }}"
                                    class="action-menu hidden bg-white border rounded-lg shadow-lg">

                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <button
                                                type="button"
                                                onclick="closeAllActionMenus()"
                                                wire:click="edit('{{ $hos->id }}')"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i>
                                                Edit
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                type="button"
                                                onclick="closeAllActionMenus()"
                                                wire:click="delete('{{ $hos->id }}')"
                                                class="inline-flex items-center w-full p-2 hover:bg-red-50 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i>
                                                Delete
                                            </button>
                                        </li>

                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900">No admin users found</p>
                            <p class="text-sm text-gray-600">Start by adding your first cashier admin</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{-- {{ $users->links() }} --}}
        </div>

    </div>

    <flux:modal name="delete-receptionist" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>

                <!-- Close Icon -->
                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <!-- Title -->
                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Receptionist?
                </h2>

                <!-- Description -->
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    You're about to delete this cashier admin.<br>
                    This action cannot be reversed.
                </p>

                <!-- Buttons -->
                <div class="flex justify-end gap-4">
                    <flux:button  variant="ghost"
                        wire:click="closeModal"
                        class="text-sm font-medium text-black hover:text-gray-900">
                        <i class="fa-solid fa-times mr-2 text-black"></i>
                        <span class="hidden sm:inline text-black">Cancel</span>
                        <span class="sm:hidden text-black">Cancel</span>
                    </flux:button>

                    <button
                        type="button"
                        wire:click="destroy"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Delete Receptionist
                    </button>
                </div>

            </div>
        </div>
    </flux:modal>

    <!-- Add Receptionist Modal -->
    <flux:modal name="add-receptionist-user" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Add Receptionist
                </h2>
                <!-- Close Icon -->
                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <!-- Profile Image -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Profile Image (Optional)</label>
                    
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
                            const fileInput = document.getElementById('profileImageAdd');
                            if (fileInput) fileInput.value = '';
                            $wire.removeImage();
                        }
                    }"
                    @reset-file-input.window="
                        if (previewUrl) {
                            URL.revokeObjectURL(previewUrl);
                        }
                        previewUrl = null;
                        const fileInput = document.getElementById('profileImageAdd');
                        if (fileInput) fileInput.value = '';
                    ">
                        <!-- Image Preview -->
                        <div class="mb-3 flex items-center gap-3">
                            <div class="relative">
                                @if($profile_image)
                                    <img src="{{ $profile_image->temporaryUrl() }}" alt="Preview" class="w-24 h-24 rounded-full object-cover border-2 border-gray-300 shadow-sm">
                                    <button 
                                        type="button"
                                        wire:click="removeImage"
                                        @click="clearPreview()"
                                        class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-lg transition-all duration-200 hover:scale-110">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                @else
                                    <div class="w-24 h-24 rounded-full border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-400 text-3xl"></i>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- File Upload Area -->
                        <label for="profileImageAdd" class="relative cursor-pointer">
                            <div class="flex items-center justify-center w-full px-6 py-4 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 hover:border-[#0da2e7] transition-all duration-200 group">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2 group-hover:text-[#0da2e7] transition-colors"></i>
                                    <p class="text-sm text-gray-600 font-medium">
                                        <span class="text-[#0da2e7] font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG, JPEG up to 2MB</p>
                                </div>
                            </div>
                            <input 
                                type="file" 
                                id="profileImageAdd"
                                wire:model.live="profile_image" 
                                @change="handleFileChange($event)"
                                accept="image/*" 
                                class="hidden">
                        </label>
                    </div>
                    
                    @error('profile_image')
                        <span class="text-red-500 text-sm block mt-2">{{ $message }}</span>
                    @enderror
                </div>

                <!-- First Name and Last Name -->
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <input type="text" wire:model="first_name" class="w-full px-4 py-2 rounded-lg border glass-input" placeholder="First Name (Optional)">
                        @error('first_name')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <input type="text" wire:model="last_name" class="w-full px-4 py-2 rounded-lg border glass-input" placeholder="Last Name (Optional)">
                        @error('last_name')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Email -->
                <input type="email" wire:model="email" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Email *">
                @error('email')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <!-- Mobile Number -->
                <input type="text" wire:model="mobile_number" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Mobile Number (Optional)">
                @error('mobile_number')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <!-- Gender and Date of Birth -->
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <select wire:model="gender" class="w-full px-4 py-2 rounded-lg border glass-input">
                            <option value="">Select Gender (Optional)</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        @error('gender')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <input type="date" wire:model="dob" class="w-full px-4 py-2 rounded-lg border glass-input" placeholder="Date of Birth (Optional)">
                        @error('dob')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Password -->
                <input type="password" wire:model="password" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Password *">
                @error('password')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <input type="password" wire:model="password_confirmation" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Confirm Password *">
                @error('password_confirmation')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <!-- Buttons -->
                <div class="flex justify-end gap-4 mt-4">
                    <flux:button  variant="ghost"
                        wire:click="closeModal"
                        class="text-sm font-medium text-black hover:text-gray-900">
                        <i class="fa-solid fa-times mr-2 text-black"></i>
                        <span class="hidden sm:inline text-black">Cancel</span>
                        <span class="sm:hidden text-black">Cancel</span>
                    </flux:button>

                    <button
                        type="button"
                        wire:click="createUser"
                        wire:loading.attr="disabled"
                        class="bg-[#0da2e7] hover:bg-[#0da2e7]/80 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                        <span wire:loading.remove wire:target="createUser">Add Receptionist</span>
                        <span wire:loading wire:target="createUser">Adding...</span>
                    </button>
                </div>

            </div>
        </div>
    </flux:modal>

    <!-- Edit Receptionist Modal -->
    <flux:modal name="edit-receptionist-user" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Edit Receptionist
                </h2>
                <!-- Close Icon -->
                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <!-- Profile Image -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Profile Image (Optional)</label>
                    
                    <div x-data="{ 
                        previewUrl: null,
                        showExisting: @js($old_profile_image ? true : false),
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
                            const fileInput = document.getElementById('profileImageEdit');
                            if (fileInput) fileInput.value = '';
                            $wire.removeImage();
                        },
                        removeExisting() {
                            this.showExisting = false;
                            $wire.removeOldImage();
                        }
                    }"
                    @reset-file-input.window="
                        if (previewUrl) {
                            URL.revokeObjectURL(previewUrl);
                        }
                        previewUrl = null;
                        const fileInput = document.getElementById('profileImageEdit');
                        if (fileInput) fileInput.value = '';
                    ">
                        <!-- Image Preview -->
                        <div class="mb-3 flex items-center gap-3">
                            <div class="relative">
                                @if($profile_image)
                                    <img src="{{ $profile_image->temporaryUrl() }}" alt="Preview" class="w-24 h-24 rounded-full object-cover border-2 border-gray-300 shadow-sm">
                                    <button 
                                        type="button"
                                        wire:click="removeImage"
                                        @click="clearPreview()"
                                        class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-lg transition-all duration-200 hover:scale-110">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                @elseif($old_profile_image)
                                    <img src="{{ asset('storage/users/' . $old_profile_image) }}" alt="Current" class="w-24 h-24 rounded-full object-cover border-2 border-gray-300 shadow-sm">
                                    <button 
                                        type="button"
                                        wire:click="removeOldImage"
                                        @click="removeExisting()"
                                        class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-lg transition-all duration-200 hover:scale-110">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                @else
                                    <div class="w-24 h-24 rounded-full border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-400 text-3xl"></i>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- File Upload Area -->
                        <label for="profileImageEdit" class="relative cursor-pointer">
                            <div class="flex items-center justify-center w-full px-6 py-4 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 hover:border-[#0da2e7] transition-all duration-200 group">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2 group-hover:text-[#0da2e7] transition-colors"></i>
                                    <p class="text-sm text-gray-600 font-medium">
                                        <span class="text-[#0da2e7] font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG, JPEG up to 2MB</p>
                                </div>
                            </div>
                            <input 
                                type="file" 
                                id="profileImageEdit"
                                wire:model.live="profile_image" 
                                @change="handleFileChange($event)"
                                accept="image/*" 
                                class="hidden">
                        </label>
                    </div>
                    
                    @error('profile_image')
                        <span class="text-red-500 text-sm block mt-2">{{ $message }}</span>
                    @enderror
                </div>

                <!-- First Name and Last Name -->
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <input type="text" wire:model="first_name" class="w-full px-4 py-2 rounded-lg border glass-input" placeholder="First Name (Optional)">
                        @error('first_name')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <input type="text" wire:model="last_name" class="w-full px-4 py-2 rounded-lg border glass-input" placeholder="Last Name (Optional)">
                        @error('last_name')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Email -->
                <input type="email" wire:model="email" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Email *">
                @error('email')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <!-- Mobile Number -->
                <input type="text" wire:model="mobile_number" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Mobile Number (Optional)">
                @error('mobile_number')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <!-- Gender and Date of Birth -->
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <select wire:model="gender" class="w-full px-4 py-2 rounded-lg border glass-input">
                            <option value="">Select Gender (Optional)</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        @error('gender')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <input type="date" wire:model="dob" class="w-full px-4 py-2 rounded-lg border glass-input" placeholder="Date of Birth (Optional)">
                        @error('dob')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Password -->
                <input type="password" wire:model="password" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Password (leave blank to keep current)">
                @error('password')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <input type="password" wire:model="password_confirmation" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Confirm Password">
                @error('password_confirmation')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror

                <!-- Buttons -->
                <div class="flex justify-end gap-4 mt-4">
                    <flux:button  variant="ghost"
                        wire:click="closeModal"
                        class="text-sm font-medium text-black hover:text-gray-900">
                        <i class="fa-solid fa-times mr-2 text-black"></i>
                        <span class="hidden sm:inline text-black">Cancel</span>
                        <span class="sm:hidden text-black">Cancel</span>
                    </flux:button>

                    <button
                        type="button"
                        wire:click="updateUser"
                        wire:loading.attr="disabled"
                        class="bg-[#0da2e7] hover:bg-[#0da2e7]/80 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                        <span wire:loading.remove wire:target="updateUser">Update Receptionist</span>
                        <span wire:loading wire:target="updateUser">Updating...</span>
                    </button>
                </div>

            </div>
        </div>
    </flux:modal>

</div>
