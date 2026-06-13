<?php

namespace App\Livewire\Admin\SecondOpinion;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\SecondOpinion;
use App\Models\SecondOpinionStatus;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

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
        $this->dispatch('openUpdateSecondOpinionStatusModal', id: $id);
        $this->render();
    }

    public function openDeleteBookingModal($id)
    {
        $this->id = $id;
        Flux::modal('delete-second-opinion')->show();
    }

    public function closeDeleteBookingModal()
    {
        $this->id = null;
        Flux::modal('delete-second-opinion')->close();
        $this->render();
    }

    public function deleteBooking()
    {
        $secondOpinion = SecondOpinion::find($this->id);

        if (! $secondOpinion) {
            $this->closeDeleteBookingModal();

            return;
        }

        SecondOpinionStatus::where('second_opinion_id', $this->id)->delete();
        $secondOpinion->delete();

        $this->dispatch('refreshSecondOpinions');
        $this->closeDeleteBookingModal();
        $this->dispatch('toast', type: 'success', message: 'Second opinion booking deleted successfully!');
    }

    #[On('refreshSecondOpinions')]
    public function refreshSecondOpinions()
    {
        $this->render();
    }

    public function render()
    {
        $query = SecondOpinion::with(['member', 'branch', 'doctor', 'speciality'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('patient_name', 'like', '%' . $this->search . '%')
                        ->orWhere('diagnosis', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($memberQuery) {
                            $memberQuery->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                ->orWhere('hip_id', 'like', '%' . $this->search . '%')
                                ->orWhere('mobile_num', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('branch', function ($branchQuery) {
                            $branchQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('doctor', function ($doctorQuery) {
                            $doctorQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('speciality', function ($specialityQuery) {
                            $specialityQuery->where('name', 'like', '%' . $this->search . '%');
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
                $q->where('branch_id', $this->hospitalFilter);
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('preferred_date', $this->dateFilter);
            })
            ->orderBy('id', 'desc');

        $secondOpinions = $query->paginate(10);

        $doctorIds = SecondOpinion::distinct()->pluck('doctor_id')->filter();
        $availableDoctors = Doctor::whereIn('id', $doctorIds)->orderBy('name')->get();

        $hospitalIds = SecondOpinion::distinct()->pluck('branch_id')->filter();
        $availableHospitals = Hospital::whereIn('id', $hospitalIds)->orderBy('name')->get();

        return view('livewire.admin.second-opinion.index', [
            'secondOpinions' => $secondOpinions,
            'availableDoctors' => $availableDoctors,
            'availableHospitals' => $availableHospitals,
        ]);
    }
}
