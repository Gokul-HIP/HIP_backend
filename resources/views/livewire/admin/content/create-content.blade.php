<div x-data="{ 
    closeAllDropdowns() {
        // Close all dropdowns by dispatching a global event
        window.dispatchEvent(new CustomEvent('close-all-dropdowns'));
    }
}"
@close-all-dropdowns.window="
    // This will be handled by individual dropdowns
">
    <style>
        [x-cloak] { display: none !important; }
        .upload-box {
            border: 2px dashed #b3e4f8;
            border-radius: 12px;
            min-height: 150px;
            background: #f0faff;
            transition: all 0.2s;
        }
        .upload-box:hover {
            border-color: var(--button-hover);
            background: rgba(13,162,231,0.06);
        }
        .preview-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
        /* Toggle switch */
        .toggle-track {
            width: 44px; height: 24px;
            background: #e2e8f0;
            border-radius: 999px;
            position: relative;
            transition: background 0.2s;
            cursor: pointer;
        }
        .toggle-track.on { background: var(--button-color); }
        .toggle-thumb {
            width: 18px; height: 18px;
            background: #fff;
            border-radius: 50%;
            position: absolute;
            top: 3px; left: 3px;
            transition: transform 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        .toggle-track.on .toggle-thumb { transform: translateX(20px); }
        .tinymce-content-description .tox-tinymce {
            min-height: 220px;
        }
        .tinymce-content-description .tox {
            border: none !important;
        }
    </style>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-sky-50/40 to-cyan-50/30 p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8">
            {{-- <div class="flex items-center gap-2 text-sm text-slate-400 mb-3">
                <i class="fa-solid fa-house text-xs"></i>
                <span>/</span><span>Content</span>
                <span>/</span><span class="text-slate-600 font-medium">Create</span>
            </div> --}}
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center"
                     style="background: linear-gradient(135deg, var(--button-color), var(--button-hover)); box-shadow: 0 8px 20px rgba(var(--button-hover),0.3);">
                    <i class="fa-solid fa-pen-nib text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight">Create New Content</h1>
                    <p class="text-slate-400 text-sm mt-0.5">Fill in the details below to publish your content</p>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="store" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

                <!-- ===== LEFT COLUMN (spans 3) ===== -->
                <div class="xl:col-span-3 space-y-5">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100">

                        <!-- Card Header -->
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                                 style="background: rgba(13,162,231,0.1);">
                                <i class="fa-solid fa-circle-info text-sm" style="color:var(--button-color);"></i>
                            </div>
                            <h2 class="font-semibold text-slate-700">Basic Information</h2>
                        </div>

                        <div class="p-6 space-y-5">

                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">
                                    Title <span class="text-rose-400">*</span>
                                </label>
                                <input type="text" wire:model="title"
                                    placeholder="Enter a compelling title..."
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 placeholder-slate-400 text-sm outline-none transition-all duration-200"
                                    onfocus="this.style.borderColor='#0DA2E7';this.style.boxShadow='0 0 0 3px rgba(13,162,231,0.15)';this.style.background='#fff';"
                                    onblur="this.style.borderColor='';this.style.boxShadow='';this.style.background='';">
                                @error('title')
                                    <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Description (TinyMCE; synced to Livewire `description`) -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Description</label>
                                <div
                                    wire:ignore
                                    class="tinymce-content-description rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm"
                                >
                                    <textarea
                                        id="content_description_editor"
                                        data-tinymce-content-description="1"
                                        rows="12"
                                        class="w-full min-h-[220px] px-4 py-3 text-sm text-slate-800 placeholder-slate-400"
                                        placeholder="Describe your content in detail...">{!! $description ?? '' !!}</textarea>
                                </div>
                                @error('description')
                                    <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Category & Speciality -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-2">
                                        Category <span class="text-rose-400">*</span>
                                    </label>
                                    <div x-data="{
                                            open: false, dropStyle: '',
                                            toggle() { 
                                                window.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                                                this.open=!this.open; 
                                                if(this.open) this.$nextTick(()=>this.calc()); 
                                            },
                                            calc() { const r=this.$refs.btn.getBoundingClientRect(); this.dropStyle=`position:fixed;z-index:9999;width:${r.width}px;left:${r.left}px;top:${r.bottom+4}px;`; }
                                        }"
                                        @close-all-dropdowns.window="if($event.target !== $el) open = false"
                                        @click.away="open=false" @scroll.window="open=false" @resize.window="open=false">
                                        <button type="button" x-ref="btn" @click.stop="toggle()"
                                            class="w-full border rounded-xl px-4 py-3 bg-slate-50 flex justify-between items-center text-sm outline-none transition-all duration-200"
                                            :style="open ? 'border-color:#0DA2E7;box-shadow:0 0 0 3px rgba(13,162,231,0.15);background:#fff;' : 'border-color:var(--button-hover);'">
                                            <span>
                                                <span class="text-slate-400" x-show="!$wire.category">Select Category</span>
                                                <span x-show="$wire.category" class="font-medium" style="color:#0DA2E7;">{{ ucfirst($category ?? '') }}</span>
                                            </span>
                                            <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="{'rotate-180':open}"></i>
                                        </button>
                                        <template x-teleport="body">
                                            <div x-show="open" :style="dropStyle"
                                                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0 scale-95"
                                                class="bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto" style="position:fixed;z-index:9999;">
                                                <div class="p-2 space-y-0.5">
                                                    @foreach($categories as $cat)
                                                        <button type="button"
                                                            wire:click="$set('category','{{ $cat }}')" @click="open=false"
                                                            class="w-full text-left px-3 py-2.5 text-sm rounded-lg transition-colors duration-150 {{ $category===$cat ? 'font-medium' : 'text-slate-600' }}"
                                                            style="{{ $category===$cat ? 'background:rgba(13,162,231,0.1);color:#0DA2E7;' : '' }}"
                                                            onmouseover="{{ $category!==$cat ? 'this.style.background=\"rgba(13,162,231,0.07)\"' : '' }}"
                                                            onmouseout="{{ $category!==$cat ? 'this.style.background=\"\"' : '' }}">
                                                            {{ ucfirst($cat) }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    @error('category')
                                        <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Speciality -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-2">Speciality</label>
                                    <div x-data="{
                                            open: false, dropStyle: '',
                                            toggle() { 
                                                window.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                                                this.open=!this.open; 
                                                if(this.open) this.$nextTick(()=>this.calc()); 
                                            },
                                            calc() { const r=this.$refs.btn.getBoundingClientRect(); this.dropStyle=`position:fixed;z-index:9999;width:${r.width}px;left:${r.left}px;top:${r.bottom+4}px;`; }
                                        }"
                                        @close-all-dropdowns.window="if($event.target !== $el) open = false"
                                        @click.away="open=false" @scroll.window="open=false" @resize.window="open=false">
                                        <button type="button" x-ref="btn" @click.stop="toggle()"
                                            class="w-full border rounded-xl px-4 py-3 bg-slate-50 flex justify-between items-center text-sm outline-none transition-all duration-200"
                                            :style="open ? 'border-color:#0DA2E7;box-shadow:0 0 0 3px rgba(13,162,231,0.15);background:#fff;' : 'border-color:var(--button-hover);'">
                                            <span>
                                                <span class="text-slate-400" x-show="!$wire.speciality_id">Select Speciality</span>
                                                <span x-show="$wire.speciality_id" class="font-medium" style="color:#0DA2E7;">
                                                    @if($speciality_id){{ $specialities->firstWhere('id',$speciality_id)?->name ?? '' }}@endif
                                                </span>
                                            </span>
                                            <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="{'rotate-180':open}"></i>
                                        </button>
                                        <template x-teleport="body">
                                            <div x-show="open" :style="dropStyle"
                                                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0 scale-95"
                                                class="bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto" style="position:fixed;z-index:9999;">
                                                <div class="p-2 space-y-0.5">
                                                    @foreach($specialities as $speciality)
                                                        <button type="button"
                                                            wire:click="$set('speciality_id',{{ $speciality->id }})" @click="open=false"
                                                            class="w-full text-left px-3 py-2.5 text-sm rounded-lg transition-colors duration-150 {{ $speciality_id===$speciality->id ? 'font-medium' : 'text-slate-600' }}"
                                                            style="{{ $speciality_id===$speciality->id ? 'background:rgba(13,162,231,0.1);color:#0DA2E7;' : '' }}"
                                                            onmouseover="{{ $speciality_id!==$speciality->id ? 'this.style.background=\"rgba(13,162,231,0.07)\"' : '' }}"
                                                            onmouseout="{{ $speciality_id!==$speciality->id ? 'this.style.background=\"\"' : '' }}">
                                                            {{ $speciality->name }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    @error('speciality_id')
                                        <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Areas -->
                            <div x-data="areaPillbox({
                                    options: @js($areas->map(function($area) { return ['id' => $area->id, 'name' => $area->area . ' - ' . $area->city]; })->values()->all()),
                                    selected: @entangle('area_ids'),
                                    init() {
                                        this.$watch('open', (value) => {
                                            if (value) {
                                                window.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                                            }
                                        });
                                    }
                                })"
                                @close-all-dropdowns.window="if($event.target !== $el) open = false"
                                class="relative">
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Areas</label>

                                <!-- Combined Input Field with Dropdown -->
                                <div class="relative">
                                    <!-- Input Field with Pills -->
                                    <div 
                                        @click="toggleDropdown()"
                                        class="min-h-[44px] flex flex-wrap items-center gap-2 px-3 py-2 border rounded-xl bg-slate-50 cursor-pointer outline-none transition-all duration-200"
                                        style="border-color:#e2e8f0;"
                                        :style="open ? 'border-color:#0DA2E7;box-shadow:0 0 0 3px rgba(13,162,231,0.15);background:#fff;' : ''">
                                        <template x-for="id in selected" :key="id">
                                            <span class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                                                  style="background:rgba(13,162,231,0.1);color:var(--button-color);">
                                                <span x-text="getLabel(id)"></span>
                                                <button type="button" 
                                                        @click.stop="remove(id)" 
                                                        class="ml-1 hover:bg-[#0DA2E7] hover:text-white rounded-full p-0.5 transition-colors">
                                                    <i class="fas fa-times text-xs"></i>
                                                </button>
                                            </span>
                                        </template>

                                        <span x-show="selected.length === 0" class="text-gray-400 text-sm">
                                            Select areas...
                                        </span>
                                        
                                        <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200 ml-auto" :class="{'rotate-180': open}"></i>
                                    </div>
                                    
                                    <!-- Dropdown Menu -->
                                    <div x-show="open" 
                                         @click.away="open = false"
                                         x-transition
                                         class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto"
                                         style="border-color:#e2e8f0;">
                                        <div class="p-2 space-y-0.5">
                                            <template x-for="option in options" :key="option.id">
                                                <label class="flex items-center gap-3 p-2.5 rounded-lg cursor-pointer group transition-colors duration-150"
                                                       onmouseover="this.style.background='rgba(13,162,231,0.07)'" 
                                                       onmouseout="this.style.background=''">
                                                    <input type="checkbox"
                                                           :value="option.id"
                                                           @change="toggleOption(option.id)"
                                                           :checked="selected.includes(option.id)"
                                                           class="w-4 h-4 rounded border-slate-300"
                                                           style="accent-color:var(--button-color);">
                                                    <span class="text-sm text-slate-600" x-text="option.name"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                @error('area_ids')
                                    <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ===== RIGHT COLUMN (spans 2) ===== -->
                <div class="xl:col-span-2 space-y-5">

                    <!-- Association Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background:rgba(13,162,231,0.1);">
                                <i class="fa-solid fa-link text-sm" style="color:var(--button-color);"></i>
                            </div>
                            <h2 class="font-semibold text-slate-700">Association</h2>
                        </div>

                        <div class="p-6 space-y-5">

                            <!-- Hospital -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Hospital</label>
                                <div x-data="{
                                        open:false, dropStyle:'',
                                        toggle(){ 
                                            window.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                                            this.open=!this.open; 
                                            if(this.open) this.$nextTick(()=>this.calc()); 
                                        },
                                        calc(){ const r=this.$refs.btn.getBoundingClientRect(); this.dropStyle=`position:fixed;z-index:9999;width:${r.width}px;left:${r.left}px;top:${r.bottom+4}px;`; }
                                    }"
                                    @close-all-dropdowns.window="if($event.target !== $el) open = false"
                                    @click.away="open=false" @scroll.window="open=false" @resize.window="open=false">
                                    <button type="button" x-ref="btn" @click.stop="toggle()"
                                        class="w-full border rounded-xl px-4 py-3 bg-slate-50 flex justify-between items-center text-sm outline-none transition-all duration-200"
                                        :style="open ? 'border-color:#0DA2E7;box-shadow:0 0 0 3px rgba(13,162,231,0.15);background:#fff;' : 'border-color:var(--button-hover);'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-5 h-5 rounded-md flex items-center justify-center flex-shrink-0" style="background:rgba(13,162,231,0.1);">
                                                <i class="fa-solid fa-hospital text-xs" style="color:var(--button-color);"></i>
                                            </div>
                                            <span>
                                                <span class="text-slate-400" x-show="!$wire.hospital_id">Select Hospital</span>
                                                <span x-show="$wire.hospital_id" class="font-medium" style="color:var(--button-color);">
                                                    @if($hospital_id){{ $hospitals->firstWhere('id',$hospital_id)?->name ?? '' }}@endif
                                                </span>
                                            </span>
                                        </div>
                                        <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="{'rotate-180':open}"></i>
                                    </button>
                                    <template x-teleport="body">
                                        <div x-show="open" :style="dropStyle"
                                            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0 scale-95"
                                            class="bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto" style="position:fixed;z-index:9999;">
                                            <div class="p-2 space-y-0.5">
                                                @foreach($hospitals as $hospital)
                                                    <button type="button"
                                                        wire:click="$set('hospital_id',{{ $hospital->id }})" @click="open=false"
                                                        class="w-full text-left px-3 py-2.5 text-sm rounded-lg transition-colors duration-150 {{ $hospital_id===$hospital->id ? 'font-medium' : 'text-slate-600' }}"
                                                        style="{{ $hospital_id===$hospital->id ? 'background:rgba(13,162,231,0.1);color:#0DA2E7;' : '' }}"
                                                        onmouseover="{{ $hospital_id!==$hospital->id ? 'this.style.background=\"rgba(13,162,231,0.07)\"' : '' }}"
                                                        onmouseout="{{ $hospital_id!==$hospital->id ? 'this.style.background=\"\"' : '' }}">
                                                        {{ $hospital->name }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                @error('hospital_id')
                                    <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Doctor -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Doctor</label>
                                <div x-data="{
                                        open:false, dropStyle:'',
                                        toggle(){ 
                                            if(!$wire.hospital_id) return; 
                                            window.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                                            this.open=!this.open; 
                                            if(this.open) this.$nextTick(()=>this.calc()); 
                                        },
                                        calc(){ const r=this.$refs.btn.getBoundingClientRect(); this.dropStyle=`position:fixed;z-index:9999;width:${r.width}px;left:${r.left}px;top:${r.bottom+4}px;`; }
                                    }"
                                    @close-all-dropdowns.window="if($event.target !== $el) open = false"
                                    @click.away="open=false" @scroll.window="open=false" @resize.window="open=false">
                                    <button type="button" x-ref="btn" @click.stop="toggle()"
                                        class="w-full border rounded-xl px-4 py-3 bg-slate-50 flex justify-between items-center text-sm outline-none transition-all duration-200"
                                        :class="!$wire.hospital_id ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                                        :style="open ? 'border-color:#0DA2E7;box-shadow:0 0 0 3px rgba(13,162,231,0.15);background:#fff;' : 'border-color:var(--button-hover);'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-5 h-5 rounded-md flex items-center justify-center flex-shrink-0" style="background:rgba(13,162,231,0.1);">
                                                <i class="fa-solid fa-user-doctor text-xs" style="color:var(--button-color);"></i>
                                            </div>
                                            <span>
                                                @if(!$hospital_id)
                                                    <span class="text-slate-400">Select Hospital First</span>
                                                @elseif($doctor_id)
                                                    <span class="font-medium" style="color:#0DA2E7;">{{ $doctors->firstWhere('id',$doctor_id)?->name ?? 'Select Doctor' }}</span>
                                                @else
                                                    <span class="text-slate-400">Select Doctor</span>
                                                @endif
                                            </span>
                                        </div>
                                        <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="{'rotate-180':open}"></i>
                                    </button>
                                    <template x-teleport="body">
                                        <div x-show="open && $wire.hospital_id" :style="dropStyle"
                                            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0 scale-95"
                                            class="bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto" style="position:fixed;z-index:9999;">
                                            <div class="p-2 space-y-0.5">
                                                @if($hospital_id && count($doctors)>0)
                                                    @foreach($doctors as $doctor)
                                                        <button type="button"
                                                            wire:click="$set('doctor_id',{{ $doctor->id }})" @click="open=false"
                                                            class="w-full text-left px-3 py-2.5 text-sm rounded-lg transition-colors duration-150 {{ $doctor_id===$doctor->id ? 'font-medium' : 'text-slate-600' }}"
                                                            style="{{ $doctor_id===$doctor->id ? 'background:rgba(13,162,231,0.1);color:#0DA2E7;' : '' }}"
                                                            onmouseover="{{ $doctor_id!==$doctor->id ? 'this.style.background=\"rgba(13,162,231,0.07)\"' : '' }}"
                                                            onmouseout="{{ $doctor_id!==$doctor->id ? 'this.style.background=\"\"' : '' }}">
                                                            {{ $doctor->name }}
                                                        </button>
                                                    @endforeach
                                                @else
                                                    <div class="px-3 py-3 text-sm text-slate-400 text-center">No doctors available</div>
                                                @endif
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                @error('doctor_id')
                                    <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">
                                    Status <span class="text-rose-400">*</span>
                                </label>
                                <div x-data="{
                                        open:false, dropStyle:'',
                                        toggle(){ 
                                            window.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                                            this.open=!this.open; 
                                            if(this.open) this.$nextTick(()=>this.calc()); 
                                        },
                                        calc(){ const r=this.$refs.btn.getBoundingClientRect(); this.dropStyle=`position:fixed;z-index:9999;width:${r.width}px;left:${r.left}px;top:${r.bottom+4}px;`; }
                                    }"
                                    @close-all-dropdowns.window="if($event.target !== $el) open = false"
                                    @click.away="open=false" @scroll.window="open=false" @resize.window="open=false">
                                    <button type="button" x-ref="btn" @click.stop="toggle()"
                                        class="w-full border rounded-xl px-4 py-3 bg-slate-50 flex justify-between items-center text-sm outline-none transition-all duration-200"
                                        :style="open ? 'border-color:#0DA2E7;box-shadow:0 0 0 3px rgba(13,162,231,0.15);background:#fff;' : 'border-color:var(--button-hover);'">
                                        @php
                                            $sIcons  = ['active'=>'fa-circle-check','inactive'=>'fa-circle-xmark'];
                                            $sColors = ['active'=>'#10b981','inactive'=>'#f43f5e'];
                                        @endphp
                                        <span>
                                            @if($status && in_array($status, ['active', 'inactive']))
                                                <span class="flex items-center gap-2">
                                                    <i class="fa-solid {{ $sIcons[$status] ?? 'fa-circle' }} text-xs" style="color:{{ $sColors[$status] ?? '#0DA2E7' }};"></i>
                                                    <span class="font-medium capitalize" style="color:#0DA2E7;">{{ ucfirst($status) }}</span>
                                                </span>
                                            @else
                                                <span class="text-slate-400">Select Status</span>
                                            @endif
                                        </span>
                                        <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="{'rotate-180':open}"></i>
                                    </button>
                                    <template x-teleport="body">
                                        <div x-show="open" :style="dropStyle"
                                            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0 scale-95"
                                            class="bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden" style="position:fixed;z-index:9999;">
                                            <div class="p-2 space-y-0.5">
                                                @foreach(['active'=>['fa-circle-check','#10b981'],'inactive'=>['fa-circle-xmark','#f43f5e']] as $stat=>[$icon,$color])
                                                    <button type="button"
                                                        wire:click="$set('status','{{ $stat }}')" @click="open=false"
                                                        class="w-full text-left px-3 py-2.5 text-sm rounded-lg flex items-center gap-2.5 transition-colors duration-150 {{ $status===$stat ? 'font-medium' : 'text-slate-600' }}"
                                                        style="{{ $status===$stat ? 'background:rgba(13,162,231,0.1);color:#0DA2E7;' : '' }}"
                                                        onmouseover="{{ $status!==$stat ? 'this.style.background=\"rgba(13,162,231,0.07)\"' : '' }}"
                                                        onmouseout="{{ $status!==$stat ? 'this.style.background=\"\"' : '' }}">
                                                        <i class="fa-solid {{ $icon }} text-xs" style="color:{{ $color }};"></i>
                                                        <span class="capitalize">{{ ucfirst($stat) }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                @error('status')
                                    <p class="text-rose-500 text-xs mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Schedule Date & Time (Always Visible) -->
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Schedule Date & Time</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-1.5">Date</label>
                                        <input type="date" wire:model="schedule_date"
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-[#0DA2E7]/50 focus:border-[#0DA2E7] focus:bg-white
                                                   transition-all duration-200"
                                            min="{{ date('Y-m-d') }}">
                                        @error('schedule_date')
                                            <p class="text-rose-500 text-xs mt-1 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-1.5">Time</label>
                                        <input type="time" wire:model="schedule_time"
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-[#0DA2E7]/50 focus:border-[#0DA2E7] focus:bg-white
                                                   transition-all duration-200">
                                        @error('schedule_time')
                                            <p class="text-rose-500 text-xs mt-1 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Published Toggle -->
                            <div class="flex items-center justify-between p-3.5 rounded-xl border border-slate-100 bg-slate-50">
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Published</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Make content publicly visible</p>
                                </div>
                                <div x-data="{ on: @entangle('is_published') }"
                                     @click="on=!on"
                                     class="toggle-track" :class="on ? 'on' : ''">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Media Upload Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100"
                         x-data="{
                             previewUrl: null, fileType: null,
                             handleFileChange(e) {
                                 const f=e.target.files[0];
                                 if(f){ this.previewUrl=URL.createObjectURL(f); this.fileType=f.type.startsWith('video')?'video':'image'; }
                             },
                             clearPreview() {
                                 if(this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                                 this.previewUrl=null; this.fileType=null;
                                 const fi=document.getElementById('mediaFile');
                                 if(fi) fi.value='';
                                 $wire.call('removeMediaFile');
                             }
                         }"
                         @reset-file-input.window="if(previewUrl) URL.revokeObjectURL(previewUrl); previewUrl=null; fileType=null;">

                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background:rgba(13,162,231,0.1);">
                                <i class="fa-solid fa-photo-film text-sm" style="color:var(--button-color);"></i>
                            </div>
                            <h2 class="font-semibold text-slate-700">Media File</h2>
                        </div>

                        <div class="p-6">
                            <input type="file" id="mediaFile" wire:model="media_file"
                                accept="image/*,video/*" @change="handleFileChange($event)" class="hidden">

                            <!-- Empty State -->
                            <div x-show="!previewUrl">
                                <label for="mediaFile" class="upload-box flex flex-col items-center justify-center gap-3 w-full py-10 px-4 cursor-pointer block">
                                    <div class="w-14 h-14 rounded-2xl bg-white shadow-sm border border-slate-200 flex items-center justify-center transition-all duration-200">
                                        <i class="fa-solid fa-cloud-arrow-up text-2xl" style="color:var(--button-color);"></i>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-slate-600">Click to upload media</p>
                                        <p class="text-xs text-slate-400 mt-1">Images or videos — max 10MB</p>
                                    </div>
                                    <span class="text-xs text-slate-400 font-mono bg-white border border-slate-100 rounded-lg px-3 py-1">
                                        JPG · PNG · GIF · MP4 · MOV
                                    </span>
                                </label>
                            </div>

                            <!-- Loading -->
                            <div wire:loading wire:target="media_file" class="flex flex-col items-center justify-center py-10 gap-3">
                                <div class="w-8 h-8 rounded-full border-2 border-t-2 animate-spin"
                                     style="border-color:rgba(13,162,231,0.2);border-top-color:var(--button-color);"></div>
                                <p class="text-sm text-slate-500">Processing file...</p>
                            </div>

                            <!-- Preview -->
                            <div x-show="previewUrl" class="relative rounded-xl overflow-hidden bg-slate-900 aspect-video">
                                <img x-show="fileType==='image'" x-bind:src="previewUrl" alt="Preview" class="w-full h-full object-cover">
                                <video x-show="fileType==='video'" x-bind:src="previewUrl" controls class="w-full h-full object-cover"></video>
                                <button type="button" @click="clearPreview()"
                                    class="absolute top-2.5 right-2.5 w-8 h-8 bg-black/50 hover:bg-rose-500 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition-colors duration-150">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>

                            @error('media_file')
                                <p class="text-rose-500 text-xs mt-2 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <!-- ===== Action Footer ===== -->
            <div class="mt-6 bg-white rounded-2xl shadow-sm border border-slate-100 px-6 py-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-slate-300"></i>
                        Fields marked <span class="text-rose-400 font-bold mx-1">*</span> are required
                    </p>
                    <div class="flex items-center gap-3 w-full sm:w-auto">

                        <!-- Cancel -->
                        <a href="{{ route('admin.content-moderation.index') }}"
                            class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl
                                   border border-slate-200 bg-white text-slate-600 text-sm font-medium
                                   hover:bg-slate-50 hover:border-slate-300 transition-all duration-150">
                            <i class="fa-solid fa-xmark text-xs"></i> Cancel
                        </a>

                        <!-- Reset -->
                        <button type="button" wire:click="resetForm"
                            class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border text-sm font-medium transition-all duration-150"
                            style="border-color:var(--button-hover);background:#f0faff;color:var(--button-color);"
                            onmouseover="this.style.background='#e0f4fd';this.style.borderColor:var(--button-hover);;"
                            onmouseout="this.style.background='#f0faff';this.style.borderColor:var(--button-hover);;">
                            <i class="fa-solid fa-rotate-right text-xs"></i> Reset
                        </button>

                        <!-- Save as Draft -->
                        <button type="button" wire:click="saveAsDraft"
                            class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border text-sm font-medium transition-all duration-150"
                            style="border-color:var(--button-hover);background:#e0f4fd;color:var(--button-color);"
                            onmouseover="this.style.background='#cceef9';this.style.borderColor:var(--button-hover);;"
                            onmouseout="this.style.background='#e0f4fd';this.style.borderColor:var(--button-hover);;">
                            <i class="fa-solid fa-floppy-disk text-xs"></i>
                            <span class="hidden sm:inline">Save Draft</span>
                            <span class="sm:hidden">Draft</span>
                        </button>

                        <!-- Create Content -->
                        <button type="submit"
                            class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-150 active:scale-[0.98]"
                            style="background:var(--button-color);box-shadow:0 4px 14px rgba(13,162,231,0.35);"
                            onmouseover="this.style.background:var(--button-hover);this.style.boxShadow='0 6px 18px rgba(13,162,231,0.45)';"
                            onmouseout="this.style.background:var(--button-color);this.style.boxShadow='0 4px 14px rgba(13,162,231,0.35)';">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;">Create Content</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Create</span>
                        </button>

                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
    function areaPillbox({ options, selected }) {
        return {
            open: false,
            options: options,
            selected: selected,

            toggleDropdown() {
                this.open = !this.open;
            },

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

    function tinyMceImageUploadHandler(uploadUrl) {
        return (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then((response) => response.json())
                .then((result) => {
                    if (!result || typeof result.location !== 'string') {
                        reject('Invalid upload response');
                        return;
                    }
                    resolve(result.location);
                })
                .catch(() => reject('Image upload failed'));
        });
    }

    function initContentDescriptionEditor() {
        const textarea = document.getElementById('content_description_editor');
        if (!textarea || !window.tinymce) return;

        const root = textarea.closest('[wire\\:id]');
        if (!root) return;
        const componentId = root.getAttribute('wire:id');
        if (!componentId) return;

        if (window.tinymce.get(textarea.id)) {
            return;
        }

        const uploadUrl = '{{ route('admin.tinymce.upload') }}';

        window.tinymce.init({
            selector: `#${textarea.id}`,
            menubar: false,
            branding: false,
            license_key: 'gpl',
            height: 220,
            plugins: 'lists link image code table',
            toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image | code',
            image_title: true,
            automatic_uploads: true,
            images_upload_handler: tinyMceImageUploadHandler(uploadUrl),
            convert_urls: false,
            relative_urls: false,
            setup(editor) {
                const sync = () => {
                    const wire = window.Livewire?.find ? window.Livewire.find(componentId) : null;
                    if (wire) {
                        wire.set('description', editor.getContent(), false);
                    }
                };

                editor.on('init', sync);
                editor.on('change keyup blur undo redo', sync);

                const form = textarea.closest('form');
                if (form) {
                    form.addEventListener('submit', sync, { capture: true });
                }
            },
        });
    }

    function bootContentDescriptionEditorWithRetry() {
        let attempts = 0;
        const maxAttempts = 120; // ~12 seconds
        const timer = setInterval(() => {
            attempts += 1;
            initContentDescriptionEditor();

            const textarea = document.getElementById('content_description_editor');
            if (textarea && window.tinymce?.get?.(textarea.id)) {
                clearInterval(timer);
                return;
            }

            if (attempts >= maxAttempts) {
                clearInterval(timer);
            }
        }, 100);
    }

    document.addEventListener('DOMContentLoaded', () => setTimeout(bootContentDescriptionEditorWithRetry, 0));
    window.addEventListener('load', () => setTimeout(bootContentDescriptionEditorWithRetry, 0));
    document.addEventListener('livewire:init', () => {
        setTimeout(bootContentDescriptionEditorWithRetry, 0);
        if (window.Livewire?.hook) {
            Livewire.hook('component.init', () => setTimeout(bootContentDescriptionEditorWithRetry, 0));
        }
    });

</script>
@endpush
