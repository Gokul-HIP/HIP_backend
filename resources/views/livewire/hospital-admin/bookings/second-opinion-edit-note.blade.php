<flux:modal name="edit-second-opinion-note" class="p-0" wire:close="closeEditNoteModal">
    <div x-data @click.outside="$wire.closeEditNoteModal()">
        <div>
            <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeEditNoteModal" />

            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Edit Note
            </h2>

            <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                Update your note below.
            </p>

            <div class="flex flex-col items-end gap-3">
                <textarea wire:model="editingNoteText"
                    class="w-full p-2 border border-gray-300 rounded-lg text-sm text-gray-700" rows="4"
                    placeholder="Enter your note here..."></textarea>

                @error('editingNoteText')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror

                <div class="flex justify-end gap-3 w-full">
                    <button type="button" wire:click="closeEditNoteModal"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Cancel
                    </button>
                    <button type="button" wire:click="updateNote" wire:loading.attr="disabled"
                        class="bg-[#0DA2E7] hover:bg-[#0DA2E7]/80 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                        <span wire:loading.remove wire:target="updateNote">Update Note</span>
                        <span wire:loading wire:target="updateNote">Updating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</flux:modal>
