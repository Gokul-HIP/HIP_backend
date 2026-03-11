<div>
    <flux:modal name="doctor-credentials" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>
                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Doctor Credentials
                </h2>

                <p class="text-sm text-gray-500 mb-4">
                    {{ $doctorName ? 'Manage login credentials for Dr. ' . $doctorName : 'Manage doctor login credentials' }}
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            type="email"
                            wire:model="email"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="doctor@email.com">
                        @error('email')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password {{ $credentialId ? '(Optional to change)' : '*' }}
                        </label>
                        <input
                            type="password"
                            wire:model="password"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="{{ $credentialId ? 'Leave blank to keep existing password' : 'Enter password' }}">
                        @error('password')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input
                            type="password"
                            wire:model="password_confirmation"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Re-enter password">
                        @error('password_confirmation')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <flux:button variant="ghost" wire:click="closeModal">
                        Cancel
                    </flux:button>

                    <button
                        type="button"
                        wire:click="saveCredentials"
                        class="bg-[#0da2e7] hover:bg-[#0b8dc8] text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Save Credentials
                    </button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>