<?php

namespace App\Livewire\Admin\Organization\Hospital\Procedure;

use Livewire\Component;
use App\Models\Hospital;
use App\Services\ProcedureService;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\Speciality;

class EditProcedure extends Component
{
    public $procedure_id;
    public $procedure_name;
    public $procedure_code;
    public $speciality_id;
    public $assign_doctor;
    public $description;
    public $estimated_time;
    public $cost;
    public $status = false;
    public $organization_id;
    public $hospital_id;
    public $hospital;
    public $specialities = [];
    protected $procedureService;

    public function boot(ProcedureService $procedureService)
    {
        $this->procedureService = $procedureService;
    }

    #[On('edit')]
    public function edit($id)
    {
        $this->procedure_id = $id;
        $procedure = $this->procedureService->findProcedure($id);
        $this->hospital_id = $procedure->hospital_id;
        $this->specialities = Speciality::where('hospital_id', $this->hospital_id)->get();
        $this->organization_id = $procedure->organization_id;
        $this->hospital = Hospital::find($this->hospital_id);
        
        $this->procedure_name = $procedure->procedure_name;
        $this->procedure_code = $procedure->procedure_code;
        $this->speciality_id = $procedure->speciality_id;
        $this->assign_doctor = $procedure->assign_doctor;
        $this->description = $procedure->description;
        $this->estimated_time = $procedure->estimated_time;
        $this->cost = $procedure->cost;
        $this->status = $procedure->status === 'active';

        Flux::modal('edit-procedure')->show();
    }

    public function resetInput()
    {
        $this->reset([
            'procedure_id',
            'procedure_name',
            'procedure_code',
            'speciality_id',
            'assign_doctor',
            'description',
            'estimated_time',
            'cost',
            'status'
        ]);
        $this->status = false;
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-procedure')->close();
    }

    public function updateProcedure()
    {
        $this->validate([
            'procedure_name' => 'required|string|max:255',
            'speciality_id' => 'required|exists:specialities,id',
            'description' => 'required|string',
            'estimated_time' => 'required|string',
            'cost' => 'required|numeric|min:0',
        ]);

        $statusValue = $this->status ? 'active' : 'inactive';

        $procedureName = $this->procedure_name;

        $procedureData = [
            'procedure_name' => $this->procedure_name,
            'speciality_id' => $this->speciality_id,
            'assign_doctor' => $this->assign_doctor,
            'description' => $this->description,
            'estimated_time' => $this->estimated_time,
            'cost' => $this->cost,
            'status' => $statusValue,
        ];

        $this->procedureService->updateProcedure($this->procedure_id, $procedureData);

        $this->resetInput();
        Flux::modal('edit-procedure')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Procedure '.$procedureName.' updated successfully!'
        );
        $this->dispatch('reloadProcedures');

    }

    public function messages()
    {
        return [
            'procedure_name.required' => 'Procedure name field is required',
            'speciality_id.required' => 'Speciality field is required',
            'speciality_id.exists' => 'Speciality not found',
            'description.required' => 'Description field is required',
            'estimated_time.required' => 'Estimated time field is required',
            'cost.required' => 'Cost field is required',
            'cost.numeric' => 'Cost must be a valid number',
            'cost.min' => 'Cost must be at least 0',
        ];
    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.procedure.edit-procedure');
    }
}

