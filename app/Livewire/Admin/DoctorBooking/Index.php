<?php

namespace App\Livewire\Admin\DoctorBooking;

use Livewire\Component;
use App\Models\DoctorBooking;
use App\Models\Doctor;
use App\Models\Hospital;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
    public $doctorFilter = 'all';
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

    public function updatingDoctorFilter()
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

    #[On('refreshDoctorBookings')]
    public function refreshDoctorBookings()
    {
        $this->render();
    }

    public function render()
    {
        $query = DoctorBooking::with(['member', 'hospital', 'doctor'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($memberQuery) {
                            $memberQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('hospital', function ($hospitalQuery) {
                            $hospitalQuery->where('hospital_name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('doctor', function ($doctorQuery) {
                            $doctorQuery->where('doctor_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->doctorFilter !== 'all', function ($q) {
                $q->where('doctor_id', $this->doctorFilter);
            })
            ->when($this->hospitalFilter !== 'all', function ($q) {
                $q->where('hospital_id', $this->hospitalFilter);
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('booking_date', $this->dateFilter);
            })
            ->orderBy('id', 'desc');

        $doctorBookings = $query->paginate(10);

        $doctorIds = DoctorBooking::distinct()->pluck('doctor_id')->filter();
        $availableDoctors = Doctor::whereIn('id', $doctorIds)->orderBy('doctor_name')->get();

        $hospitalIds = DoctorBooking::distinct()->pluck('hospital_id')->filter();
        $availableHospitals = Hospital::whereIn('id', $hospitalIds)->orderBy('hospital_name')->get();

        return view('livewire.admin.doctor-booking.index', [
            'doctorBookings' => $doctorBookings,
            'availableDoctors' => $availableDoctors,
            'availableHospitals' => $availableHospitals,
        ]);
    }
}
