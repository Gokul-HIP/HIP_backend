<?php

namespace App\Livewire\DoctorAdmin\Concerns;

use App\Models\DoctorBooking;
use App\Support\DoctorPatientViewData;
use Illuminate\Pagination\LengthAwarePaginator;

trait ManagesAppointmentHistory
{

    public bool $showAppointmentHistoryModal = false;

    public ?int $historyBookingId = null;

    public ?array $historyPatient = null;

    public array $historyStats = [
        'total' => 0,
        'completed' => 0,
        'upcoming' => 0,
        'cancelled' => 0,
    ];

    public string $historySearch = '';

    public string $historyTypeFilter = 'all';

    public string $historyStatusFilter = 'all';

    public string $historyDateFrom = '';

    public string $historyDateTo = '';

    public bool $showHistoryDateFilter = false;

    public function updatingHistorySearch(): void
    {
        $this->resetPage('historyPage');
    }

    public function updatingHistoryTypeFilter(): void
    {
        $this->resetPage('historyPage');
    }

    public function updatingHistoryStatusFilter(): void
    {
        $this->resetPage('historyPage');
    }

    public function updatingHistoryDateFrom(): void
    {
        $this->resetPage('historyPage');
    }

    public function updatingHistoryDateTo(): void
    {
        $this->resetPage('historyPage');
    }

    public function openAppointmentHistory(int $bookingId): void
    {
        $booking = $this->findDoctorBooking($bookingId);

        if (! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Appointment history is unavailable.');

            return;
        }

        $doctor = $this->doctor();
        $allBookings = DoctorPatientViewData::patientBookings($booking, $doctor);

        $this->historyBookingId = $booking->id;
        $this->historyPatient = DoctorPatientViewData::buildHistoryPatient($booking, $doctor);
        $this->historyStats = DoctorPatientViewData::buildHistoryStats($allBookings);
        $this->historySearch = '';
        $this->historyTypeFilter = 'all';
        $this->historyStatusFilter = 'all';
        $this->historyDateFrom = '';
        $this->historyDateTo = '';
        $this->showHistoryDateFilter = false;
        $this->resetPage('historyPage');
        $this->showAppointmentHistoryModal = true;
    }

    public function closeAppointmentHistory(): void
    {
        $this->showAppointmentHistoryModal = false;
        $this->historyBookingId = null;
        $this->historyPatient = null;
        $this->showHistoryDateFilter = false;
    }

    public function toggleHistoryDateFilter(): void
    {
        $this->showHistoryDateFilter = ! $this->showHistoryDateFilter;
    }

    public function clearHistoryDateFilter(): void
    {
        $this->historyDateFrom = '';
        $this->historyDateTo = '';
        $this->resetPage('historyPage');
    }

    public function viewAppointmentDetail(int $bookingId): void
    {
        $this->closeAppointmentHistory();
        $this->openUpdateModal($bookingId);
    }

    public function goToHistoryPage(int $page): void
    {
        $this->setPage($page, 'historyPage');
    }

    public function previousHistoryPage(): void
    {
        $this->previousPage('historyPage');
    }

    public function nextHistoryPage(): void
    {
        $this->nextPage('historyPage');
    }

    protected function appointmentHistoryPaginator(): LengthAwarePaginator
    {
        if (! $this->historyBookingId) {
            return new LengthAwarePaginator([], 0, 10, 1, ['pageName' => 'historyPage']);
        }

        $booking = $this->findDoctorBooking($this->historyBookingId);

        if (! $booking) {
            return new LengthAwarePaginator([], 0, 10, 1, ['pageName' => 'historyPage']);
        }

        $doctor = $this->doctor();
        $allBookings = DoctorPatientViewData::patientBookings($booking, $doctor);
        $filtered = DoctorPatientViewData::filteredHistoryBookings(
            $allBookings,
            $this->historySearch,
            $this->historyTypeFilter,
            $this->historyStatusFilter,
            $this->historyDateFrom ?: null,
            $this->historyDateTo ?: null
        );

        $page = $this->getPage('historyPage');
        $perPage = 10;
        $total = $filtered->count();
        $items = $filtered
            ->forPage($page, $perPage)
            ->map(fn (DoctorBooking $item) => DoctorPatientViewData::mapHistoryRow($item))
            ->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'pageName' => 'historyPage',
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
