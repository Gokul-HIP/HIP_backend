<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-slate-900">Renew Subscription</h3>
        <button type="button" wire:click="close" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
    </div>

    @if($selectedUser)
        <div class="mb-4 p-3 bg-slate-50 border border-slate-200 rounded-lg text-sm">
            <span class="font-medium">{{ trim($selectedUser->first_name.' '.$selectedUser->last_name) }}</span>
            <span class="text-slate-500"> · {{ $selectedUser->mobile_num }}</span>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Package</label>
            <select wire:model.live="packageId" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Select package</option>
                @foreach($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }} — ₹{{ number_format($package->price, 2) }} (max {{ $package->max_members }} members)</option>
                @endforeach
            </select>
            @error('packageId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        @if($selectedUser && $packageId && count($familyMembers) > 0)
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-slate-700">Covered Members</label>
                    <span class="text-xs text-slate-500">{{ count($selectedMemberIds) }} / {{ $this->maxMembers }} selected</span>
                </div>
                <div class="space-y-2 border border-slate-200 rounded-lg divide-y max-h-48 overflow-y-auto">
                    @foreach($familyMembers as $member)
                        @php $checked = in_array((string) $member['id'], array_map('strval', $selectedMemberIds), true); @endphp
                        <label wire:key="renew-member-{{ $member['id'] }}"
                            class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-slate-50 {{ $checked ? 'bg-sky-50' : '' }}">
                            <input type="checkbox"
                                value="{{ $member['id'] }}"
                                wire:model.live="selectedMemberIds"
                                class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900">{{ $member['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $member['relationship'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('selectedMemberIds') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Payment mode</label>
            <div class="flex gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="paymentMode" value="cash"> Cash
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="paymentMode" value="online"> Online
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Amount (₹)</label>
            <input type="number" step="0.01" min="0" wire:model="amount"
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="px-4 py-2 bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">
                <span wire:loading.remove wire:target="submit">Renew</span>
                <span wire:loading wire:target="submit">Processing…</span>
            </button>
            <button type="button" wire:click="close" class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600">Cancel</button>
        </div>
    </form>
</div>
