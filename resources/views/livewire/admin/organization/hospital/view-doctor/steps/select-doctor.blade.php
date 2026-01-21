<div class="bg-white rounded-xl p-4 shadow flex flex-col h-[420px]">

    <input
        type="text"
        wire:model.live="doctorSearch"
        placeholder="Search doctor by name, speciality or mobile..."
        class="mb-4 border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500"/>

    <div class="flex-1 overflow-y-auto border rounded-lg">

        @if($doctors->isEmpty())
            <div class="flex flex-col items-center justify-center h-full text-gray-500">
                <svg class="h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"/>
                </svg>
                <p>No doctors found for this hospital</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="p-3"></th>
                        <th class="p-3 text-left">Doctor</th>
                        <th class="p-3 text-left">Speciality</th>
                        <th class="p-3 text-left">Qualification</th>
                        <th class="p-3 text-left">Working Since</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($doctors as $doctor)
                        <tr
                            wire:click="$set('selectedDoctorId', {{ $doctor->id }})"
                            class="border-b cursor-pointer hover:bg-blue-50
                                   {{ $selectedDoctorId === $doctor->id ? 'bg-blue-50' : '' }}">
                            <td class="p-3">
                                <input type="radio" wire:model.live="selectedDoctorId" value="{{ $doctor->id }}">
                            </td>
                            <td class="p-3 font-medium">Dr. {{ $doctor->doctor_name }}</td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-1">
                                   {{-- {{  $doctor->speciality_names ?? '-'}} --}}
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                            {{ $doctor->speciality_names ?? '-' }}
                                        </span>
                                </div>
                            </td>
                            <td class="p-3">{{ $doctor->qualification_names ?? '-' }}</td>
                            <td class="p-3">{{ $doctor->working_since ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @endif
    </div>

    <div class="pt-3">
        {{ $doctors->links() }}
    </div>

     
    @error('selectedDoctorId')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

</div>
