<?php

namespace App\Livewire\Admin\Organization\Hospital\ViewDoctor;

use Livewire\Component;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\Speciality;
use App\Models\Hospital;
use App\Services\AssignDoctorService;
use Flux\Flux;
use Livewire\Attributes\On;

class LinkedDoctor extends Component
{
    public int $hospitalId;

    public string $search = '';
    public string $status = 'all';
    public string $sort = 'name_asc';
    public int $delete_id = 0;

    protected $listeners = ['assignment-updated' => '$refresh'];

    protected $assignDoctorService;

    public function boot(AssignDoctorService $assignDoctorService)
    {
        $this->assignDoctorService = $assignDoctorService;
    }

    public function mount($hospitalId)
    {
        $this->hospitalId = (int) $hospitalId;
    }

    public function editAssignment(int $assignmentId)
    {
        $this->dispatch('open-edit-assignment', assignmentId: $assignmentId);
    }

    public function delete(int $assignmentId)
    {
        $this->delete_id = $assignmentId;
        Flux::modal('delete-assignment')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-assignment')->close();
    }

    public function destroy()
    {
        $assignment = $this->assignDoctorService->findAssignment($this->delete_id);
        $doctorId   = $assignment->doctor_id;

        $doctorName = Doctor::find($doctorId)->first();

        $this->assignDoctorService->deleteAssignmentsByDoctorAndHospital(
            $doctorId,
            $this->hospitalId
        );

        $this->assignDoctorService->updateDoctorAssignmentsFromAllAssignments($doctorId);

        Flux::modal('delete-assignment')->close();
        $this->dispatch('assignment-updated');
        $this->dispatch('toast', type: 'success', message: 'Doctor '.$doctorName->doctor_name.' removed from this hospital successfully');
    }

    #[On('assignment')]
    public function relod()
    {   
        $this->render();
    }

    public function render()
    {
        $filters = [
            'status' => $this->status,
            'search' => $this->search,
            'sort' => $this->sort,
        ];

        $doctors = $this->assignDoctorService->getDoctorsWithAssignments($this->hospitalId, $filters);

        $doctors = $doctors->map(function ($doctor) {
            $assignments = $doctor->assignments
                ->where('hospital_id', $this->hospitalId)
                ->where('status', 'active');

            $procedureIds = $assignments->pluck('procedure_ids')
                ->flatten()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            $procedures = $this->assignDoctorService->getProceduresByIds($procedureIds);
            $doctor->assigned_procedure_names = $procedures->pluck('procedure_name')->toArray();

            $specialityIds = $this->assignDoctorService->getSpecialityIdsFromProcedures($procedureIds);
            $doctor->assigned_speciality_names = Speciality::whereIn('id', $specialityIds)
                ->pluck('speciality_name')
                ->toArray();

            $doctor->assignment_id = $assignments->first()?->id;

            return $doctor;
        });

        return view('livewire.admin.organization.hospital.view-doctor.linked-doctor', [
            'hospital' => Hospital::findOrFail($this->hospitalId),
            'doctors'  => $doctors,
        ]);
    }

}