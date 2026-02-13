<div class="space-y-4">

    <h2 class="text-lg font-semibold">Profile Overview</h2>

    <div class="bg-white rounded-xl shadow-md border p-6 grid grid-cols-2 gap-6">

        <div>
            <p class="text-sm text-gray-500">Doctor Name</p>
            <p class="text-lg font-medium">{{ ucfirst($doctor->name) }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Mobile Number</p>
            <p class="text-lg font-medium">{{ $doctor->mobile_number }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Qualification</p>
            <p class="text-lg font-medium">{{ strtoupper($doctor->qualification_names ?? '-') }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Experience</p>
            <p class="text-lg font-medium">{{ $doctor->working_since ?? '-' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Specialities</p>
            <p class="font-medium">
                {{ implode(', ', $doctor->speciality ?? []) }}
            </p>
        </div>

    </div>

</div>