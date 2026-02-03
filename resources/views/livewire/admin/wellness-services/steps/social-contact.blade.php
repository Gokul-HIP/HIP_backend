<div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">4. Social Media & Contact Details</h3>
    
    <div class="space-y-6">

        <!-- Centre Social Media Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Centre Website -->
            <div>
                <label for="centre_website" class="block text-sm font-medium text-gray-700 mb-2">
                    Centre Website <span class="text-red-500">*</span>
                </label>
                <input 
                    type="url"
                    id="centre_website"
                    wire:model="centre_website"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Website URL">
                @error('centre_website')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="centre_instagram_links" class="block text-sm font-medium text-gray-700 mb-2">
                    Centre Instagram Links <span class="text-red-500">*</span>
                </label>
                <input 
                    type="url"
                    id="centre_instagram_links"
                    wire:model="centre_instagram_links"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Instagram Links">
                @error('centre_instagram_links')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="centre_facebook_links" class="block text-sm font-medium text-gray-700 mb-2">
                    Centre Facebook Links <span class="text-red-500">*</span>
                </label>
                <input 
                    type="url"
                    id="centre_facebook_links"
                    wire:model="centre_facebook_links"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Facebook Links">
                @error('centre_facebook_links')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="centre_linkedin_links" class="block text-sm font-medium text-gray-700 mb-2">
                    Centre LinkedIn Links <span class="text-red-500">*</span>
                </label>
                <input 
                    type="url"
                    id="centre_linkedin_links"
                    wire:model="centre_linkedin_links"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="LinkedIn Links">
                @error('centre_linkedin_links')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="centre_twitter_links" class="block text-sm font-medium text-gray-700 mb-2">
                    Centre Twitter Links <span class="text-red-500">*</span>
                </label>
                <input 
                    type="url"
                    id="centre_twitter_links"
                    wire:model="centre_twitter_links"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Twitter Links">
                @error('centre_twitter_links')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="centre_youtube_links" class="block text-sm font-medium text-gray-700 mb-2">
                    Centre YouTube Links <span class="text-red-500">*</span>
                </label>
                <input 
                    type="url"
                    id="centre_youtube_links"
                    wire:model="centre_youtube_links"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="YouTube Links">
                @error('centre_youtube_links')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <!-- Contact Person Name -->
        <div>
            <label for="contact_person_name" class="block text-sm font-medium text-gray-700 mb-2">
                Contact Person Name <span class="text-red-500">*</span>
            </label>
            <input 
                type="text"
                id="contact_person_name"
                wire:model="contact_person_name"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Contact Person Name">
            @error('contact_person_name')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Contact Person Mobile -->
            <div>
                <label for="contact_person_mobile" class="block text-sm font-medium text-gray-700 mb-2">
                    Contact Person Mobile <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text"
                    id="contact_person_mobile"
                    wire:model="contact_person_mobile"
                    maxlength="15"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Mobile Number">
                @error('contact_person_mobile')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Contact Person Email -->
            <div>
                <label for="contact_person_email" class="block text-sm font-medium text-gray-700 mb-2">
                    Contact Person Email <span class="text-red-500">*</span>
                </label>
                <input 
                    type="email"
                    id="contact_person_email"
                    wire:model="contact_person_email"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Email Address">
                @error('contact_person_email')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
</div>

