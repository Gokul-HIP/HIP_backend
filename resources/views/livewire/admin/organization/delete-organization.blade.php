<flux:modal name="delete-org" class="p-0" wire:close="closeModal">
    <div x-data @click.outside="$wire.closeModal()">
        <div>

            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeModal" />

            <!-- Title -->
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Delete Organization?
            </h2>

            <!-- Description -->
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                You're about to delete this Organization.<br>
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
                    Delete Organization
                </button>
            </div>

        </div>
    </div>
</flux:modal>
