<flux:modal name="update-status" class="p-0" wire:close="closeModal" id="delete-org">
    <div x-data @click.outside="$wire.closeModal()">
        <div>

            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeModal" />

            <!-- Title -->
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Update Status?
            </h2>

            <!-- Description -->
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                You're about to update the status of this doctor booking.<br>
                Are you sure you want to update the status?
            </p>

            <!-- Buttons -->
            <div class="flex flex-col items-end gap-3">

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

                    <!-- Button -->
                    <button
                        type="button"
                        @click="toggle($event)"
                        class="w-full flex justify-between items-center border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50">

                        <span>{{ ucfirst($status ?? 'pending') }}</span>

                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="m19 9-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Floating Dropdown -->
                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open=false"
                        :style="`top:${top}px; left:${left}px; width:${width}px`"
                        class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto"
                    >
                        @foreach(['pending','confirmed','completed','cancelled'] as $item)
                            <button
                                wire:click="$set('status','{{ $item }}')"
                                @click="open=false"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100
                                {{ $status === $item ? 'bg-blue-50 text-blue-600' : '' }}"
                            >
                                {{ ucfirst($item) }}
                            </button>
                        @endforeach
                    </div>
                </div>
             
                
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="updateStatus"
                        wire:loading.attr="disabled"
                        class="bg-[#0DA2E7] hover:bg-[#0DA2E7]/80 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                        <span wire:loading.remove wire:target="updateStatus">Update Status</span>
                        <span wire:loading wire:target="updateStatus">Updating...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</flux:modal>