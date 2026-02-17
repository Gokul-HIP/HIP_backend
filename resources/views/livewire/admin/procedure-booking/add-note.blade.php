<flux:modal name="add-note" class="p-0" wire:close="closeModal" id="delete-org">
    <div x-data @click.outside="$wire.closeModal()">
        <div @click.stop>
            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeModal" />

            <!-- Title -->
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Add Note
            </h2>

            <!-- Description -->
            <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                Add a note to this procedure booking.
            </p>

            <!-- Buttons -->
            <div class="flex flex-col items-end gap-3">
                <textarea wire:model.defer="note"
                    @click.stop
                    class="w-full p-2 border border-gray-300 rounded-lg text-sm text-gray-700"
                    rows="4"
                    placeholder="Enter your note here..."></textarea>
                
                @error('note')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
                
                <div class="flex justify-end gap-3 w-full">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="addNote"
                        wire:loading.attr="disabled"
                        class="bg-[#0DA2E7] hover:bg-[#0DA2E7]/80 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                        <span wire:loading.remove wire:target="addNote">Add Note</span>
                        <span wire:loading wire:target="addNote">Adding...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</flux:modal>

