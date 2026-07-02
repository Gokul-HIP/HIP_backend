<div>
    @if(!$secondOpinion)
        <div class="p-6 text-center text-gray-500">Second opinion booking not found.</div>
    @else
    <style>
        .card-shadow { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .detail-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-row:last-child { margin-bottom: 0; }
        .btn-hover:hover { opacity: 0.9; transform: translateY(-1px); transition: all 0.2s; }
        .history-item { padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
        .history-item:last-child { border-bottom: none; }
    </style>

    @php
        $slots = $secondOpinion->preferred_time_slots ?? [];
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
        $documents = $secondOpinion->documentRecords();
    @endphp

    <div class="bg-gray-50 min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <a href="{{ route('admin.second-opinion.index') }}" class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1 mb-6">
                <i class="fas fa-arrow-left"></i>
                Back to Second Opinion Bookings
            </a>

            <div class="bg-white rounded-lg p-5 mb-6 card-shadow flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Second Opinion Details - #SO{{ str_pad($secondOpinion->id, 4, '0', STR_PAD_LEFT) }}
                </h1>
            </div>

            <div class="bg-white rounded-lg p-5 mb-6 card-shadow">
                <div class="flex items-center gap-8 flex-wrap">
                    <div class="flex items-center gap-3">
                        <span class="text-gray-600 text-sm font-medium">Status:</span>
                        <span class="text-sm font-medium px-2 py-1 rounded-md
                            {{ $secondOpinion->status === 'confirmed' ? 'bg-green-100 text-green-700'
                                : ($secondOpinion->status === 'cancelled' ? 'bg-red-100 text-red-700'
                                : ($secondOpinion->status === 'pending' ? 'bg-yellow-100 text-yellow-700'
                                : 'bg-blue-100 text-blue-700')) }}">
                            {{ ucfirst($secondOpinion->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="far fa-calendar text-green-600"></i>
                        <span class="text-sm">
                            {{ optional($secondOpinion->preferred_date)->format('d M, Y') ?? '-' }}
                            {{ $fromTime !== '-' ? $fromTime . ' to ' . $toTime : '' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-video text-gray-500"></i>
                        <span class="text-sm">{{ $secondOpinion->mode_of_consultation ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg p-5 mb-6 card-shadow">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Current Status :</h3>
                <div class="flex justify-between items-start">
                    <div>
                        @if ($statuses)
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[#0DA2E7] font-medium text-sm">
                                    {{ ucfirst(optional($statuses->changedBy)->first_name) }}
                                    {{ ucfirst(optional($statuses->changedBy)->last_name) }} :
                                </span>
                                <span class="text-gray-500 text-xs">
                                    {{ optional($statuses->created_at)->format('d M, Y h:i A') }}
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm">
                                Status changed from {{ $statuses->from_status }} to {{ $statuses->to_status }}
                            </p>
                        @else
                            <p class="text-gray-600 text-sm">No status history added yet</p>
                        @endif
                    </div>

                    <div x-data="{ open:false }" class="relative">
                        <button
                            @click="open = !open"
                            class="inline-flex items-center justify-center text-white focus:ring-4 focus:ring-blue-200 shadow
                            font-medium rounded-md text-sm px-4 py-2.5 focus:outline-none" style="background:var(--button-color); hover:background:var(--button-hover);">
                            {{ ucfirst($secondOpinion->status) }}
                            <svg class="w-4 h-4 ms-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-transition
                            class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                            <ul class="p-2 text-sm font-medium">
                                @foreach(['pending','confirmed','completed','cancelled'] as $item)
                                    <li>
                                        <button wire:click="updateStatusInstant('{{ $item }}')" @click="open=false"
                                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-100
                                            {{ $secondOpinion->status === $item ? 'bg-blue-50 text-[#0DA2E7]' : '' }}">
                                            {{ ucfirst($item) }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg p-6 card-shadow">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                        <img src="{{ asset('assets/favicon.png') }}" alt="User" class="w-6 h-6">
                        Member Details
                    </h2>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Member ID:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->member?->hip_id ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Name:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->member?->name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Phone:</span>
                            <span class="text-gray-900 text-sm">+91 {{ $secondOpinion->member?->mobile_num ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Email:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->member?->email ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 card-shadow">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                        <i class="fas fa-user text-gray-900"></i>
                        Patient Details
                    </h2>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Patient Name:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->patient_name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Relationship:</span>
                            <span class="text-gray-900 text-sm">{{ ucfirst($secondOpinion->relationship ?? '-') }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Patient ID:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->patient_id ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg p-6 card-shadow">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                        <i class="far fa-calendar-alt text-gray-600"></i>
                        Consultation Details
                    </h2>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Hospital:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->branch?->name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Doctor:</span>
                            <span class="text-gray-900 text-sm">Dr. {{ $secondOpinion->doctor?->name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Speciality:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->speciality?->name ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Mode:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->mode_of_consultation ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 card-shadow">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                        <i class="far fa-clock text-gray-600"></i>
                        Preferred Timings
                    </h2>
                    <div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Preferred Date:</span>
                            <span class="text-gray-900 text-sm">{{ optional($secondOpinion->preferred_date)->format('d M, Y') ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Preferred Time:</span>
                            <span class="text-gray-900 text-sm">{{ $fromTime }} to {{ $toTime }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Payment Status:</span>
                            <span class="text-gray-900 text-sm">{{ $secondOpinion->payment_mode_label }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-500 text-sm font-medium">Amount:</span>
                            <span class="text-gray-900 text-sm">₹{{ number_format((float) ($secondOpinion->amount_after_discount ?? 0), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg p-6 mb-6 card-shadow">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Medical Information</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-[#0DA2E7] text-sm font-semibold mb-1">Diagnosis:</p>
                        <p class="text-gray-600 text-sm">{{ $secondOpinion->diagnosis ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[#0DA2E7] text-sm font-semibold mb-1">Treatment:</p>
                        <p class="text-gray-600 text-sm">{{ $secondOpinion->treatment ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[#0DA2E7] text-sm font-semibold mb-1">Question for Doctor:</p>
                        <p class="text-gray-600 text-sm">{{ $secondOpinion->question_for_doctor ?? '-' }}</p>
                    </div>
                </div>
            </div>

            @if($documents->isNotEmpty())
            <div class="bg-white rounded-lg p-6 mb-6 card-shadow">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Uploaded Documents</h2>
                <div class="space-y-3">
                    @foreach($documents as $document)
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $document->document_name ?? 'Document' }}</p>
                                <p class="text-xs text-gray-500">{{ $document->document_type ?? '' }}</p>
                            </div>
                            @if($document->document_url)
                                <a href="{{ $document->document_url }}" target="_blank"
                                    class="text-[#0DA2E7] text-sm hover:underline">
                                    View
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bg-white rounded-lg p-6 mb-6 card-shadow">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-base font-semibold text-gray-900">Internal Admin Notes :</h2>
                    <button wire:click="openAddNoteModal" class="text-white px-4 py-2 rounded-md text-sm font-medium btn-hover flex items-center gap-2" style="background:var(--button-color); hover:background:var(--button-hover);">
                        Add Notes
                        <i class="fas fa-plus text-xs"></i>
                    </button>
                </div>

                @if ($notes)
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[#0DA2E7] font-medium text-sm">
                            {{ ucfirst(optional($notes->notesBy)->first_name) }} {{ ucfirst(optional($notes->notesBy)->last_name) }} :
                        </span>
                        <span class="text-gray-500 text-xs">{{ optional($notes->created_at)->format('d M, Y h:i A') }}</span>
                    </div>
                    <p class="text-gray-600 text-sm">{{ $notes->notes }}</p>
                </div>
                @endif

                <h3 class="text-[#0DA2E7] text-sm font-semibold mb-3">Notes History :</h3>
                @if ($notesHistory && $notesHistory->count() > 0)
                    <div class="space-y-4">
                        @foreach ($notesHistory as $note)
                            <div class="border-b border-gray-200 pb-4 last:border-b-0">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-gray-900 text-sm font-medium">
                                            {{ ucfirst(optional($note->notesBy)->first_name) }} {{ ucfirst(optional($note->notesBy)->last_name) }} :
                                        </span>
                                        <span class="text-gray-500 font-medium text-xs">
                                            {{ optional($note->updated_at)->format('d M, Y h:i A') }}
                                        </span>
                                        @if ($note->created_at != $note->updated_at)
                                            <span class="text-gray-400 text-xs italic">(edited)</span>
                                        @endif
                                    </div>
                                    @if ($note->notes_by == Auth::id())
                                        <div class="flex items-center gap-2">
                                            <button wire:click="openEditNoteModal({{ $note->id }})"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-medium hover:bg-blue-100 transition">
                                                <i class="fas fa-pen text-[10px]"></i> Edit
                                            </button>
                                            <button wire:click="openDeleteNoteModal({{ $note->id }})"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100 transition">
                                                <i class="fas fa-trash text-[10px]"></i> Delete
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
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-lg p-6 card-shadow">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Status Change History :</h2>
                @if($statusHistory && $statusHistory->count() > 0)
                    @foreach ($statusHistory as $status)
                        <div class="history-item">
                            <p class="text-gray-900 text-sm font-medium mb-1">
                                {{ ucfirst(optional($status->changedBy)->first_name) }} {{ ucfirst(optional($status->changedBy)->last_name) }} :
                                {{ optional($status->created_at)->format('d M, Y h:i A') }}
                            </p>
                            <p class="text-gray-600 text-sm">Status changed from {{ $status->from_status }} to {{ $status->to_status }}</p>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p class="text-sm">No status history added yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <flux:modal name="delete-second-opinion-note" class="p-0" wire:close="closeDeleteNoteModal">
        <div x-data @click.outside="$wire.closeDeleteNoteModal()">
            <div>
                <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeDeleteNoteModal" />
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Note</h2>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this note?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteNoteModal"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteNote" wire:loading.attr="disabled"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                        Delete Note
                    </button>
                </div>
            </div>
        </div>
    </flux:modal>
    @endif
</div>
