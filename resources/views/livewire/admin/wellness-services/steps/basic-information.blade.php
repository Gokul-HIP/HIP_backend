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
    </div>
</div>

