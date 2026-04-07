<flux:modal name="add-doctor" class="p-0" wire:close="closeModal">
    <div x-data="{ modalReady: false }" 
         x-init="
            $el.closest('dialog').addEventListener('click', (e) => {
                if (e.target === e.currentTarget && modalReady) {
                    $wire.closeModal();
                }
            });
         "
         @modal-show.window="
            if ($event.detail.name === 'add-doctor') {
                modalReady = false;
                $wire.resetInput().then(() => {
                    setTimeout(() => modalReady = true, 300);
                });
            }
         ">
            
        <button type="button"
            wire:click="closeModal"
            class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-50 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

            <div class="relative max-w-5xl mx-auto" @click.stop>

        <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Add New Doctor</h1>

        <form wire:submit.prevent="addDoctorProfile" enctype="multipart/form-data">
            <div class="grid grid-cols-2 gap-10">
                
                <!-- LEFT COLUMN -->
                <div class="space-y-6">
                    
                    <!-- Doctor Name -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Doctor Name*</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 rounded border" placeholder="Enter doctor name">
                        @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Gender -->
                    <div x-data="genderSelect({value: @entangle('gender')})" class="relative">
                        <label class="block text-sm font-medium mb-2">Gender</label>
                        <button type="button" @click="toggle()" 
                        class="w-full flex items-center justify-between px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">
                            <span x-text="value || 'Select gender'"></span>
                            <span class="pointer-events-none"><i  class="fa-solid fa-chevron-down w-2 h-2"></i></span>
                        </button>
                        <div x-show="open" x-transition @click.away="close()" class="absolute left-0 w-full bg-white border rounded-lg mt-2 shadow-lg z-50">
                            <template x-for="item in options" :key="item">
                                <button type="button" @click="select(item)" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 border-b last:border-0" x-text="item"></button>
                            </template>
                        </div>
                        @error('gender')<div class="mt-1 text-red-500 text-sm">{{ $message }}</div>@enderror
                    </div>

                    {{-- Organization/Hospital selection hidden as requested --}}
                    {{--
                    <!-- Organization -->
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">Organization</label>
                        <button type="button" onclick="toggleDropdown('orgMenu', this)" 
                        class="filter-btn w-full flex items-center justify-between px-4 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-[#0da2e7]/40 
                        focus:border-[#0da2e7] {{ $organization_id ? 'border-[#0da2e7]' : 'border-gray-300' }}">
                            <span class="selected-text">
                                @if($organization_id)
                                    {{ $organizations->firstWhere('id', $organization_id)->name }}
                                @else
                                    Select Organization
                                @endif
                            </span>
                            <i class="fa-solid fa-chevron-down w-4 h-4"></i>
                        </button>
                        <div id="orgMenu" class="filter-dropdown hidden absolute left-0 w-full bg-white border mt-2 rounded-lg shadow-lg z-20" style="min-width: 100%; width: auto;">
                            @foreach ($organizations as $option)
                            <button type="button" wire:click="selectOrganization('{{ $option->id }}')"
                                class="w-full px-4 py-2 text-sm text-left hover:bg-gray-100 border-b last:border-b-0">{{ $option->name }}</button>
                            @endforeach
                        </div>
                        @error('organization_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Hospital -->
                    <div class="relative"
                        x-data="hospitalMultiSelect({
                            organizationId: @entangle('organization_id'),
                            hospitalIds: @entangle('hospital_ids'),
                            hospitals: @entangle('hospitals')
                        })"
                        wire:key="hospital-dropdown-{{ $organization_id }}">
                        <label class="block text-sm font-medium mb-2">Hospital</label>

                        <button type="button"
                            @click="toggleDropdown()"
                            :disabled="!organizationId || hospitals.length === 0"
                            class="filter-btn w-full flex justify-between px-4 py-2 border rounded-lg"
                            :class="{
                                'opacity-50 cursor-not-allowed': !organizationId || hospitals.length === 0
                            }">
                            <span x-text="getDisplayText()"></span>
                            <i class="fa-solid fa-chevron-down w-4 h-4"></i>
                        </button>

                        <div x-show="open" x-transition @click.away="open=false"
                            class="absolute w-full bg-white border mt-2 rounded shadow max-h-60 overflow-y-auto z-20">

                            <template x-for="hospital in hospitals" :key="hospital.id">
                                <button type="button"
                                    @click="toggleHospital(hospital.id)"
                                    class="w-full px-4 py-2 flex justify-between items-center hover:bg-gray-100 border-b">
                                    <span x-text="hospital.name"></span>
                                    <template x-if="hospitalIds.includes(hospital.id)">
                                        <i class="fa-solid fa-check text-blue-500"></i>
                                    </template>
                                </button>
                            </template>
                        </div>

                        @error('hospital_ids')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    --}}

                    <!-- Mobile Number -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile Number</label>
                        <input type="text" wire:model="mobile_number" class="w-full px-4 py-2 rounded border" placeholder="Enter mobile number">
                        @error('mobile_number')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Working Since -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Working since</label>
                        <input type="text" wire:model="working_since" class="w-full px-4 py-2 rounded border" placeholder="Enter Year eg 2015">
                        @error('working_since')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" wire:model="email" class="w-full px-4 py-2 rounded border" placeholder="doctor.name@hospital.com">
                        @error('email')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    
                    <!-- Qualification -->
                    <div x-data="pillbox({
                            options: @js($qualificationOptions),
                            selected: @entangle('qualifications')
                        })"
                        class="relative">

                        <label class="block text-sm font-medium mb-1">Qualification</label>

                        <!-- Pills Input -->
                        <div
                            class="min-h-[44px] flex flex-wrap items-center gap-2 px-3 py-2 border rounded-lg bg-white mb-3">
                            <template x-for="id in selected" :key="id">
                                <span
                                    class="flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs"
                                >
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

                        @error('qualifications')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <!-- RIGHT COLUMN -->
                <div class="space-y-6">

                    <!-- Publications -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Publications</label>
                        <input type="text" wire:model="publications" class="w-full px-4 py-2 rounded border" placeholder="Title - Journal - Year">
                        @error('publications')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Achievements -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Achievements</label>
                        <input type="text" wire:model="achievements" class="w-full px-4 py-2 rounded border" placeholder="Award name/ Fellowship">
                        @error('achievements')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Photo Upload -->
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
                            const fileInput = document.getElementById('doctorImage');
                            if (fileInput) fileInput.value = '';
                            $wire.removeImage();
                        }
                    }"
                    @reset-file-input.window="
                        if (previewUrl) {
                            URL.revokeObjectURL(previewUrl);
                        }
                        previewUrl = null;
                        const fileInput = document.getElementById('doctorImage');
                        if (fileInput) fileInput.value = '';
                    ">
                        <label class="block text-sm font-medium mb-2">Upload Doctor Image</label>

                        <!-- Upload Box -->
                        <div onclick="document.getElementById('doctorImage').click()"
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
                                   id="doctorImage" 
                                   wire:model="doctor_image" 
                                   class="hidden"
                                   accept="image/*"
                                   @change="handleFileChange($event)">
                        </div>

                        @error('doctor_image')
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

                    <!-- Specialties -->
                    {{-- <div> --}}
                        {{-- <label class="block text-sm font-medium mb-1">Specialties*</label>
                        <div class="border rounded-lg h-48 overflow-y-auto p-3 space-y-2">
                            @foreach($speciality_data as $spec)
                            <label class="flex items-center gap-3 text-sm">
                                <input type="checkbox" wire:model="speciality" value="{{ $spec['id'] }}">
                                <span>{{ $spec['name'] }}</span>
                            </label>
                            @endforeach
                        </div> --}}

                        {{-- <button type="button" wire:click="addCustomSpecialty" class="w-full mt-3 py-2 border rounded text-blue-600 flex items-center justify-center gap-2">
                            <i class="fa-regular fa-plus w-3 h-3"></i>Add Custom Specialty
                        </button> --}}

                        {{-- @error('speciality')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror --}}
                    {{-- </div> --}}

                    <div x-data="pillbox({
                        options: @js($speciality_data),
                        selected: @entangle('speciality')
                        })"
                        class="relative">

                        <label class="block text-sm font-medium mb-1">Specialties</label>

                        <!-- Pills Input -->
                        <div
                            class="min-h-[44px] flex flex-wrap items-center gap-2 px-3 py-2 border rounded-lg bg-white mb-3">
                            <template x-for="id in selected" :key="id">
                                <span
                                    class="flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs"
                                >
                                    <span x-text="getLabel(id)"></span>
                                    <button type="button" @click="remove(id)">✕</button>
                                </span>
                            </template>

                            <span x-show="selected.length === 0" class="text-gray-400 text-sm">
                                Select specialties...
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
                                    No specialties found
                                </p>
                            </template>
                        </div>

                        @error('speciality')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- About Doctor -->
                    <div>
                        <label class="block text-sm font-medium mb-1">About Doctor</label>
                        <textarea wire:model="about_doctor" class="w-full px-4 py-2 rounded border" placeholder="Enter about doctor"></textarea>
                        @error('about_doctor')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Status Toggle -->
                    <div class="pt-2">
                        <label class="block text-sm font-medium mb-2">Status</label>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700">Inactive</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="status" class="sr-only">
                                <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                    <span class="dot w-5 h-5 bg-white rounded-full transition-all {{ $status ? 'translate-x-6' : 'translate-x-0' }}"></span>
                                </span>
                            </label>
                            <span class="text-sm text-gray-800">Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 border-t">
                    <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" 
                    style="background:#f14336; color:#ffffff !important;" wire:click="closeModal" type="button">
                        <i class="fa-solid fa-times mr-2 text-white"></i>
                        <span class="hidden sm:inline" style="color:#ffffff !important;">Cancel</span>
                        <span class="sm:hidden" style="color:#ffffff !important;">Cancel</span>
                    </flux:button>

                    <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280" wire:click='resetInput' type="button">
                        <i class="fa-solid fa-rotate-right mr-2" style="color:#ffffff !important;"></i>
                        <span class="hidden sm:inline" style="color:#ffffff !important;">Reset</span>
                        <span class="sm:hidden" style="color:#ffffff !important;">Reset</span>
                    </flux:button>

                    <flux:button variant="primary" type="submit" 
                                 class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" 
                                 style="background:#0da2e7; color:#ffffff !important;">
                        <i class="fa-solid fa-check mr-2" style="color:#ffffff !important;"></i>
                        <span class="hidden sm:inline" style="color:#ffffff !important;">Save Doctor</span>
                        <span class="sm:hidden" style="color:#ffffff !important;">Save</span>
                    </flux:button>
            </div>
        </form>
    </div>

</div>

</flux:modal>

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
    function genderSelect({ value }) {
        return {
            open: false,
            value: value,
            options: ["Male", "Female", "Other"],

            toggle() {
                this.open = !this.open;
                this.$nextTick(() => lucide.createIcons());
            },

            close() {
                this.open = false;
                this.$nextTick(() => lucide.createIcons());
            },

            select(item) {
                this.value = item;
                this.open = false;
                this.$wire.set('gender', item);
                this.$nextTick(() => lucide.createIcons());
            }
        }
    }

    function toggleDropdown(menuId, button) {
        if (button && button.hasAttribute('disabled')) return;

        const menu = document.getElementById(menuId);
        if (!menu) return;

        document.querySelectorAll('.filter-dropdown').forEach(el => {
            if (el.id !== menuId) el.classList.add('hidden');
        });

        menu.classList.toggle('hidden');
        setTimeout(() => lucide.createIcons(), 10);
    }

    function hospitalMultiSelect({ organizationId, hospitalIds, hospitals }) {
        return {
            open: false,
            organizationId,
            hospitalIds,
            hospitals,

            toggleDropdown() {
                if (this.organizationId && this.hospitals.length) {
                    this.open = !this.open;
                }
            },

            toggleHospital(id) {
                if (this.hospitalIds.includes(id)) {
                    this.hospitalIds = this.hospitalIds.filter(h => h !== id);
                } else {
                    this.hospitalIds.push(id);
                }

                this.$wire.set('hospital_ids', this.hospitalIds);
            },

            getDisplayText() {
                if (!this.organizationId) {
                    return 'Please select an organization first';
                }

                if (this.hospitalIds.length) {
                    return this.hospitals
                        .filter(h => this.hospitalIds.includes(h.id))
                        .map(h => h.name)
                        .join(', ');
                }

                return this.hospitals.length
                    ? 'Select Hospitals'
                    : 'No hospitals available';
            }
        }
    }

    document.addEventListener('livewire:init', () => {
        Livewire.on('organization-selected', () => {
            document.getElementById('orgMenu')?.classList.add('hidden');
            setTimeout(() => lucide.createIcons(), 100);
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush