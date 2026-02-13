<?php

namespace App\Livewire\Admin\Doctor;

use Livewire\Component;
use App\Services\DoctorProfileService;
use Livewire\Attributes\On;
use Flux\Flux;
use Livewire\WithPagination;

class DoctorProfile extends Component
{
    use WithPagination;
    public $delete_id;
    public $search = '';
    public $status = 'all';
    public $sort = 'name_asc';
    protected $paginationTheme = 'tailwind';

    protected $doctorProfileService;

    public function boot(DoctorProfileService $doctorProfileService)
    {
        $this->doctorProfileService = $doctorProfileService;
    }

    // public function mount()
    // {
    // }

    // #[On("relod-doc")]
    // public function relodDoc()
    // {
    //     $this->dispatch('relod-doctor');
    // }

    #[On("relod-doc")]
    public function render()
    {
        $filters = [
            'status' => $this->status,
            'search' => $this->search,
            'sort' => $this->sort,
        ];
    
        $doctors = $this->doctorProfileService->getDoctorsPaginated($filters, 10);
    
        return view('livewire.admin.doctor.doctor-profile', [
            'doctors' => $doctors
        ]);
    }     

    public function delete($id)
    {
        $this->delete_id = $id;
        Flux::modal('delete-doctor')->show();
        $this->render();
    }
    
    public function closeModal()
    {
        Flux::modal('delete-doctor')->close();
        $this->render();
    }

    public function destroy()
    {
        $doctor = $this->doctorProfileService->findDoctor($this->delete_id);
        $doctorName = $doctor->name;

        $this->doctorProfileService->deleteDoctor($this->delete_id);

        Flux::modal('delete-doctor')->close();
        $this->render();
        
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Doctor '.$doctorName.' deleted successfully!'
        );

    }

    public function edit($id)
    {
        // dd($id);
        $this->dispatch('edit-doctor',$id);
        
    }

    public function viewDoctor($id){

        $this->dispatch('view-doc',$id);

    }

}
