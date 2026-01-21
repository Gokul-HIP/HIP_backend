<flux:modal name="add-qualification" id="delete-org" x-on:close="$wire.resetForm()">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Add Qualification</flux:heading>
            <flux:text class="mt-2">
                Enter the name of the qualification you want to add.
            </flux:text>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Qualification Name</label>
            <input type="text" wire:model="new_qualification" class="w-full px-4 py-2 rounded border" placeholder="Enter qualification name">
            @error('new_qualification')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Qualification Description</label>
            <textarea wire:model="new_qualification_description" class="w-full px-4 py-2 rounded border" placeholder="Enter qualification description"></textarea>
            @error('new_qualification_description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <button
                type="button"
                onclick="Flux.modal('add-qualification').close()"
                class="bg-red-500 text-white px-4 py-2 rounded">
                Cancel
            </button>
            <button type="button" 
                    wire:click="addQualification" 
                    class="bg-[#0da2e7] text-white rounded text-sm px-6 py-2 hover:opacity-90">
                Save Qualification
            </button>
        </div>
    </div>
</flux:modal>