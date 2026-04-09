<div x-data="{}">

    <style>
        [x-cloak] { display: none !important; }

        /* ── Upload box ── */
        .upload-box {
            border: 2px dashed #b3e4f8;
            border-radius: 12px;
            min-height: 150px;
            background: #f0faff;
            transition: all 0.2s;
        }
        .upload-box:hover { border-color: #0DA2E7; background: rgba(13,162,231,0.06); }

        /* ── Form elements ── */
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-input {
            width: 100%; padding: 10px 14px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: 13.5px; color: #334155; background: #fff; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-input:focus { border-color: #0DA2E7; box-shadow: 0 0 0 3px rgba(13,162,231,0.12); }
        .form-input::placeholder { color: #cbd5e1; }
        textarea.form-input { resize: vertical; }

        /* ── Cards ── */
        .form-card { background: #fff; border: 1px solid #e8f4fd; border-radius: 16px; padding: 24px; }
        .card-title { font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }

        /* ── Toggle group (Internal/External, Image/Video) ── */
        .toggle-group { display: flex; background: #f1f5f9; border-radius: 8px; padding: 3px; gap: 2px; }
        .toggle-btn {
            flex: 1; padding: 7px 14px; font-size: 13px; font-weight: 500; border-radius: 6px;
            border: none; cursor: pointer; transition: all 0.15s; color: #64748b; background: transparent;
        }
        .toggle-btn.active { background: #fff; color: #0DA2E7; font-weight: 600; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }

        /* ── Destination prefix ── */
        .dest-prefix {
            display: inline-flex; align-items: center; padding: 10px 12px;
            background: #f1f5f9; border: 1px solid #e2e8f0; border-right: none;
            border-radius: 10px 0 0 10px; font-size: 13px; color: #64748b; white-space: nowrap;
        }
        .dest-input {
            flex: 1; padding: 10px 14px; border: 1px solid #e2e8f0;
            border-radius: 0 10px 10px 0; font-size: 13px; color: #334155; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .dest-input:focus { border-color: #0DA2E7; box-shadow: 0 0 0 3px rgba(13,162,231,0.12); }

        /* ── Custom dropdown trigger (matches filter-btn from dashboard) ── */
        .custom-select-trigger {
            width: 100%;
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: 13.5px; color: #334155; background: #fff;
            cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .custom-select-trigger:focus,
        .custom-select-trigger.open {
            border-color: #0DA2E7;
            box-shadow: 0 0 0 3px rgba(13,162,231,0.12);
            outline: none;
        }

        /* ── Pillbox trigger ── */
        .pillbox-trigger {
            min-height: 44px;
            display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
            padding: 8px 12px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            background: #f8fafc; cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .pillbox-trigger.open {
            border-color: #0DA2E7;
            box-shadow: 0 0 0 3px rgba(13,162,231,0.15);
            background: #fff;
        }

        /* ── Dropdown panel (same for all) ── */
        .dropdown-panel {
            position: absolute; left: 0; top: calc(100% + 4px);
            width: 100%; z-index: 9999;
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 6px;
            max-height: 240px; overflow-y: auto;
        }
        .dropdown-option {
            display: flex; align-items: center; gap-10px;
            width: 100%; padding: 9px 12px;
            font-size: 13px; color: #475569; border-radius: 8px;
            transition: background 0.1s; cursor: pointer;
            border: none; background: none; text-align: left;
        }
        .dropdown-option:hover,
        .dropdown-option.hovered { background: rgba(13,162,231,0.06); }
        .dropdown-option.selected { background: rgba(13,162,231,0.12); color: #0DA2E7; font-weight: 600; }

        /* ── Pill tag ── */
        .pill-tag {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 500;
            background: rgba(13,162,231,0.1); color: #0DA2E7;
        }
        .pill-tag button {
            display: flex; align-items: center; justify-content: center;
            width: 14px; height: 14px; border-radius: 50%;
            border: none; background: none; color: #0DA2E7;
            cursor: pointer; font-size: 11px; opacity: 0.7; transition: opacity 0.1s;
        }
        .pill-tag button:hover { opacity: 1; }
    </style>

    <div class="min-h-screen bg-gray-50 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Edit Ad</h1>
            <p class="text-slate-400 text-sm mt-1">Update promotional content for the HIP app.</p>
        </div>

        <form wire:submit.prevent="update" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <!-- ══════════ LEFT COLUMN ══════════ -->
                <div class="space-y-5">

                    <!-- Ad Identity & Content -->
                    <div class="form-card">
                        <h2 class="card-title">Ad Identity &amp; Content</h2>

                        <!-- Title -->
                        <div class="mb-4">
                            <label class="form-label">Ad Title <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="title" class="form-input" placeholder="e.g. New Telehealth Feature 2026">
                            @error('title')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label">Description</label>
                            <textarea wire:model="description" rows="4" class="form-input" placeholder="Briefly describe the purpose of this ad..."></textarea>
                            @error('description')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>

                        <!-- Media Upload -->
                        <div class="mb-4"
                             x-data="{
                                 existingUrl: @js($existingMediaUrl),
                                 existingType: @js($media_type),
                                 previewUrl: @js($existingMediaUrl),
                                 fileType: @js($media_type),
                                 handleFileChange(e) {
                                     const f = e.target.files[0];
                                     if (f) { this.previewUrl = URL.createObjectURL(f); this.fileType = f.type.startsWith('video') ? 'video' : 'image'; }
                                 },
                                 clearPreview() {
                                     if (this.previewUrl && this.previewUrl !== this.existingUrl) URL.revokeObjectURL(this.previewUrl);
                                     this.previewUrl = this.existingUrl;
                                     this.fileType = this.existingType;
                                     const fi = document.getElementById('editAdMediaFile'); if (fi) fi.value = '';
                                     $wire.call('removeMediaFile');
                                 }
                             }"
                             @reset-file-input.window="previewUrl = existingUrl; fileType = existingType;">
                            <label class="form-label">Upload Media <span class="text-slate-400 font-normal">(leave empty to keep current)</span></label>
                            <input type="file" id="editAdMediaFile" wire:model="media_file" accept="image/*,video/*" @change="handleFileChange($event)" class="hidden">

                            <!-- Empty state (no existing and no new) -->
                            <div x-show="!previewUrl">
                                <label for="editAdMediaFile" class="upload-box flex flex-col items-center justify-center gap-3 w-full py-10 px-4 cursor-pointer block">
                                    <div class="w-14 h-14 rounded-2xl bg-white shadow-sm border border-slate-200 flex items-center justify-center">
                                        <i class="fa-solid fa-cloud-arrow-up text-2xl" style="color:#0DA2E7;"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-600">Click to upload or drag and drop</p>
                                    <p class="text-xs text-slate-400">PNG, JPG, MP4 (Max 50 MB)</p>
                                </label>
                            </div>

                            <!-- Loading -->
                            <div wire:loading wire:target="media_file" class="flex flex-col items-center justify-center py-10 gap-3">
                                <div class="w-8 h-8 rounded-full border-2 border-t-2 animate-spin" style="border-color:rgba(13,162,231,0.2);border-top-color:#0DA2E7;"></div>
                                <p class="text-sm text-slate-500">Processing file...</p>
                            </div>

                            <!-- Preview (existing or new) -->
                            <div x-show="previewUrl" x-cloak class="relative rounded-xl overflow-hidden bg-slate-900 aspect-video mt-2">
                                <img x-show="fileType==='image'" x-bind:src="previewUrl" class="w-full h-full object-cover">
                                <video x-show="fileType==='video'" x-bind:src="previewUrl" controls class="w-full h-full object-cover"></video>
                                <button type="button" @click="clearPreview()"
                                        class="absolute top-2.5 right-2.5 w-8 h-8 bg-black/50 hover:bg-rose-500 text-white rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                            @error('media_file')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>

                        <!-- Media Type Toggle -->
                        <div class="mb-4">
                            <label class="form-label">Media Type <span class="text-rose-500">*</span></label>
                            <div class="toggle-group" x-data="{ type: @entangle('media_type') }">
                                <button type="button" @click="$wire.set('media_type','image')" :class="type==='image'?'active':''" class="toggle-btn">Image</button>
                                <button type="button" @click="$wire.set('media_type','video')" :class="type==='video'?'active':''" class="toggle-btn">Video</button>
                            </div>
                        </div>

                        <!-- Hospital — custom dropdown -->
                        <div>
                            <label class="form-label">Select Hospital</label>
                            <div x-data="{
                                    open: false,
                                    val: @js((string)($hospital_id ?? '')),
                                    label: @js($currentHospitalName),
                                    opts: @js($hospitals->map(fn($h) => ['id' => (string)$h->id, 'name' => $h->name ?? 'Hospital #'.$h->id])->prepend(['id' => '', 'name' => 'All Hospitals'])->values()->all()),
                                    select(id, name) { this.val = id; this.label = name; this.open = false; $wire.set('hospital_id', id); },
                                    init() {}
                                 }"
                                 class="relative" @click.outside="open=false">
                                <button type="button" @click="open=!open"
                                        class="custom-select-trigger" :class="open ? 'open' : ''">
                                    <span x-text="label" :class="label==='All Hospitals' ? 'text-slate-400' : 'text-slate-700'"></span>
                                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
                                </button>
                                <div x-show="open" x-cloak
                                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                     class="dropdown-panel">
                                    <template x-for="o in opts" :key="o.id">
                                        <button type="button" @click="select(o.id, o.name)" x-text="o.name"
                                                class="dropdown-option"
                                                :class="val === o.id ? 'selected' : ''"
                                                onmouseover="if(!this.classList.contains('selected'))this.style.background='rgba(13,162,231,0.06)'"
                                                onmouseout="if(!this.classList.contains('selected'))this.style.background=''">
                                        </button>
                                    </template>
                                </div>
                            </div>
                            @error('hospital_id')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Audience Targeting — pillbox multi-select -->
                    <div class="form-card">
                        <h2 class="card-title">Audience Targeting</h2>
                        <div x-data="areaPillbox({
                                options: @js($locations->map(fn($loc) => ['id' => $loc->id, 'name' => $loc->area . ' - ' . $loc->city])->values()->all()),
                                selected: @entangle('location_master_ids'),
                                init() {}
                             })"
                             class="relative">
                            <label class="form-label">Target Area <span class="font-normal text-slate-400">(Multi-select)</span></label>
                            <div class="relative" @click.outside="open=false">
                                <div @click="toggleDropdown($event)"
                                     class="pillbox-trigger" :class="open ? 'open' : ''">
                                    <template x-for="id in selected" :key="id">
                                        <span class="pill-tag">
                                            <span x-text="getLabel(id)"></span>
                                            <button type="button" @click.stop="remove(id)"><i class="fas fa-times text-xs"></i></button>
                                        </span>
                                    </template>
                                    <span x-show="selected.length === 0" class="text-slate-400 text-sm">Select areas...</span>
                                    <i class="fas fa-chevron-down text-xs text-slate-400 ml-auto transition-transform" :class="{'rotate-180':open}"></i>
                                </div>
                                <div x-show="open" x-cloak
                                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                     class="dropdown-panel">
                                    <div class="space-y-0.5">
                                        <template x-for="option in options" :key="option.id">
                                            <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-colors"
                                                   onmouseover="this.style.background='rgba(13,162,231,0.06)'"
                                                   onmouseout="this.style.background=''">
                                                <input type="checkbox" :value="option.id" @change="toggleOption(option.id)" :checked="selected.includes(option.id)"
                                                       class="w-4 h-4 rounded" style="accent-color:#0DA2E7; flex-shrink:0;">
                                                <span class="text-sm text-slate-600" x-text="option.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            @error('location_master_ids')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                </div>

                <!-- ══════════ RIGHT COLUMN ══════════ -->
                <div class="space-y-5">

                    <!-- Scheduling & Priority -->
                    <div class="form-card">
                        <h2 class="card-title">Scheduling &amp; Priority</h2>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">Start Date <span class="text-rose-500">*</span></label>
                                <input type="date" wire:model="start_date" class="form-input" style="color-scheme:light;">
                                @error('start_date')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">End Date <span class="text-rose-500">*</span></label>
                                <input type="date" wire:model="end_date" class="form-input" style="color-scheme:light;">
                                @error('end_date')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Priority Type <span class="text-rose-500">*</span></label>
                            <div x-data="{ open: false }" class="relative" @click.outside="open=false">
                                <button type="button" @click="open=!open"
                                        class="custom-select-trigger w-full" :class="open ? 'open' : ''">
                                    <span class="text-slate-700">{{ $priorityTypeOptions[$priority_type] ?? 'Medium' }}</span>
                                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
                                </button>
                                <div x-show="open" x-cloak
                                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                     class="dropdown-panel">
                                    @foreach($priorityTypeOptions as $value => $label)
                                        <button type="button" wire:click="$set('priority_type', '{{ $value }}')" @click="open=false"
                                                class="dropdown-option w-full {{ $priority_type === $value ? 'selected' : '' }}"
                                                onmouseover="if(!this.classList.contains('selected'))this.style.background='rgba(13,162,231,0.06)'"
                                                onmouseout="if(!this.classList.contains('selected'))this.style.background=''">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5">High = shown more often; Low = less frequent</p>
                            @error('priority_type')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Display Priority (1–100) <span class="text-slate-400 font-normal">optional</span></label>
                            <input type="number" wire:model="priority" min="1" max="100" class="form-input" placeholder="Override numeric priority (e.g. 75)">
                            <p class="text-xs text-slate-400 mt-1.5">Leave empty to use default for the priority type above</p>
                            @error('priority')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Redirect Logic -->
                    <div class="form-card">
                        <h2 class="card-title">Redirect Logic</h2>
                        <div class="mb-4">
                            <label class="form-label">Redirect Type</label>
                            <div class="toggle-group" x-data="{ type: @entangle('redirect_type') }">
                                <button type="button" @click="$wire.set('redirect_type','internal')" :class="type==='internal'?'active':''" class="toggle-btn">Internal</button>
                                <button type="button" @click="$wire.set('redirect_type','external')" :class="type==='external'?'active':''" class="toggle-btn">External</button>
                            </div>
                        </div>
                        <div x-data="{ rt: @entangle('redirect_type') }">
                            <label class="form-label">Destination <span class="text-rose-500">*</span></label>
                            <div class="flex">
                                <span class="dest-prefix" x-show="rt === 'internal'" x-cloak>hip://app/</span>
                                <input type="text" wire:model="redirect_url" class="dest-input"
                                       :style="rt==='internal' ? '' : 'border-radius:10px;'"
                                       placeholder="Internal: screen/... or External: https://...">
                            </div>
                            @error('redirect_url')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Ad Placement — pillbox multi-select -->
                    <div class="form-card">
                        <h2 class="card-title">Ad Placement <span class="text-sm font-normal text-slate-400">(Multi-select)</span></h2>
                        <div x-data="areaPillbox({
                                options: @js($placementOptions),
                                selected: @entangle('placements'),
                                init() {}
                             })"
                             class="relative">
                            <div class="relative" @click.outside="open=false">
                                <div @click="toggleDropdown($event)"
                                     class="pillbox-trigger" :class="open ? 'open' : ''">
                                    <template x-for="id in selected" :key="id">
                                        <span class="pill-tag">
                                            <span x-text="getLabel(id)"></span>
                                            <button type="button" @click.stop="remove(id)"><i class="fas fa-times text-xs"></i></button>
                                        </span>
                                    </template>
                                    <span x-show="selected.length === 0" class="text-slate-400 text-sm">Select placements...</span>
                                    <i class="fas fa-chevron-down text-xs text-slate-400 ml-auto transition-transform" :class="{'rotate-180':open}"></i>
                                </div>
                                <div x-show="open" x-cloak
                                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                     class="dropdown-panel">
                                    <div class="space-y-0.5">
                                        <template x-for="option in options" :key="option.id">
                                            <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-colors"
                                                   onmouseover="this.style.background='rgba(13,162,231,0.06)'"
                                                   onmouseout="this.style.background=''">
                                                <input type="checkbox" :value="option.id" @change="toggleOption(option.id)" :checked="selected.includes(option.id)"
                                                       class="w-4 h-4 rounded" style="accent-color:#0DA2E7; flex-shrink:0;">
                                                <span class="text-sm text-slate-600" x-text="option.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            @error('placements')<p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <a href="{{ route('healthcare.ads.ad-management.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-medium hover:bg-slate-50 transition-colors">
                    Cancel
                </a>
                <button type="button" wire:click="updateAsDraft"
                        class="px-6 py-2.5 rounded-xl border text-sm font-semibold transition-all"
                        style="border-color:#cbd5e1;background:#f1f5f9;color:#64748b;"
                        onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                    Save as Draft
                </button>
                <button type="submit"
                        class="px-7 py-2.5 rounded-xl text-white text-sm font-semibold active:scale-[0.98] transition-all"
                        style="background:#0DA2E7;box-shadow:0 4px 14px rgba(13,162,231,0.3);"
                        onmouseover="this.style.background='#0b8fcf';" onmouseout="this.style.background='#0DA2E7';">
                    <span wire:loading.remove wire:target="update">Update</span>
                    <span wire:loading wire:target="update" class="flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin text-xs"></i> Updating...
                    </span>
                </button>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
    function areaPillbox({ options, selected, init: userInit }) {
        return {
            open: false,
            options: options,
            selected: selected,
            init() { if (userInit) userInit.call(this); },
            toggleDropdown() { this.open = !this.open; },
            toggleOption(id) {
                const idx = this.selected.indexOf(id);
                if (idx > -1) this.selected.splice(idx, 1);
                else this.selected.push(id);
            },
            remove(id) { this.selected = this.selected.filter(i => i !== id); },
            getLabel(id) {
                const opt = this.options.find(o => o.id == id);
                return opt ? opt.name : '';
            }
        };
    }
</script>
@endpush

