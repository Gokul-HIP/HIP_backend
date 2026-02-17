<div>
    <style>
    /* Force light mode on modal - override dark mode */
    [data-flux-modal="delete-org"] dialog,
    [data-flux-modal="delete-org"] dialog * {
        color-scheme: light !important;
        background-color: #ffffff !important;
        color: #111827 !important;
        border-color: #d1d5db !important;
    }
    
    [data-flux-modal="delete-org"] dialog {
        background-color: #ffffff !important;
        border-color: #d1d5db !important;
    }
    
    /* Force light borders on all elements */
    [data-flux-modal="delete-org"] dialog input,
    [data-flux-modal="delete-org"] dialog textarea,
    [data-flux-modal="delete-org"] dialog select,
    [data-flux-modal="delete-org"] dialog button,
    [data-flux-modal="delete-org"] dialog div,
    [data-flux-modal="delete-org"] dialog .border,
    [data-flux-modal="delete-org"] dialog [class*="border"] {
        border-color: #d1d5db !important;
    }
    </style>

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
</div>
