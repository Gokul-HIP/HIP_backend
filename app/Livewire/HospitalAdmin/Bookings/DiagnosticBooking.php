<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\Diagnostic;
use App\Models\DiagnosticLabTest;
use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Models\Hospital;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Flux\Flux;

class DiagnosticBooking extends Component
{
    public string $search = '';
    public string $statusFilter = 'all';
    public string $testTypeFilter = 'all';
    public string $diagnosticFilter = 'all';
    public string $dateFilter = '';
    public ?int $id = null;

    public function clearDateFilter(): void
    {
        $this->dateFilter = '';
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'testTypeFilter', 'diagnosticFilter', 'dateFilter']);
    }

    protected function organizationDiagnosticIds(): array
    {
        $organizationId = Auth::user()->organization_id;

        return Hospital::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('diagnostic_center_id')
            ->pluck('diagnostic_center_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function findScopedBooking(int $id): ?DiagnosticTestBooking
    {
        return DiagnosticTestBooking::query()
            ->whereIn('diagnostic_center_id', $this->organizationDiagnosticIds())
            ->find($id);
    }

    public function openUpdateStatusModal(int $id): void
    {
        if (!$this->findScopedBooking($id)) {
            return;
        }

        $this->dispatch('openUpdateStatusModal', id: $id);
    }

    public function openDeleteBookingModal(int $id): void
    {
        if (!$this->findScopedBooking($id)) {
            return;
        }

        $this->id = $id;
        Flux::modal('delete-booking')->show();
    }

    public function closeDeleteBookingModal(): void
    {
        $this->id = null;
        Flux::modal('delete-booking')->close();
    }

    public function deleteBooking(): void
    {
        $booking = $this->id ? $this->findScopedBooking($this->id) : null;

        if (!$booking) {
            $this->closeDeleteBookingModal();
            return;
        }

        DiagnosticTestBookingStatus::query()
            ->where('diagnostic_test_booking_id', $booking->id)
            ->delete();

        $booking->delete();

        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
        $this->closeDeleteBookingModal();
        $this->dispatch('refreshDiagnosticTestBookings');
    }

    #[On('refreshDiagnosticTestBookings')]
    public function refreshDiagnosticTestBookings(): void
    {
        // Force a rerender after modal actions.
    }

    protected function filteredQuery(array $diagnosticIds)
    {
        return DiagnosticTestBooking::query()
            ->with(['member', 'diagnosticCenter'])
            ->whereIn('diagnostic_center_id', $diagnosticIds)
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%')
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%'])
                                ->orWhere('hip_id', 'like', '%' . $search . '%')
                                ->orWhere('mobile_num', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('diagnosticCenter', function ($diagnosticQuery) use ($search) {
                            $diagnosticQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->testTypeFilter !== 'all', function ($query) {
                if ($this->testTypeFilter === 'test') {
                    $query->whereIn('test_type', ['single', 'multi']);
                    return;
                }

                $query->where('test_type', $this->testTypeFilter);
            })
            ->when($this->diagnosticFilter !== 'all', function ($query) {
                $query->where('diagnostic_center_id', (int) $this->diagnosticFilter);
            })
            ->when($this->dateFilter !== '', function ($query) {
                $query->whereDate('booking_date', $this->dateFilter);
            });
    }

    public function render()
    {
        $diagnosticIds = $this->organizationDiagnosticIds();
        $organizationId = Auth::user()->organization_id;

        $baseScopedQuery = DiagnosticTestBooking::query()
            ->whereIn('diagnostic_center_id', $diagnosticIds);

        $bookings = $this->filteredQuery($diagnosticIds)
            ->orderByDesc('id')
            ->get();

        $availableDiagnostics = Diagnostic::query()
            ->where('organization_id', $organizationId)
            ->whereIn('id', $diagnosticIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $hospitalsByDiagnostic = Hospital::query()
            ->where('organization_id', $organizationId)
            ->whereIn('diagnostic_center_id', $diagnosticIds)
            ->get(['id', 'name', 'diagnostic_center_id'])
            ->groupBy('diagnostic_center_id');

        $allTestIds = $bookings
            ->pluck('test_items')
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $labTests = DiagnosticLabTest::query()
            ->whereIn('id', $allTestIds)
            ->get(['id', 'test_name', 'test_code'])
            ->keyBy('id');

        $bookingRows = $bookings->map(function (DiagnosticTestBooking $booking) use ($labTests, $hospitalsByDiagnostic) {
            $testItems = collect($booking->test_items ?? [])
                ->map(fn ($id) => $labTests->get((int) $id))
                ->filter()
                ->values();

            $primaryTest = $testItems->first();
            $remainingCount = max($testItems->count() - 1, 0);
            $bookingHospitals = $hospitalsByDiagnostic->get($booking->diagnostic_center_id, collect());

            $timeSlots = collect($booking->required_time_slots ?? [])->filter()->values();
            $timeLabel = match ($timeSlots->count()) {
                0 => '-',
                1 => (string) $timeSlots->first(),
                default => $timeSlots->first() . ' - ' . $timeSlots->last(),
            };

            $status = strtolower((string) ($booking->status ?? 'pending'));
            $statusStyle = match ($status) {
                'confirmed' => 'background:#DCFCE7; color:#16A34A;',
                'completed' => 'background:#DBEAFE; color:#2563EB;',
                'cancelled' => 'background:#FEE2E2; color:#DC2626;',
                default => 'background:#FEF3C7; color:#D97706;',
            };

            return [
                'id' => $booking->id,
                'apt_id' => 'APT' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                'name' => $booking->name ?: '-',
                'member_name' => $booking->member?->name ?: '-',
                'member_hip_id' => $booking->member?->hip_id ?: 'N/A',
                'mobile_number' => $booking->mobile_number ? '+91 ' . $booking->mobile_number : '-',
                'centre_name' => $booking->diagnosticCenter?->name ?: '-',
                'centre_meta' => $bookingHospitals->pluck('name')->filter()->unique()->join(', ') ?: 'DGN' . str_pad((string) ($booking->diagnostic_center_id ?? 0), 4, '0', STR_PAD_LEFT),
                'test_name' => $primaryTest?->test_name
                    ? ($remainingCount > 0 ? $primaryTest->test_name . ' +' . $remainingCount . ' more' : $primaryTest->test_name)
                    : ucfirst((string) $booking->test_type),
                'test_meta' => $primaryTest?->test_code ?: strtoupper((string) ($booking->test_type ?? '-')),
                'test_type_label' => match ($booking->test_type) {
                    'single' => 'Test',
                    'multi' => 'Test',
                    'package' => 'Package',
                    default => '-',
                },
                'booking_date' => $booking->booking_date?->format('M d, Y') ?: '-',
                'booking_time' => $timeLabel,
                'payment_mode_label' => DiagnosticTestBooking::paymentModeLabel(
                    (bool) $booking->is_online_payment,
                    $booking->payment_status
                ),
                'status_label' => ucfirst($status),
                'status_style' => $statusStyle,
            ];
        });

        return view('livewire.hospital-admin.bookings.diagnostic-booking', [
            'bookingRows' => $bookingRows,
            'availableDiagnostics' => $availableDiagnostics,
            'totalBookings' => $baseScopedQuery->count(),
            'pendingBookings' => (clone $baseScopedQuery)->where('status', 'pending')->count(),
            'cancelledBookings' => (clone $baseScopedQuery)->where('status', 'cancelled')->count(),
            'completedBookings' => (clone $baseScopedQuery)->where('status', 'completed')->count(),
            'upcomingBookings' => (clone $baseScopedQuery)->whereDate('booking_date', '>=', now()->toDateString())->count(),
        ]);
    }
}
