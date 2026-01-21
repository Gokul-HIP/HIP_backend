@if($selectedDoctorId)
    @php
        $selectedDoctor = \App\Models\Doctor::find($selectedDoctorId);
    @endphp

    @if($selectedDoctor)
        {{-- Doctor Details Card --}}
        <div class="bg-white rounded-xl p-6 shadow mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Doctor Details</h3>
            
            <div class="flex items-start gap-6">
                {{-- Avatar --}}
                <div class="flex-shrink-0">
                    @if($selectedDoctor->doctor_image)
                        <img src="{{ asset('storage/doctor/' . $selectedDoctor->doctor_image) }}" 
                             alt="{{ $selectedDoctor->doctor_name }}"
                             class="w-24 h-24 rounded-full object-cover">
                    @else
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 
                                    flex items-center justify-center text-white text-3xl font-bold">
                            {{ substr($selectedDoctor->doctor_name, 0, 1) }}
                        </div>
                    @endif
                </div>

                {{-- Doctor Info --}}
                <div class="flex-1 grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Name</p>
                        <p class="text-base font-semibold text-gray-900">Dr. {{ $selectedDoctor->doctor_name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Specialty</p>
                        @if($selectedDoctor->speciality && count($selectedDoctor->speciality) > 0)
                            <p class="text-base font-semibold text-gray-900">{{ implode(', ', $selectedDoctor->speciality) }}</p>
                        @else
                            <p class="text-base font-semibold text-gray-900">Not specified</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Availability Card --}}
        <div class="bg-white rounded-xl p-6 shadow mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Availability</h3>
            
            <div class="border rounded-lg overflow-hidden">
                <div class="max-h-[400px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8"></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Assigned</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Slots</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Procedure Assigned</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">

                            {{-- EDITED ASSIGNMENT --}}
                            @if($currentAssignment && $currentAssignment->count())
                                @foreach($currentAssignment as $assignment)
                                    <tr class="bg-yellow-50 border-l-4 border-yellow-500">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center">
                                                <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </td>
                        
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900">
                                                    {{ $assignment['day'] }}
                                                </span>
                                                <span class="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded-full font-medium">
                                                    EDITED
                                                </span>
                                            </div>
                                        </td>
                        
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $assignment['date'] }}
                                        </td>
                        
                                        <td class="px-4 py-3">
                                            <div class="space-y-1">
                                                @foreach($assignment['time_slots'] as $slot)
                                                    <div class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded inline-block">
                                                        {{ $slot['start'] }} - {{ $slot['end'] }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                        
                                        <td class="px-4 py-3">
                                            <div class="space-y-1">
                                                @foreach($assignment['procedures'] as $procedure)
                                                    <div class="text-blue-600 font-medium">
                                                        {{ $procedure }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        
                            {{-- EXISTING ASSIGNMENTS --}}
                            @forelse($doctorAssignments as $assignment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </td>
                        
                                    <td class="px-4 py-3 text-gray-900">{{ $assignment['day'] }}</td>
                                    <td class="px-4 py-3 text-gray-900">{{ $assignment['date'] }}</td>
                        
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            @foreach($assignment['time_slots'] as $slot)
                                                <div class="text-xs text-gray-600">
                                                    {{ $slot['start'] }} - {{ $slot['end'] }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                        
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            @foreach($assignment['procedures'] as $procedure)
                                                <span class="text-gray-700">{{ $procedure }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                @if(!$currentAssignment || !$currentAssignment->count())
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                            No other assignments
                                        </td>
                                    </tr>
                                @endif
                            @endforelse
                        
                        </tbody>
                        
                    </table>
                </div>
            </div>

        </div>

        <div class="bg-white rounded-xl p-6 shadow">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Additional Notes</h3>
            
            <textarea
                wire:model="notes"
                rows="5"
                placeholder="Enter any relevant notes about this assignment"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm
                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       resize-none"></textarea>
        </div>

    @else
        <div class="bg-white rounded-xl p-6 shadow">
            <div class="text-center py-12 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg font-medium">Doctor not found</p>
                <p class="text-sm mt-1">The selected doctor could not be loaded</p>
            </div>
        </div>
    @endif

@else
    {{-- No Doctor Selected State --}}
    <div class="bg-white rounded-xl p-6 shadow">
        <div class="text-center py-16 text-gray-500">
            <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <p class="text-lg font-medium text-gray-900">No Doctor Selected</p>
            <p class="text-sm mt-2">Please select a doctor from the previous step</p>
        </div>
    </div>
@endif

