<?php

namespace App\Livewire\Admin\Organization\Hospital\Procedure;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hospital;
use App\Services\ProcedureService;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Flux\Flux;
use App\Models\Speciality;

class EditProcedure extends Component
{
    use WithFileUploads;

    public $procedure_id;
    
    #[Rule("required|string|max:255")]
    public $procedure_name;
    
    public $procedure_code;
    
    #[Rule("required|exists:specialities,id")]
    public $speciality_id;
    
    public $assign_doctor;
    
    #[Rule("required|string")]
    public $description;
    
    #[Rule("required|string")]
    public $estimated_time;
    
    #[Rule("required|numeric|min:0")]
    public $cost;
    
    public $status = false;
    public $organization_id;
    public $hospital_id;
    public $hospital;
    public $specialities = [];
    
    // New fields
    #[Rule("required|numeric|min:0")]
    public $recovery_from;
    
    #[Rule("required|numeric|min:0")]
    public $recovery_to;
    
    #[Rule("required|string")]
    public $recovery_unit;
    
    #[Rule("required|numeric|min:0")]
    public $success_rate;
    
    #[Rule("required|numeric|min:0")]
    public $hospitalization_days;
    
    #[Rule("nullable|image|max:2048")]
    public $procedure_image;
    
    public $old_image_path;
    public $remove_image = false;
    
    protected $procedureService;

    public function boot(ProcedureService $procedureService)
    {
        $this->procedureService = $procedureService;
    }

    #[On('edit')]
    public function edit($id)
    {
        // Reset all fields first
        $this->resetInput();
        
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

        $this->success_rate = $procedure->success_rate;
        $this->hospitalization_days = $procedure->hospitalization_days;
        $this->old_image_path = $procedure->image;
        
        // Parse recovery_time (e.g., "2 days to 4 days")
        if ($procedure->recovery_time) {
            $parts = explode(' to ', $procedure->recovery_time);
            if (count($parts) == 2) {
                // Extract "2 days" from first part
                $fromParts = explode(' ', trim($parts[0]));
                $this->recovery_from = $fromParts[0] ?? 0;
                $this->recovery_unit = $fromParts[1] ?? 'days';
                
                // Extract "4" from second part (just the number)
                $toParts = explode(' ', trim($parts[1]));
                $this->recovery_to = $toParts[0] ?? 0;
            }
        }
        
        $this->remove_image = false;
        $this->procedure_image = null;

        Flux::modal('edit-procedure')->show();
    }

    public function removeImage()
    {
        $this->procedure_image = null;
        $this->remove_image = true;
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->procedure_image = null;
        $this->remove_image = false;
        $this->dispatch('reset-file-input');
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
            'status',
            'recovery_from',
            'recovery_to',
            'recovery_unit',
            'success_rate',
            'hospitalization_days',
            'procedure_image',
            'old_image_path',
            'remove_image'
        ]);
        $this->status = false;
        $this->remove_image = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-procedure')->close();
    }

    public function updateProcedure()
    {
        $this->validate();

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
            'recovery_from' => $this->recovery_from,
            'recovery_to' => $this->recovery_to,
            'recovery_unit' => $this->recovery_unit,
            'success_rate' => $this->success_rate,
            'hospitalization_days' => $this->hospitalization_days,
        ];

        $this->procedureService->updateProcedure(
            $this->procedure_id,
            $procedureData,
            $this->procedure_image,
            $this->remove_image
        );

        $this->resetInput();
        Flux::modal('edit-procedure')->close();
        
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Procedure ' . $procedureName . ' updated successfully!'
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
            'recovery_from.required' => 'Recovery from field is required',
            'recovery_from.numeric' => 'Recovery from must be a valid number',
            'recovery_from.min' => 'Recovery from must be at least 0',
            'recovery_to.required' => 'Recovery to field is required',
            'recovery_to.numeric' => 'Recovery to must be a valid number',
            'recovery_to.min' => 'Recovery to must be at least 0',
            'recovery_unit.required' => 'Recovery unit field is required',
            'success_rate.required' => 'Success rate field is required',
            'success_rate.numeric' => 'Success rate must be a valid number',
            'success_rate.min' => 'Success rate must be at least 0',
            'hospitalization_days.required' => 'Hospitalization days field is required',
            'hospitalization_days.numeric' => 'Hospitalization days must be a valid number',
            'hospitalization_days.min' => 'Hospitalization days must be at least 0',
            'procedure_image.image' => 'Procedure image must be an image',
            'procedure_image.max' => 'Procedure image must be less than 2MB',
        ];
    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.procedure.edit-procedure');
    }
}