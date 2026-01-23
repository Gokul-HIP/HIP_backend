<?php

namespace App\Livewire\Admin\Organization\Hospital\Procedure;

use Livewire\Component;
use App\Models\Hospital;
use App\Services\ProcedureService;
use Flux\Flux;
use App\Models\Speciality;
use App\Models\Procedure;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class AddProcedure extends Component
{
    use WithFileUploads;
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
    public $recovery_from;
    public $recovery_to;
    public $recovery_unit;
    public $success_rate;
    public $hospitalization_days;
    public $procedure_image;
    public $remove_image = false;
    protected $procedureService;

    public function boot(ProcedureService $procedureService)
    {
        $this->procedureService = $procedureService;
    }

    public function mount($hospitalId)
    {
        $this->hospital_id = $hospitalId;
        
        $hospital = Hospital::find($hospitalId);
        if (!$hospital) {
            abort(404, 'Hospital not found');
        }
        
        $this->hospital = $hospital;
        $this->organization_id = $hospital->organization_id;
        $this->specialities = Speciality::where('hospital_id', $hospitalId)->get();
        
        $this->generateProcedureCode();
    }

    public function generateProcedureCode()
    {
        do {
            $number = rand(1000, 9999);
            $code = Str::slug($this->hospital->hospital_name, '-')
                . '-PROC-' . date('Y') . '-' . $number;
        } while (Procedure::where('procedure_code', $code)->exists());

        $this->procedure_code = strtoupper($code);
    }

    public function resetInput()
    {
        $this->reset([
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
            'remove_image'
        ]);
        $this->status = false;
        $this->generateProcedureCode();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->remove_image = false;
        $this->procedure_image = null;
        $this->dispatch('reset-file-input');
    }

    public function removeImage()
    {
        $this->procedure_image = null;
        $this->remove_image = true;
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('add-procedure')->close();
    }

    public function addProcedure()
    {
        $this->validate([
            'procedure_name' => 'required|string|max:255',
            'speciality_id' => 'required|exists:specialities,id',
            'description' => 'required|string',
            'estimated_time' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'recovery_from' => 'required|numeric|min:0',
            'recovery_to' => 'required|numeric|min:0',
            'recovery_unit' => 'required|string',
            'success_rate' => 'required|numeric|min:0',
            'hospitalization_days' => 'required|numeric|min:0',
            'procedure_image' => 'required|image|max:2048',
        ]);
    
        $procedureName = $this->procedure_name;
        $statusValue = $this->status ? 'active' : 'inactive';
    
        $procedureData = [
            'procedure_name' => $this->procedure_name,
            'procedure_code' => $this->procedure_code,
            'speciality_id'  => $this->speciality_id,
            'assign_doctor' => $this->assign_doctor,
            'description' => $this->description,
            'estimated_time' => $this->estimated_time,
            'cost' => $this->cost,
            'status' => $statusValue,
            'hospital_id' => $this->hospital_id,
            'organization_id' => $this->organization_id,
            'recovery_from' => $this->recovery_from,
            'recovery_to' => $this->recovery_to,
            'recovery_unit' => $this->recovery_unit,
            'success_rate' => $this->success_rate,
            'hospitalization_days' => $this->hospitalization_days,
        ];
    
        try {
            $this->procedureService->createProcedure($procedureData, $this->procedure_image);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate entry error
            if ($e->errorInfo[1] == 1062) {
                // Regenerate code and retry
                $this->generateProcedureCode();
                $procedureData['procedure_code'] = $this->procedure_code;
                
                $this->procedureService->createProcedure($procedureData, $this->procedure_image);
            } else {
                throw $e;
            }
        }
    
        $this->resetInput();
        Flux::modal('add-procedure')->close();
        
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Procedure ' . $procedureName . ' added successfully!'
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
            'procedure_image.required' => 'Procedure image field is required',
            'procedure_image.image' => 'Procedure image must be an image',
            'procedure_image.max' => 'Procedure image must be less than 2MB',
        ];
    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.procedure.add-procedure');
    }
}