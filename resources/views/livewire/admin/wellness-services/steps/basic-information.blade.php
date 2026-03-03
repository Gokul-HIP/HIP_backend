<div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">2. Basic Information</h3>
    
    <div class="space-y-6">
        <!-- Centre Name -->
        <div>
            <label for="centre_name" class="block text-sm font-medium text-gray-700 mb-2">
                Centre Name <span class="text-red-500">*</span>
            </label>
            <input 
                type="text"
                id="centre_name"
                wire:model="centre_name"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Registered name of the Centre">
            @error('centre_name')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Age Group Served -->
        <div>
            <label for="age_group_served" class="block text-sm font-medium text-gray-700 mb-2">
                Age Group Served
            </label>
            <input 
                type="text"
                id="age_group_served"
                wire:model="age_group_served"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g 18-65 years">
            @error('age_group_served')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                Description <span class="text-red-500">*</span>
            </label>
            <textarea 
                id="description"
                wire:model="description"
                rows="4"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Describe your wellness centre.."></textarea>
            @error('description')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Languages Supported -->
        <div>
            <label for="languages_supported" class="block text-sm font-medium text-gray-700 mb-2">
                Languages Supported
            </label>
            <input 
                type="text"
                id="languages_supported"
                wire:model="languages_supported"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="eg English Hindi Kannada">
            @error('languages_supported')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Target Audience -->
        <div>
            <label for="target_audience" class="block text-sm font-medium text-gray-700 mb-2">
                Target Audience
            </label>
            <input 
                type="text"
                id="target_audience"
                wire:model="target_audience"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Individual/Corporate/Students/Women">
            @error('target_audience')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Working Since -->
        <div>
            <label for="working_since" class="block text-sm font-medium text-gray-700 mb-2">
                Working Since
            </label>
            <input 
                type="text"
                id="working_since"
                wire:model="working_since"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. 2015 or January 2015">
            @error('working_since')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Timings: Working Days & Working Time -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Timings</label>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-4">
                <!-- Working Days -->
                <div>
                    <span class="block text-xs font-medium text-gray-500 mb-2">Working Days</span>
                    <div class="flex flex-wrap gap-3">
                        @php
                            $days = [
                                'monday'    => 'Mon',
                                'tuesday'   => 'Tue',
                                'wednesday' => 'Wed',
                                'thursday'  => 'Thu',
                                'friday'    => 'Fri',
                                'saturday'  => 'Sat',
                                'sunday'    => 'Sun',
                            ];
                        @endphp
                        @foreach ($days as $value => $label)
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="working_days"
                                    value="{{ $value }}"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('working_days')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
                <!-- Working Time -->
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 text-blue-600 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div>
                                <label for="working_open_time" class="sr-only">Open time</label>
                                <input
                                    type="time"
                                    id="working_open_time"
                                    wire:model="working_open_time"
                                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                            <span class="text-gray-500 text-sm">to</span>
                            <div>
                                <label for="working_close_time" class="sr-only">Close time</label>
                                <input
                                    type="time"
                                    id="working_close_time"
                                    wire:model="working_close_time"
                                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

