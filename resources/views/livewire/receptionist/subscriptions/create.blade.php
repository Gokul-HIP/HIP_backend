<div class="max-w-2xl mx-auto px-6 py-6">
    <div class="mb-6">
        <a href="{{ route('receptionist.manage-subscriptions.index') }}" class="text-sm text-sky-600 hover:underline">← Back to subscriptions</a>
        <h2 class="text-xl font-bold text-slate-900 mt-2">New Subscription</h2>
        <p class="text-sm text-slate-500">Activate a family package for a user</p>
    </div>

    <form wire:submit="submit" class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Search user by mobile</label>
            <input type="text" wire:model.live.debounce.400ms="phoneSearch" placeholder="Enter mobile number"
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            @error('selectedUser') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

            @if($searchResults->isNotEmpty() && ! $selectedUser)
                <ul class="mt-2 border border-slate-200 rounded-lg divide-y max-h-48 overflow-y-auto">
                    @foreach($searchResults as $user)
                        <li>
                            <button type="button" wire:click="selectUser('{{ $user->id }}')"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50">
                                {{ trim($user->first_name.' '.$user->last_name) }} — {{ $user->mobile_num }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($selectedUser)
                <div class="mt-3 p-3 bg-sky-50 border border-sky-100 rounded-lg text-sm">
                    <span class="font-medium text-sky-900">{{ trim($selectedUser->first_name.' '.$selectedUser->last_name) }}</span>
                    <span class="text-sky-700"> · {{ $selectedUser->mobile_num }}</span>
                </div>
            @elseif(trim($phoneSearch) !== '')
                <p class="text-amber-600 text-xs mt-2">No user found. Check the mobile number.</p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Package</label>
            <select wire:model.live="packageId" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Select package</option>
                @foreach($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }} — ₹{{ number_format($package->price, 2) }} ({{ $package->duration_days }} days, max {{ $package->max_members }} members)</option>
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
                <p class="text-xs text-slate-500 mb-3">Select up to {{ $this->maxMembers }} member(s) — self and/or dependents.</p>
                <div class="space-y-2 border border-slate-200 rounded-lg divide-y">
                    @foreach($familyMembers as $member)
                        @php $checked = in_array((string) $member['id'], array_map('strval', $selectedMemberIds), true); @endphp
                        <label wire:key="covered-member-{{ $member['id'] }}"
                            class="flex items-center gap-3 px-3 py-3 cursor-pointer hover:bg-slate-50 {{ $checked ? 'bg-sky-50' : '' }}">
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
        @elseif($selectedUser && $packageId && count($familyMembers) === 0)
            <p class="text-amber-600 text-xs bg-amber-50 border border-amber-100 rounded-lg p-3">
                No family members found for this user. Ensure the user has a primary profile and dependents in the system.
            </p>
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
            @error('paymentMode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Amount (₹)</label>
            <input type="number" step="0.01" min="0" wire:model="amount"
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        @if($paymentMode === 'online')
            <p class="text-xs text-slate-500 bg-sky-50 border border-sky-100 rounded-lg p-3">
                Online payment is recorded at the counter (e.g. QR scan). The subscription activates immediately — no payment notification is sent to the user.
            </p>
        @else
            <p class="text-xs text-slate-500 bg-green-50 border border-green-100 rounded-lg p-3">
                Cash payment will create a completed invoice and activate the subscription immediately.
            </p>
        @endif

        <div class="flex gap-3 pt-2">
            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="px-5 py-2.5 text-white font-semibold text-sm rounded-lg"
                style="background: var(--button-color); hover:bg-button-hover; disabled:opacity-60;" >
                <i class="fas fa-plus text-xs" style="color: #fff;"></i>
                <span wire:loading.class="hidden" wire:target="submit">Create Subscription</span>
                <span wire:loading.class.remove="hidden" wire:target="submit" class="hidden">Processing…</span>
            </button>
            <a href="{{ route('receptionist.manage-subscriptions.index') }}" class="px-5 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>
