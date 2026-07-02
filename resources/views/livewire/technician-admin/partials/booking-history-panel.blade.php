@if($showBookingHistoryModal && $historyPatient)
    <div class="pp-overlay" wire:click="closeBookingHistory"></div>
    <aside class="pp-panel" role="dialog" aria-label="Booking History">
        <div class="pp-panel-header">
            <h2>Diagnostic Booking History</h2>
            <button type="button" class="pp-panel-close" wire:click="closeBookingHistory"><i class="fas fa-times"></i></button>
        </div>
        <div class="pp-panel-body">
            <p class="text-sm text-gray-600 mb-2">{{ $historyPatient['name'] }} • {{ $historyPatient['uhid'] }}</p>
            <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                <div class="bg-gray-50 rounded p-3"><strong>Total:</strong> {{ $historyStats['total'] ?? 0 }}</div>
                <div class="bg-gray-50 rounded p-3"><strong>Completed:</strong> {{ $historyStats['completed'] ?? 0 }}</div>
                <div class="bg-gray-50 rounded p-3"><strong>Upcoming:</strong> {{ $historyStats['upcoming'] ?? 0 }}</div>
                <div class="bg-gray-50 rounded p-3"><strong>Cancelled:</strong> {{ $historyStats['cancelled'] ?? 0 }}</div>
            </div>
            <div class="pp-timeline">
                @foreach($historyBookings as $item)
                    <div class="pp-timeline-item">
                        <div class="pp-timeline-label">{{ $item->booking_date?->format('d M Y') ?: '—' }}</div>
                        <div class="pp-timeline-line">
                            APT-{{ str_pad((string) $item->id, 4, '0', STR_PAD_LEFT) }} •
                            {{ ucfirst((string) $item->status) }} •
                            {{ $item->diagnosticCenter?->name ?: ($item->branch?->name ?: 'Diagnostic Center') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </aside>
@endif
