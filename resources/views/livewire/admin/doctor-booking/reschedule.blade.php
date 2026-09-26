<flux:modal name="reschedule-appointment" class="p-0" wire:close="closeRescheduleModal">
    <div @click.outside="$wire.closeRescheduleModal()">
        <div>
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeRescheduleModal" />

            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Reschedule Appointment
            </h2>
            <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                Choose a new date and time for this appointment. The same appointment record will be updated.
            </p>

            <div class="mb-4 rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-700 space-y-1">
                <p><span class="font-semibold">Appointment ID:</span> {{ $aptId ?: '-' }}</p>
                <p><span class="font-semibold">Patient:</span> {{ $patientName ?: '-' }}</p>
                <p><span class="font-semibold">Doctor:</span> {{ $doctorName ?: '-' }}</p>
                <p><span class="font-semibold">Current Date:</span> {{ $currentDateLabel ?: '-' }}</p>
                <p><span class="font-semibold">Current Time:</span> {{ $currentTimeLabel ?: '-' }}</p>
            </div>

            <div class="space-y-3 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Date</label>
                    <input type="date" wire:model="newDate"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                        min="{{ now()->toDateString() }}">
                    @error('newDate') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    @error('booking_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Time</label>
                    <input type="time" wire:model="newTime"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @error('newTime') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    @error('required_time_slots') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    @error('required_time_slots.0') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeRescheduleModal"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow"
                    style="background:#6b7280; color:#ffffff !important;">
                    Cancel
                </button>
                <button type="button" wire:click="rescheduleAppointment" wire:loading.attr="disabled"
                    class="text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50"
                    style="background:var(--button-color);">
                    <span wire:loading.remove wire:target="rescheduleAppointment" style="color:#ffffff !important;">Reschedule Appointment</span>
                    <span wire:loading wire:target="rescheduleAppointment" style="color:#ffffff !important;">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</flux:modal>
