<?php

namespace App\Livewire\Admin\ProcedureBooking;

use Livewire\Component;
use App\Models\ProcedureBooking;
use App\Models\Procedure;
use App\Models\Hospital;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\ProcedureBookingStatus;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
    public $procedureFilter = 'all';
    public $hospitalFilter = 'all';
    public $dateFilter = '';
    public $id;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingProcedureFilter()
    {
        $this->resetPage();
    }

    public function updatingHospitalFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function clearDateFilter()
    {
        $this->dateFilter = '';
        $this->resetPage();
    }

    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->dispatch('openUpdateStatusModal', id: $id);
        $this->render();
    }

    public function openDeleteBookingModal($id)
    {
        $this->id = $id;
        Flux::modal('delete-booking')->show();
    }

    public function closeDeleteBookingModal()
    {
        $this->id = null;
        Flux::modal('delete-booking')->close();
        $this->render();
    }

    public function deleteBooking()
    {
        $procedureBooking = ProcedureBooking::find($this->id);
        
        $procedureBookingStatuses = ProcedureBookingStatus::where('procedure_booking_id', $this->id)->get();

        foreach ($procedureBookingStatuses as $status) {
            $status->delete();
        }
        
        $procedureBooking->delete();

        $this->dispatch('refreshProcedureBookings');
        $this->closeDeleteBookingModal();
        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
    }

    #[On('refreshProcedureBookings')]
    public function refreshProcedureBookings()
    {
        $this->render();
    }

    public function render()
    {
        $query = ProcedureBooking::with(['member', 'hospital', 'procedure'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($memberQuery) {
                            $memberQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('hospital', function ($hospitalQuery) {
                            $hospitalQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('procedure', function ($procedureQuery) {
                            $procedureQuery->where('procedure_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->procedureFilter !== 'all', function ($q) {
                $q->where('procedure_id', $this->procedureFilter);
            })
            ->when($this->hospitalFilter !== 'all', function ($q) {
                $q->where('hospital_id', $this->hospitalFilter);
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('booking_date', $this->dateFilter);
            })
            ->orderBy('id', 'desc');

        $procedureBookings = $query->paginate(10);

        $procedureIds = ProcedureBooking::distinct()->pluck('procedure_id')->filter();
        $availableProcedures = Procedure::whereIn('id', $procedureIds)->orderBy('procedure_name')->get();

        $hospitalIds = ProcedureBooking::distinct()->pluck('hospital_id')->filter();
        $availableHospitals = Hospital::whereIn('id', $hospitalIds)->orderBy('name')->get();

        return view('livewire.admin.procedure-booking.index', [
            'procedureBookings' => $procedureBookings,
            'availableProcedures' => $availableProcedures,
            'availableHospitals' => $availableHospitals,
        ]);
    }
}

