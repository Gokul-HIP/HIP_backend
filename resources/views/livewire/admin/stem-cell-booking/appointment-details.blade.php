<div>
    <style>
        .card-shadow {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    
        .detail-row {
            display: grid;
            grid-template-columns: 140px 1fr;
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
            <a href="{{ route('admin.stemcell-booking.index') }}" class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1 mb-6">
                <i class="fas fa-arrow-left"></i>
                Back to Stem Cell Bookings
            </a>
            <!-- Header Section -->
            <div class="bg-white rounded-lg p-5 mb-6 card-shadow flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Appointment Details - #APT{{ str_pad($stemCellBooking->id, 4, '0', STR_PAD_LEFT) }}</h1>
            </div>
    
            <!-- Status Bar -->
            <div class="bg-white rounded-lg p-5 mb-6 card-shadow">
                <div class="flex items-center gap-8 flex-wrap">
                    @if ($stemCellBooking->status == 'confirmed')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-green-600 text-sm font-medium bg-green-100 text-green-700 px-2 py-1 rounded-md">Confirmed</span>
                        </div>
                    @elseif ($stemCellBooking->status == 'cancelled')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-red-600 text-sm font-medium bg-red-100 text-red-700 px-2 py-1 rounded-md">Cancelled</span>
                        </div>
                    @elseif ($stemCellBooking->status == 'pending')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-yellow-600 text-sm font-medium bg-yellow-100 text-yellow-700 px-2 py-1 rounded-md">Pending</span>
                        </div>
                    @elseif ($stemCellBooking->status == 'completed')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-green-600 text-sm font-medium bg-green-100 text-green-700 px-2 py-1 rounded-md">Completed</span>
                        </div>
                    @elseif ($stemCellBooking->status == 'enquiry')
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 text-sm font-medium">Status:</span>
                            <span class="text-blue-600 text-sm font-medium bg-blue-100 text-blue-700 px-2 py-1 rounded-md">Enquiry</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="far fa-calendar text-green-600"></i>
                        @php
                            $slots = $stemCellBooking->required_time_slots ?? [];
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
                        <span class="text-sm">{{ optional($stemCellBooking->booking_date)->format('d M ,Y') }} {{ $fromTime }} to {{ $toTime }}</span>
                    </div>
                </div>
            </div>
    
            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Member/Person Details Card -->
                <div class="bg-white rounded-lg p-5 card-shadow">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Member Details</h3>
                    <div class="space-y-3">
                      
                        @if($stemCellBooking->member)
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm">Member ID:</span>
                            <span class="text-gray-900 text-sm font-medium">HIP-{{ str_pad($stemCellBooking->member->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm">Member Name:</span>
                            <span class="text-gray-900 text-sm font-medium">{{ $stemCellBooking->member->full_name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm">Email:</span>
                            <span class="text-gray-900 text-sm font-medium">{{ $stemCellBooking->member->email ?? '-' }}</span>
                        </div>
                        @endif
                    </div>
                </div>
    
                <!-- Booking Details Card -->
                <div class="bg-white rounded-lg p-5 card-shadow">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Person Details</h3>
                    <div class="space-y-3">
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm">Name:</span>
                            <span class="text-gray-900 text-sm font-medium">{{ $stemCellBooking->name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm">Mobile:</span>
                            <span class="text-gray-900 text-sm font-medium">+91 {{ $stemCellBooking->mobile_number ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm">Booking Date:</span>
                            <span class="text-gray-900 text-sm font-medium">{{ optional($stemCellBooking->booking_date)->format('d M, Y') ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm">Time Slots:</span>
                            <span class="text-gray-900 text-sm font-medium">
                                @php    
                                    $slots = $stemCellBooking->required_time_slots ?? [];
                                    if (count($slots) >= 2) {
                                        $timeText = $slots[0] . ' - ' . end($slots);
                                    } elseif (count($slots) === 1) {
                                        $timeText = $slots[0];
                                    } else {
                                        $timeText = '-';
                                    }
                                @endphp
                                {{ $timeText }}
                            </span>
                        </div>
                        {{-- <div class="detail-row">
                            <span class="text-gray-500 text-sm">Status:</span>
                            <span class="text-gray-900 text-sm font-medium">
                                <span class="px-2 py-1 rounded-md text-xs font-medium
                                    {{ $stemCellBooking->status === 'confirmed'
                                        ? 'bg-green-100 text-green-700'
                                        : ($stemCellBooking->status === 'cancelled'
                                            ? 'bg-red-100 text-red-700'
                                            : ($stemCellBooking->status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-700'
                                                : ($stemCellBooking->status === 'completed'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-gray-100 text-gray-700'))) }}">
                                    {{ ucfirst($stemCellBooking->status) }}
                                </span>
                            </span>
                        </div> --}}
                    </div>
                </div>
            </div>
    
            <!-- Internal Admin Notes Section -->
            <div class="bg-white rounded-lg p-6 mb-6 card-shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Purpose & Comments :</h2>
                </div>
                <div>
                    <p class="text-[#0DA2E7] text-sm font-semibold mb-2">PURPOSE :</p>
                    <p class="text-gray-600 text-sm">{{ $stemCellBooking->purpose ?? 'wants to know more' }}</p>
                </div>
            </div>

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
        </div>
    </div>

    <!-- Delete Note Modal -->
    <flux:modal name="delete-note" class="p-0" wire:close="closeDeleteNoteModal">
        <div x-data @click.outside="$wire.closeDeleteNoteModal()">
            <div @click.stop>
                <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeDeleteNoteModal" />
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Note</h2>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this note?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteNoteModal"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteNote" wire:loading.attr="disabled"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50">
                        <span wire:loading.remove wire:target="deleteNote">Delete</span>
                        <span wire:loading wire:target="deleteNote">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>

