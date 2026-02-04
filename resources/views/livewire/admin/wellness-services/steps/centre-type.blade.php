<div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">1. Centre Type</h3>
    <p class="text-sm text-gray-500 mb-6">Select the type of wellness centre you're registering</p>
    
    <div class="space-y-6">
        <!-- Centre Type -->
        <div>
            <label for="centre_type" class="block text-sm font-medium text-gray-700 mb-2">
                Centre Type <span class="text-red-500">*</span>
            </label>
            <div
                x-data="{
                    open:false,
                    top:0,
                    left:0,
                    width:0,
                    toggle(e){
                        const rect = e.target.closest('button').getBoundingClientRect();
                        this.top = rect.bottom + window.scrollY + 6;
                        this.left = rect.left + window.scrollX;
                        this.width = rect.width;
                        this.open = !this.open;
                    }
                }"
                class="w-full">
                <button
                    type="button"
                    @click="toggle($event)"
                    class="w-full flex justify-between items-center border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50">
                    <span>
                        @if($centre_type)
                            @php
                                $selectedCategory = $wellnessCategories->firstWhere('id', $centre_type);
                            @endphp
                            {{ $selectedCategory->parent_category ?? 'Select category' }}
                        @else
                            Select category
                        @endif
                    </span>
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="m19 9-7 7-7-7"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition
                    @click.outside="open=false"
                    :style="`top:${top}px; left:${left}px; width:${width}px`"
                    class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto"
                >
                    <button
                        wire:click="$set('centre_type', '')"
                        @click="open=false"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                        {{ !$centre_type ? 'bg-blue-50 text-blue-600' : '' }}"
                    >
                        Select category
                    </button>
                    @foreach($wellnessCategories as $category)
                        <button
                            wire:click="$set('centre_type', '{{ $category->id }}')"
                            @click="open=false"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                            {{ $centre_type == $category->id ? 'bg-blue-50 text-blue-600' : '' }}"
                        >
                            {{ $category->parent_category }}
                        </button>
                    @endforeach
                </div>
            </div>
            @error('centre_type')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Operating Mode -->
        <div>
            <label for="operating_mode" class="block text-sm font-medium text-gray-700 mb-2">
                Operating Mode <span class="text-red-500">*</span>
            </label>
            <div
                x-data="{
                    open:false,
                    top:0,
                    left:0,
                    width:0,
                    toggle(e){
                        const rect = e.target.closest('button').getBoundingClientRect();
                        this.top = rect.bottom + window.scrollY + 6;
                        this.left = rect.left + window.scrollX;
                        this.width = rect.width;
                        this.open = !this.open;
                    }
                }"
                class="w-full">
                <button
                    type="button"
                    @click="toggle($event)"
                    class="w-full flex justify-between items-center border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50">
                    <span>{{ $operating_mode ?: 'Select operating mode' }}</span>
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="m19 9-7 7-7-7"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition
                    @click.outside="open=false"
                    :style="`top:${top}px; left:${left}px; width:${width}px`"
                    class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto"
                >
                    <button
                        wire:click="$set('operating_mode', '')"
                        @click="open=false"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                        {{ !$operating_mode ? 'bg-blue-50 text-blue-600' : '' }}"
                    >
                        Select operating mode
                    </button>
                    @foreach(['Online', 'Offline', 'Hybrid'] as $item)
                        <button
                            wire:click="$set('operating_mode', '{{ $item }}')"
                            @click="open=false"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                            {{ $operating_mode === $item ? 'bg-blue-50 text-blue-600' : '' }}"
                        >
                            {{ $item }}
                        </button>
                    @endforeach
                </div>
            </div>
            @error('operating_mode')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

