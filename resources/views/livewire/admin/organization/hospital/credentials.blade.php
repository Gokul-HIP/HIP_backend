<flux:modal name="create-user" class="p-0" wire:close="closeModal" id="delete-org">
    <div x-data @click.outside="$wire.closeModal()">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                Create User
            </h2>
            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeModal" />

            <!-- Title -->
            <input type="email" wire:model="email" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Email">
            @error('email')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror

            <input type="password" wire:model="password" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Password">
            @error('password')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror

            <input type="password" wire:model="password_confirmation" class="w-full px-4 py-2 rounded-lg border glass-input mb-2" placeholder="Confirm Password">
            @error('password_confirmation')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror

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
                    wire:click="createUser"
                    class="bg-[#0da2e7] hover:bg-[#0da2e7]/80 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                    Create User
                </button>
            </div>

        </div>
    </div>
</flux:modal>
