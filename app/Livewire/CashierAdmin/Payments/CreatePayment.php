<?php

namespace App\Livewire\CashierAdmin\Payments;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use App\Models\Procedure;
use App\Models\DiagnosticLabTest;
use App\Models\DiagnosticPackage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Flux\Flux;
use Livewire\WithPagination;

class CreatePayment extends Component
{
    use WithPagination;

    // State used in the Livewire view
    public $phoneSearch = '';
    public $selectedMemberId = null;

    public $includesProcedures = true;
    public $includesDiagnostics = true;
    public $includesPharmacy = true;
    public $step = 1;
    public $member;
    public $familyMembers = [];
    public $first_name = '';
    public $last_name = '';
    public $mobile = '';
    public $gender = '';
    public $dob = '';

    /** Step 2: Procedures */
    public $procedureSearch = '';
    public $selectedProcedureIds = [];

    /** Step 3: Lab (tests & packages for hospital's diagnostic center) */
    public $labSearch = '';
    public $labTab = 'tests';
    public $selectedLabTestIds = [];
    public $selectedLabPackageIds = [];

    public function mount(){
        $this->phoneSearch = '';
        $this->selectedMemberId = null;
        $this->includesProcedures = true;
        $this->includesDiagnostics = true;
        $this->includesPharmacy = true;
        $this->step = 1;
        $this->familyMembers = [];
    }

    public function updatedPhoneSearch($value)
    {
        $this->searchMember();
    }


    public function searchMember()
    {
        if (! trim($this->phoneSearch)) {
            $this->member = null;
            $this->familyMembers = [];
            return;
        }

        $member = Persons::where('mobile', 'like', '%' . $this->phoneSearch . '%')->first();

        if (! $member) {
            $this->member = null;
            $this->familyMembers = [];
            return;
        }

        $this->member = $member;

        $this->familyMembers = Persons::where('parent_id', $member->parent_id)
            ->where('id', '!=', $member->id)
            ->get();
    }

    public function selectMember($id)
    {
        $this->selectedMemberId = $id;
    }

    public function addFamilyMember(){

        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:255|unique:persons,mobile',
            'gender' => 'required|string|max:255',
            'dob' => 'required|date',
        ]);

        if(Persons::where('mobile', $this->mobile)->exists()){
            $this->addError('mobile', 'Mobile number already exists.');
            return;
        }

        if(!$this->member){
            $this->addError('member', 'Member not found.');
            return;
        }

        if($this->mobile){
           $mobile = HIPUser::where('mobile_num', $this->mobile)->first();
           if($mobile){
            $this->addError('mobile', 'Mobile number already exists.');
            return;
           }
        }

        if(!$this->member->parent_id){
            $this->addError('member', 'Member parent not found.');
            return;
        }

       DB::transaction(function () {

        Persons::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'parent_id' => $this->member->parent_id,
            'is_primary' => false,
        ]);

       });

       $this->familyMembers = Persons::where('parent_id', $this->member->parent_id)
       ->where('id', '!=', $this->member->id)
       ->get();

       Flux::modal('add-member')->close();
       $this->dispatch(
            'toast',
            type: 'success',
            message: 'Family member added successfully!'
        );
       // dd($person);

       $this->reset(['first_name', 'last_name', 'mobile', 'gender', 'dob']);
       $this->resetErrorBag();
       $this->resetValidation();
       $this->dispatch('reset-file-input');

    }

    public function openAddMemberModal()
    {
         Flux::modal('add-member')->show();
    }

    public function closeModal()
    {
        Flux::modal('add-member')->close();
    }

    public function nextStep()
    {
        if ($this->step < 6) {
            $this->step++;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function cancelPayment()
    {
        $this->step = 1;
        $this->includesProcedures   = true;
        $this->includesDiagnostics  = true;
        $this->includesPharmacy     = true;
    }

    public function savePayment()
    {
        // Placeholder for future save logic
    }

    public function goToStep($step)
    {
        $step = (int) $step;

        if ($step >= 1 && $step <= 6 && $step <= $this->step) {
            $this->step = $step;
        }
    }

    /**
     * Get hospital_id and organization_id from the logged-in cashier admin user.
     */
    protected function getHospitalScope(): array
    {
        $user = Auth::guard('filament')->user();
        if (!$user || !($user instanceof HIPUser)) {
            return ['hospital_id' => null, 'organization_id' => null];
        }
        return [
            'hospital_id' => $user->hospital_id,
            'organization_id' => $user->organization_id,
        ];
    }

    /**
     * Add a procedure to the selected list (only if it belongs to the user's hospital).
     */
    public function addProcedure($id)
    {
        $scope = $this->getHospitalScope();
        if (!$scope['hospital_id']) {
            return;
        }
        $procedure = Procedure::where('id', $id)
            ->where('hospital_id', $scope['hospital_id'])
            ->first();
        if ($procedure && !in_array((int) $id, $this->selectedProcedureIds, true)) {
            $this->selectedProcedureIds[] = (int) $id;
        }
    }

    /**
     * Remove a procedure from the selected list.
     */
    public function removeProcedure($id)
    {
        $this->selectedProcedureIds = array_values(
            array_filter($this->selectedProcedureIds, fn ($sid) => $sid !== (int) $id)
        );
    }

    /**
     * Get diagnostic_center_id for the logged-in user's hospital.
     */
    protected function getDiagnosticCenterId(): ?int
    {
        $user = Auth::guard('filament')->user();
        if (!$user || !($user instanceof HIPUser) || !$user->hospital_id) {
            return null;
        }
        $hospital = Hospital::find($user->hospital_id);
        return $hospital && $hospital->diagnostic_center_id ? (int) $hospital->diagnostic_center_id : null;
    }

    /**
     * Add a lab test to the selected list (only for this hospital's diagnostic center).
     */
    public function addLabTest($id)
    {
        $diagnosticId = $this->getDiagnosticCenterId();
        if (!$diagnosticId) {
            return;
        }
        $exists = DiagnosticLabTest::where('id', $id)
            ->where('diagnostic_id', $diagnosticId)
            ->where('test_status', 'active')
            ->exists();
        if ($exists && !in_array((int) $id, $this->selectedLabTestIds, true)) {
            $this->selectedLabTestIds[] = (int) $id;
        }
    }

    /**
     * Add a lab package to the selected list (only for this hospital's diagnostic center).
     */
    public function addLabPackage($id)
    {
        $diagnosticId = $this->getDiagnosticCenterId();
        if (!$diagnosticId) {
            return;
        }
        $exists = DiagnosticPackage::where('id', $id)
            ->where('diagnostic_id', $diagnosticId)
            ->where('status', 'active')
            ->exists();
        if ($exists && !in_array((int) $id, $this->selectedLabPackageIds, true)) {
            $this->selectedLabPackageIds[] = (int) $id;
        }
    }

    /**
     * Remove a lab test from the selected list.
     */
    public function removeLabTest($id)
    {
        $this->selectedLabTestIds = array_values(
            array_filter($this->selectedLabTestIds, fn ($sid) => $sid !== (int) $id)
        );
    }

    /**
     * Remove a lab package from the selected list.
     */
    public function removeLabPackage($id)
    {
        $this->selectedLabPackageIds = array_values(
            array_filter($this->selectedLabPackageIds, fn ($sid) => $sid !== (int) $id)
        );
    }

    public function render()
    {
        $scope = $this->getHospitalScope();
        $hospitalId = $scope['hospital_id'];
        $organizationId = $scope['organization_id'];

        $availableProcedures = collect([]);
        $selectedProcedures = collect([]);

        if ($hospitalId) {
            $query = Procedure::with('speciality')
                ->where('hospital_id', $hospitalId)
                ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
                ->where('status', 'active');

            if ($this->procedureSearch !== '') {
                $term = '%' . trim($this->procedureSearch) . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('procedure_name', 'like', $term)
                        ->orWhere('procedure_code', 'like', $term)
                        ->orWhereHas('speciality', fn ($s) => $s->where('speciality_name', 'like', $term));
                });
            }

            $availableProcedures = $query->whereNotIn('id', $this->selectedProcedureIds)->orderBy('procedure_name')->get();

            if (!empty($this->selectedProcedureIds)) {
                $ids = array_map('intval', $this->selectedProcedureIds);
                $selectedProcedures = Procedure::with('speciality')
                    ->where('hospital_id', $hospitalId)
                    ->whereIn('id', $ids)
                    ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                    ->get();
            }
        }

        // Step 3: Lab – tests and packages for hospital's diagnostic center
        $diagnosticId = $this->getDiagnosticCenterId();
        $availableLabTests = collect([]);
        $selectedLabTests = collect([]);

        if ($diagnosticId) {
            $labSearchTerm = trim($this->labSearch ?? '');
            $isTestsTab = ($this->labTab ?? 'tests') === 'tests';

            if ($isTestsTab) {
                $testsQuery = DiagnosticLabTest::where('diagnostic_id', $diagnosticId)
                    ->where('test_status', 'active')
                    ->whereNotIn('id', $this->selectedLabTestIds);
                if ($labSearchTerm !== '') {
                    $term = '%' . $labSearchTerm . '%';
                    $testsQuery->where(function ($q) use ($term) {
                        $q->where('test_name', 'like', $term)
                            ->orWhere('test_category', 'like', $term)
                            ->orWhere('test_code', 'like', $term);
                    });
                }
                $availableLabTests = $testsQuery->orderBy('test_name')->get();
            } else {
                $packagesQuery = DiagnosticPackage::where('diagnostic_id', $diagnosticId)
                    ->where('status', 'active')
                    ->whereNotIn('id', $this->selectedLabPackageIds);
                if ($labSearchTerm !== '') {
                    $term = '%' . $labSearchTerm . '%';
                    $packagesQuery->where(function ($q) use ($term) {
                        $q->where('name', 'like', $term)
                            ->orWhere('code', 'like', $term)
                            ->orWhere('description', 'like', $term);
                    });
                }
                $availableLabTests = $packagesQuery->orderBy('name')->get();
            }

            $selectedTests = collect([]);
            $selectedPackages = collect([]);
            if (!empty($this->selectedLabTestIds)) {
                $selectedTests = DiagnosticLabTest::where('diagnostic_id', $diagnosticId)
                    ->whereIn('id', $this->selectedLabTestIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $this->selectedLabTestIds)) . ')')
                    ->get();
            }
            if (!empty($this->selectedLabPackageIds)) {
                $selectedPackages = DiagnosticPackage::where('diagnostic_id', $diagnosticId)
                    ->whereIn('id', $this->selectedLabPackageIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $this->selectedLabPackageIds)) . ')')
                    ->get();
            }
            $selectedLabTests = $selectedTests->map(fn ($t) => (object) ['type' => 'test', 'model' => $t])
                ->concat($selectedPackages->map(fn ($p) => (object) ['type' => 'package', 'model' => $p]));
        }

        return view('livewire.cashier-admin.payments.create-payment', [
            'availableProcedures' => $availableProcedures,
            'selectedProcedures' => $selectedProcedures,
            'availableLabTests' => $availableLabTests,
            'selectedLabTests' => $selectedLabTests,
        ]);
    }
}
