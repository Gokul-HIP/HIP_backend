<div>
    <style>
        .card-shadow {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .detail-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .btn-hover:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            transition: all 0.2s;
        }

        .history-item {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .history-item:last-child {
            border-bottom: none;
        }
    </style>

    <div class="bg-gray-50 min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <a href="{{ route('admin.diagnostic-test-booking.index') }}" class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1 mb-6">
                <i class="fas fa-arrow-left"></i>
                Back to Diagnostic Test Bookings
            </a>

            <!-- Header Section -->
            <div class="bg-white rounded-lg p-5 mb-6 card-shadow flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Test Details - #APT{{ str_pad($diagnosticBooking->id, 4, '0', STR_PAD_LEFT) }}</h1>
                <button class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition">
                    <i class="fas fa-edit text-gray-600"></i>
                    <span class="text-sm text-gray-700">Edit</span>
                </button>
            </div>

            <!-- Status Bar -->
            <div class="bg-white rounded-lg p-5 mb-6 card-shadow">
                <div class="flex items-center gap-8 flex-wrap">
                    @if ($diagnosticBooking->status == 'confirmed')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-green-600 text-sm font-medium bg-green-100 text-green-700 px-2 py-1 rounded-md">Confirmed</span>
                        </div>
                    @elseif ($diagnosticBooking->status == 'cancelled')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-red-600 text-sm font-medium bg-red-100 text-red-700 px-2 py-1 rounded-md">Cancelled</span>
                        </div>
                    @elseif ($diagnosticBooking->status == 'pending')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-yellow-600 text-sm font-medium bg-yellow-100 text-yellow-700 px-2 py-1 rounded-md">Pending</span>
                        </div>
                    @elseif ($diagnosticBooking->status == 'completed')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-green-600 text-sm font-medium bg-green-100 text-green-700 px-2 py-1 rounded-md">Completed</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="far fa-calendar text-green-600"></i>
                        @php
                            $slots = $diagnosticBooking->required_time_slots ?? [];
                            if (count($slots) >= 2) {
                                $fromTime = $slots[0];
                                $toTime = end($slots);
                            } elseif (count($slots) === 1) {
                                $fromTime = $slots[0];
                                $toTime = $slots[0];
                            } else {
                                $fromTime = '-';
                                $toTime = '-';
                            }
                        @endphp
                        <span class="text-sm">{{ optional($diagnosticBooking->booking_date)->format('d M ,Y') }} {{ $fromTime }} to {{ $toTime }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-vial text-gray-500"></i>
                        <span class="text-sm">{{ $diagnosticBooking->sample_collection === 'home' ? 'At Home' : ($diagnosticBooking->sample_collection === 'lab' ? 'Lab Visit' : '-') }}</span>
                    </div>
                </div>
            </div>

            <!-- Current Status Section -->
            <div class="bg-white rounded-lg p-5 mb-6 card-shadow">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Current Status :</h3>
                <!-- Header Row -->
                <div class="flex justify-between items-start">
                    <!-- Left: Status Info -->
                    <div>
                        @if ($statuses && $statuses->count() > 0)
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[#0DA2E7] font-medium text-sm">
                                    {{ ucfirst(optional($statuses->changedBy)->first_name) }}
                                    {{ ucfirst(optional($statuses->changedBy)->last_name) }} :
                                </span>

                                <span class="text-gray-500 text-xs">
                                    {{ optional($statuses->created_at)->format('d M ,Y h:i A') }}
                                </span>
                            </div>

                            <p class="text-gray-600 text-sm">
                                Status Changed from {{ $statuses->from_status }} to {{ $statuses->to_status }}
                            </p>
                        @else
                            <p class="text-gray-600 text-sm">No status history added yet</p>
                        @endif
                    </div>

                    <!-- Right: Update Status Dropdown -->
                    <div x-data="{ open:false }" class="relative">

                        <button
                            @click="open = !open"
                            class="inline-flex items-center justify-center text-white bg-[#0DA2E7]
                            hover:bg-[#0b8ac5] focus:ring-4 focus:ring-blue-200 shadow
                            font-medium rounded-md text-sm px-4 py-2.5 focus:outline-none">

                            {{ ucfirst($diagnosticBooking->status) }}

                            <svg class="w-4 h-4 ms-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 9-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div
                            x-show="open"
                            @click.outside="open=false"
                            x-transition
                            class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50">

                            <ul class="p-2 text-sm font-medium">

                                @foreach(['pending','confirmed','completed','cancelled'] as $item)
                                    <li>
                                        <button
                                            wire:click="updateStatusInstant('{{ $item }}')"
                                            @click="open=false"
                                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-100
                                            {{ $diagnosticBooking->status === $item ? 'bg-blue-50 text-[#0DA2E7]' : '' }}">

                                            {{ ucfirst($item) }}
                                        </button>
                                    </li>
                                @endforeach

                            </ul>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Member Details and Patient Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Member Details Card -->
                <div class="bg-white rounded-lg p-6 card-shadow">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <img src="{{ asset('assets/favicon.png') }}" alt="User" class="w-6 h-6">
                            Member Details
                        </h2>
                    </div>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Member ID:</span>
                            <span class="text-gray-900 text-sm">HIP-{{ str_pad(optional($diagnosticBooking->member)->id ?? 0, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Name:</span>
                            <span class="text-gray-900 text-sm">{{ optional($diagnosticBooking->member)->first_name ?? '-' }} {{ optional($diagnosticBooking->member)->last_name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Phone:</span>
                            <span class="text-gray-900 text-sm">+91 {{ $diagnosticBooking->mobile_number ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Email:</span>
                            <span class="text-gray-900 text-sm">{{ optional($diagnosticBooking->member)->email ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Patient Details Card -->
                <div class="bg-white rounded-lg p-6 card-shadow">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-user text-gray-900"></i>
                            Person Details
                        </h2>
                        <button class="flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50 transition text-sm">
                            <i class="fas fa-edit text-gray-600"></i>
                            Edit
                        </button>
                    </div>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Patient Name:</span>
                            <span class="text-gray-900 text-sm">{{ $diagnosticBooking->name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Phone:</span>
                            <span class="text-gray-900 text-sm">+91 {{ $diagnosticBooking->mobile_number ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointment Details and Timings Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Appointment Details Card -->
                <div class="bg-white rounded-lg p-6 card-shadow">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <i class="far fa-calendar-alt text-gray-600"></i>
                            Appointment Details
                        </h2>
                        <button class="flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50 transition text-sm">
                            <i class="fas fa-edit text-gray-600"></i>
                            Edit
                        </button>
                    </div>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Center Name:</span>
                            <span class="text-gray-900 text-sm">{{ optional($diagnosticBooking->diagnosticCenter)->name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Center ID:</span>
                            <span class="text-gray-900 text-sm">DGN-{{ str_pad(optional($diagnosticBooking->diagnosticCenter)->id ?? 0, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Center Whatsapp:</span>
                            <span class="text-gray-900 text-sm">+91 {{ optional($diagnosticBooking->diagnosticCenter)->contact_person_number ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Timings Card -->
                <div class="bg-white rounded-lg p-6 card-shadow">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <i class="far fa-clock text-gray-600"></i>
                            Timings
                        </h2>
                        <button class="flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50 transition text-sm">
                            <i class="fas fa-edit text-gray-600"></i>
                            Edit
                        </button>
                    </div>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Requested date:</span>
                            <span class="text-gray-900 text-sm">{{ optional($diagnosticBooking->booking_date)->format('d M ,Y') }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Requested Time Slot:</span>
                            <span class="text-gray-900 text-sm">{{ $fromTime }} to {{ $toTime }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Sample Collection:</span>
                            <span class="text-gray-900 text-sm">{{ $diagnosticBooking->sample_collection === 'home' ? 'At Home' : ($diagnosticBooking->sample_collection === 'lab' ? 'Lab Visit' : '-') }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Test Type:</span>
                            <span class="text-gray-900 text-sm">
                                {{ $diagnosticBooking->test_type === 'single' ? 'Single Test' : ($diagnosticBooking->test_type === 'multi' ? 'Multi Test' : ($diagnosticBooking->test_type === 'package' ? 'Package' : '-')) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Details -->
            <div class="bg-white rounded-lg p-6 mb-6 card-shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Test Details :</h2>
                    <button class="flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50 transition text-sm">
                        <i class="fas fa-edit text-gray-600"></i>
                        Edit
                    </button>
                </div>
                <div class="detail-row">
                    <span class="text-gray-500 text-sm font-medium">Sample Collection:</span>
                    <span class="text-gray-900 text-sm">{{ $diagnosticBooking->sample_collection === 'home' ? 'At Home' : ($diagnosticBooking->sample_collection === 'lab' ? 'Lab Visit' : '-') }}</span>
                </div>
                <div class="detail-row">
                    <span class="text-gray-500 text-sm font-medium">Test Type:</span>
                    <span class="text-gray-900 text-sm">
                        {{ $diagnosticBooking->test_type === 'single' ? 'Single Test' : ($diagnosticBooking->test_type === 'multi' ? 'Multi Test' : ($diagnosticBooking->test_type === 'package' ? 'Package' : '-')) }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="text-gray-500 text-sm font-medium">Test name & code:</span>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $items = $diagnosticBooking->test_items ?? [];
                            $items = App\Models\DiagnosticLabTest::whereIn('id', $items)->get();
                        @endphp
                        @forelse ($items as $item)
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">
                                {{ $item->test_name ?? '-' }} - {{ $item->test_code ?? '-' }}
                            </span>
                        @empty
                            <span class="text-sm text-gray-500">-</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Purpose & Comments Section -->
            <div class="bg-white rounded-lg p-6 mb-6 card-shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Purpose & Comments :</h2>
                </div>
                <div>
                    <p class="text-[#0DA2E7] text-sm font-semibold mb-2">PURPOSE :</p>
                    <p class="text-gray-600 text-sm">{{ $diagnosticBooking->purpose ?? '-' }}</p>
                </div>
            </div>

            <!-- Internal Admin Notes Section -->
            <div class="bg-white rounded-lg p-6 mb-6 card-shadow">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-base font-semibold text-gray-900">Internal Admin Notes :</h2>
                    <div class="flex gap-3">
                        <button wire:click="openAddNoteModal" class="bg-[#0DA2E7] text-white px-4 py-2 rounded-md text-sm font-medium btn-hover flex items-center gap-2">
                            Add Notes
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </div>
                <!-- Current Notes Section -->
                @if ($notes)
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[#0DA2E7] font-medium text-sm">{{ ucfirst(optional($notes->notesBy)->first_name) }} {{ ucfirst(optional($notes->notesBy)->last_name) }} :</span>
                        <span class="text-gray-500 text-xs">{{ optional($notes->created_at)->format('d M ,Y h:i A') }}</span>
                    </div>
                    <p class="text-gray-600 text-sm">{{ $notes->notes }}</p>
                </div>
                   
                @endif
                
                <!-- Notes History Section -->
                <h3 class="text-[#0DA2E7] text-sm font-semibold mb-3">Notes History :</h3>
                @if ($notesHistory && $notesHistory->count() > 0)
                    <div class="space-y-4">
                        @foreach ($notesHistory as $note)
                            <div class="border-b border-gray-200 pb-4 last:border-b-0">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-gray-900 text-sm font-medium mb-1">
                                            {{ ucfirst(optional($note->notesBy)->first_name) }} {{ ucfirst(optional($note->notesBy)->last_name)}} :
                                        </span>
                                        <span class="text-gray-500 font-medium text-xs">
                                            {{ optional($note->updated_at)->format('d M ,Y h:i A') }}
                                        </span>
                                        @if ($note->created_at != $note->updated_at)
                                            <span class="text-gray-400 text-xs italic">(edited)</span>
                                        @endif
                                    </div>
                                    
                                    @if ($note->notes_by == Auth::id())
                                        <div class="flex items-center gap-2">
                                            <button
                                                wire:click="openEditNoteModal({{ $note->id }})"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-medium hover:bg-blue-100 transition">
                                        
                                                <i class="fas fa-pen text-[10px]"></i>
                                                Edit
                                            </button>
                                        
                                            <button
                                                wire:click="openDeleteNoteModal({{ $note->id }})"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100 transition">
                                        
                                                <i class="fas fa-trash text-[10px]"></i>
                                                Delete
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ $note->notes }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-sticky-note text-4xl mb-3 opacity-50"></i>
                        <p class="text-sm">No notes added yet</p>
                        <p class="text-xs mt-1">Click "Add Notes" to create your first note</p>
                    </div>
                @endif
            </div>

            <!-- Status Change History Section -->
            <div class="bg-white rounded-lg p-6 card-shadow">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Status Change History :</h2>
                @if($statuses && $statuses->count() > 0)
                    <div class="mb-5">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-[#0DA2E7] font-medium text-sm">{{ ucfirst(optional($statuses->changedBy)->first_name) }} {{ ucfirst(optional($statuses->changedBy)->last_name) }} :</span>
                            <span class="text-gray-500 text-xs">{{ optional($statuses->created_at)->format('d M ,Y h:i A') }}</span>
                        </div>
                        <p class="text-gray-600 text-sm">Status Changed from {{ $statuses->from_status }} to {{ $statuses->to_status }}</p>
                    </div>
                @endif

               @if ($statusHistory && $statusHistory->count() > 0)
                <div class="mt-6">
                    <h3 class="text-[#0DA2E7] text-sm font-semibold mb-3">Status History :</h3>
                    
                    @foreach ($statusHistory as $status)
                        <div class="history-item">
                            <p class="text-gray-900 text-sm font-medium mb-1">{{ ucfirst(optional($status->changedBy)->first_name) }} {{ ucfirst(optional($status->changedBy)->last_name) }} : {{ optional($status->created_at)->format('d M ,Y h:i A') }}</p>
                            <p class="text-gray-600 text-sm">Status Changed from {{ $status->from_status }} to {{ $status->to_status }}</p>
                        </div>
                    @endforeach
                </div>
              @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-sticky-note text-4xl mb-3 opacity-50"></i>
                    <p class="text-sm">No status history added yet</p>
                    <p class="text-xs mt-1">Click "Add Status" to create your first status</p>
                </div>
              @endif

            </div>

        </div>
    </div>
    <flux:modal name="delete-note" class="p-0" wire:close="closeDeleteNoteModal" id="delete-org">
        <div x-data @click.outside="$wire.closeDeleteNoteModal()">
            <div>
                <!-- Close Icon -->
                <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeDeleteNoteModal" />

                <!-- Title -->
                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Note
                </h2>

                <!-- Description -->
                <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                    Are you sure you want to delete this note?
                </p>

                <!-- Buttons -->
                <div class="flex flex-col items-end gap-3">
                    <div class="flex justify-end gap-3 w-full">
                        <button type="button" wire:click="closeDeleteNoteModal"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                            Cancel
                        </button>
                        <button type="button" wire:click="deleteNote" wire:loading.attr="disabled"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                            <span wire:loading.remove wire:target="deleteNote">Delete Note</span>
                            <span wire:loading wire:target="deleteNote">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
