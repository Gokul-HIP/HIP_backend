<div class="space-y-4">

    <h2 class="text-lg font-semibold">Profile Overview</h2>

    <div class="bg-white rounded-xl shadow-md border p-6">
    
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-10 gap-y-6">
    
            <div>
                <p class="text-sm text-gray-500">First Name</p>
                <p class="text-lg font-medium text-gray-900">
                    {{ ucfirst($member->first_name ?? '-') }}
                </p>
            </div>
    
            <div>
                <p class="text-sm text-gray-500">Last Name</p>
                <p class="text-lg font-medium text-gray-900">
                    {{ ucfirst($member->last_name ?? '-') }}
                </p>
            </div>
    
            <div>
                <p class="text-sm text-gray-500">Mobile Number</p>
                <p class="text-lg font-medium text-gray-900">
                    {{ $member->mobile_num ?? '-' }}
                </p>
            </div>
    
            <div>
                <p class="text-sm text-gray-500">Gender</p>
                <p class="text-lg font-medium text-gray-900">
                    {{ ucfirst($member->gender ?? '-') }}
                </p>
            </div>
    
            <div>
                <p class="text-sm text-gray-500">Date of Birth</p>
                <p class="text-lg font-medium text-gray-900">
                    {{ $member->dob ?? '-' }}
                </p>
            </div>
    
            <div>
                <p class="text-sm text-gray-500">Profile Update</p>
                <p class="text-lg font-medium text-gray-900">
                    {{ $member->profile_update ? 'Updated' : 'Not Updated' ?? '-' }}
                </p>
            </div>
    
            <div class="md:col-span-3">
                <p class="text-sm text-gray-500">Email</p>
                <p class="text-lg font-medium text-gray-900 truncate" title="{{ $member->email }}">
                    {{ $member->email ?? '-' }}
                </p>
            </div>
    
        </div>
    
    </div>
    

</div>
