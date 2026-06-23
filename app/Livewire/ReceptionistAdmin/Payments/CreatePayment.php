<?php

namespace App\Livewire\ReceptionistAdmin\Payments;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\HIPCard;
use App\Models\Persons;
use App\Models\Procedure;
use App\Models\DiagnosticLabTest;
use App\Models\DiagnosticPackage;
use App\Models\Invoice;
use App\Services\Api\PaymentApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class CreatePayment extends Component
{
    use WithPagination;
    use WithFileUploads;

    // State used in the Livewire view
    public $phoneSearch = '';
    public $selectedMemberId = null;

    public $includesProcedures = true;
    public $includesDiagnostics = true;
    public $includesPharmacy = true;
    public bool $canSelectProcedures = true;
    public bool $canSelectDiagnostics = true;
    public bool $canSelectPharmacy = true;
    public $step = 1;
    public $member;
    public $familyMembers = [];
    public $first_name = '';
    public $last_name = '';
    public $mobile = '';
    public $gender = '';
    public $dob = '';
    public bool $showAddMemberModal = false;

    /** Step 2: Procedures */
    public $procedureSearch = '';
    public $selectedProcedureIds = [];

    /** Step 3: Lab (tests & packages for hospital's diagnostic center) */
    public $labSearch = '';
    public $labTab = 'tests';
    public $selectedLabTestIds = [];
    public $selectedLabPackageIds = [];

    /** Step 4: Pharmacy */
    public $prescriptionFile = null;
    public $pharmacyAmount = '';

    /** After save (step 6) */
    public $lastInvoiceId = null;
    public $lastInvoiceTotal = null;
    public $lastTransactionId = null;
    public $coinsEarned = 0;
    public $deviceId = null;

    public function mount(){
        $this->phoneSearch = '';
        $this->selectedMemberId = null;
        $this->includesProcedures = true;
        $this->includesDiagnostics = true;
        $this->includesPharmacy = true;
        $this->step = 1;
        $this->familyMembers = [];
        $this->deviceId = request()->query('device_id');
    }

    public function updatedIncludesProcedures($value): void
    {
        if (! $this->canSelectProcedures) {
            $this->includesProcedures = false;
        }
    }

    public function updatedIncludesDiagnostics($value): void
    {
        if (! $this->canSelectDiagnostics) {
            $this->includesDiagnostics = false;
        }
    }

    public function updatedIncludesPharmacy($value): void
    {
        if (! $this->canSelectPharmacy) {
            $this->includesPharmacy = false;
        }
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

        $member = Persons::where('mobile', $this->phoneSearch)->first();

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
        $this->selectedMemberId = (string) $id;
    }

    public function personDisplayId($person): string
    {
        if (!$person) {
            return '-';
        }

        $personId = (string) ($person->id ?? '');
        if ($personId === '') {
            return '-';
        }

        $hipCardId = HIPCard::query()
            ->where('patient_id', $personId)
            ->value('hip_card_id');

        return $hipCardId ?: $personId;
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

       $this->closeModal();
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

    public function openAddMemberModal(): void
    {
        $this->reset(['first_name', 'last_name', 'mobile', 'gender', 'dob']);
        $this->resetErrorBag();
        $this->gender = 'male';
        $this->showAddMemberModal = true;
    }

    public function closeModal(): void
    {
        $this->showAddMemberModal = false;
        $this->reset(['first_name', 'last_name', 'mobile', 'gender', 'dob']);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('reset-file-input');
    }

    /**
     * Next step number (2-6), skipping unchecked steps. E.g. only Pharmacy checked -> from 1 go to 4.
     */
    public function getNextStepNumber(): int
    {
        $current = (int) $this->step;
        if ($current >= 6) {
            return 6;
        }
        if ($current === 1) {
            if ($this->includesProcedures) {
                return 2;
            }
            if ($this->includesDiagnostics) {
                return 3;
            }
            if ($this->includesPharmacy) {
                return 4;
            }
            return 5; // review
        }
        if ($current === 2) {
            if ($this->includesDiagnostics) {
                return 3;
            }
            if ($this->includesPharmacy) {
                return 4;
            }
            return 5;
        }
        if ($current === 3) {
            if ($this->includesPharmacy) {
                return 4;
            }
            return 5;
        }
        if ($current === 4) {
            return 5;
        }
        if ($current === 5) {
            return 6;
        }
        return $current;
    }

    /**
     * Previous step number (1-5), skipping unchecked steps.
     */
    public function getPreviousStepNumber(): int
    {
        $current = (int) $this->step;
        if ($current <= 1) {
            return 1;
        }
        if ($current === 2) {
            return 1;
        }
        if ($current === 3) {
            return $this->includesProcedures ? 2 : 1;
        }
        if ($current === 4) {
            if ($this->includesDiagnostics) {
                return 3;
            }
            return $this->includesProcedures ? 2 : 1;
        }
        if ($current === 5) {
            if ($this->includesPharmacy) {
                return 4;
            }
            if ($this->includesDiagnostics) {
                return 3;
            }
            return $this->includesProcedures ? 2 : 1;
        }
        if ($current === 6) {
            return 5;
        }
        return $current;
    }

    public function nextStep()
    {
        if ((int) $this->step === 5) {
            $this->savePayment();
            return;
        }

        $next = $this->getNextStepNumber();
        if ($next > $this->step) {
            $this->step = $next;
        }
    }

    public function previousStep()
    {
        $prev = $this->getPreviousStepNumber();
        if ($prev < $this->step) {
            $this->step = $prev;
        }
    }

    public function cancelPayment()
    {
        $this->step = 1;
        $this->includesProcedures   = true;
        $this->includesDiagnostics  = true;
        $this->includesPharmacy     = true;
    }

    /**
     * Get the selected person (member) model.
     */
    protected function getSelectedPerson(): ?Persons
    {
        if (!$this->selectedMemberId || !$this->member) {
            return null;
        }
        if ((string) $this->member->id === (string) $this->selectedMemberId) {
            return $this->member;
        }
        return collect($this->familyMembers)->firstWhere('id', (string) $this->selectedMemberId);
    }

    /**
     * Primary person id should always be the family primary wallet owner.
     */
    protected function getPrimaryPersonId(): ?string
    {
        if ($this->member && isset($this->member->id)) {
            if (!empty($this->member->parent_id)) {
                return (string) $this->member->parent_id;
            }

            return (string) $this->member->id;
        }

        $person = $this->getSelectedPerson();
        if (! $person) {
            return null;
        }

        if (!empty($person->parent_id)) {
            return (string) $person->parent_id;
        }

        return (string) $person->id;
    }

    /**
     * Selected procedures collection (same as in render).
     */
    protected function getSelectedProceduresCollection()
    {
        $scope = $this->getHospitalScope();
        $hospitalId = $scope['hospital_id'];
        if (!$hospitalId || empty($this->selectedProcedureIds)) {
            return collect([]);
        }
        $ids = array_map('intval', $this->selectedProcedureIds);
        return Procedure::with('speciality')
            ->where('hospital_id', $hospitalId)
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->get();
    }

    /**
     * Selected lab tests + packages collection (same structure as in render).
     */
    protected function getSelectedLabTestsCollection()
    {
        $diagnosticId = $this->getDiagnosticCenterId();
        if (!$diagnosticId) {
            return collect([]);
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
        return $selectedTests->map(fn ($t) => (object) ['type' => 'test', 'model' => $t])
            ->concat($selectedPackages->map(fn ($p) => (object) ['type' => 'package', 'model' => $p]));
    }

    /**
     * Build service_types array: which categories are included (procedure, labTest, package, pharmacy).
     */
    protected function buildServiceTypes(): array
    {
        $types = [];
        if ($this->includesProcedures && !empty($this->selectedProcedureIds)) {
            $types[] = 'procedure';
        }
        if ($this->includesDiagnostics) {
            if (!empty($this->selectedLabTestIds)) {
                $types[] = 'lab_test';
            }
            if (!empty($this->selectedLabPackageIds)) {
                $types[] = 'package';
            }
        }
        if ($this->includesPharmacy && ($this->pharmacyAmount !== '' && $this->pharmacyAmount !== null)) {
            $types[] = 'pharmacy';
        }
        return $types;
    }

    /**
     * Build invoice_details JSON: procedures, labTest, package, pharmacy.
     */
    protected function buildInvoiceDetails(): array
    {
        $details = [];
        $selectedProcedures = $this->getSelectedProceduresCollection();
        $selectedLabTests = $this->getSelectedLabTestsCollection();

        if ($this->includesProcedures && $selectedProcedures->isNotEmpty()) {
            $details['procedures'] = $selectedProcedures->map(function ($p) {
                $cost = (float) ($p->cost ?? 0);
                $discount = isset($p->discount) && $p->discount !== '' ? (float) $p->discount : null;
                $final = $discount !== null ? $discount : $cost;
                return [
                    'name' => $p->procedure_name ?? '',
                    'amount' => (string) $cost,
                    'discount_amount' => (string) $final,
                ];
            })->values()->all();
        }

        if ($this->includesDiagnostics && $selectedLabTests->isNotEmpty()) {
            $labItems = $selectedLabTests->filter(fn ($i) => $i->type === 'test')->map(function ($item) {
                $price = (float) ($item->model->test_price ?? 0);
                $discount = isset($item->model->test_discount) && $item->model->test_discount !== '' ? (float) $item->model->test_discount : null;
                $final = $discount !== null ? $discount : $price;
                return [
                    'name' => $item->model->test_name ?? '',
                    'amount' => (string) $price,
                    'discount_amount' => (string) $final,
                ];
            })->values()->all();
            $pkgItems = $selectedLabTests->filter(fn ($i) => $i->type === 'package')->map(function ($item) {
                $price = (float) ($item->model->price ?? 0);
                $discount = isset($item->model->discount) && $item->model->discount !== '' ? (float) $item->model->discount : null;
                $final = $discount !== null ? $discount : $price;
                return [
                    'name' => $item->model->name ?? '',
                    'amount' => (string) $price,
                    'discount_amount' => (string) $final,
                ];
            })->values()->all();
            if (!empty($labItems)) {
                $details['lab_test'] = $labItems;
            }
            if (!empty($pkgItems)) {
                $details['package'] = $pkgItems;
            }
        }

        if ($this->includesPharmacy && $this->pharmacyAmount !== '' && $this->pharmacyAmount !== null) {
            $details['pharmacy'] = [
                'amount' => (string) (float) $this->pharmacyAmount,
            ];
        }

        return $details;
    }

    /**
     * Charge percentages from config/env.
     */
    protected function getChargePercentages(): array
    {
        return [
            'gst' => (float) config('services.gst_percent', 5),
            'service_charges' => (float) config('services.service_charges_percent', 3),
            'payment_gateway_charges' => (float) config('services.payment_gateway_charges_percent', 2),
        ];
    }

    /**
     * Subtotal (amount before GST and charges) from included categories.
     */
    protected function getSubtotal(float $proceduresTotal, float $labTotal, float $pharmacyTotal): float
    {
        $sub = 0;
        if ($this->includesProcedures) {
            $sub += $proceduresTotal;
        }
        if ($this->includesDiagnostics) {
            $sub += $labTotal;
        }
        if ($this->includesPharmacy) {
            $sub += $pharmacyTotal;
        }
        return round($sub, 2);
    }

    /**
     * Total discount (saved) from procedures + lab.
     */
    protected function getTotalSavedAmount($selectedProcedures, $selectedLabTests): float
    {
        $saved = 0;
        foreach ($selectedProcedures as $p) {
            $cost = (float) ($p->cost ?? 0);
            $discount = isset($p->discount) && $p->discount !== '' ? (float) $p->discount : null;
            if ($discount !== null) {
                $saved += $cost - $discount;
            }
        }
        foreach ($selectedLabTests as $item) {
            if ($item->type === 'test') {
                $price = (float) ($item->model->test_price ?? 0);
                $discount = isset($item->model->test_discount) && $item->model->test_discount !== '' ? (float) $item->model->test_discount : null;
                if ($discount !== null) {
                    $saved += $price - $discount;
                }
            } else {
                $price = (float) ($item->model->price ?? 0);
                $discount = isset($item->model->discount) && $item->model->discount !== '' ? (float) $item->model->discount : null;
                if ($discount !== null) {
                    $saved += $price - $discount;
                }
            }
        }
        return round($saved, 2);
    }

    public function savePayment()
    {
        if ($this->lastInvoiceId) {
            $this->step = 6;
            return;
        }

        $person = $this->getSelectedPerson();
        if (!$person) {
            $this->dispatch('toast', type: 'error', message: 'Please select a member in Step 1.');
            return;
        }

        $primaryPersonId = $this->getPrimaryPersonId();
        $selectedProcedures = $this->getSelectedProceduresCollection();
        $selectedLabTests = $this->getSelectedLabTestsCollection();

        $proceduresTotal = $selectedProcedures->sum(function ($p) {
            $cost = (float) ($p->cost ?? 0);
            $discount = isset($p->discount) && $p->discount !== '' ? (float) $p->discount : null;
            return $discount !== null ? $discount : $cost;
        });
        $labTotal = $selectedLabTests->sum(function ($item) {
            if ($item->type === 'test') {
                $price = (float) ($item->model->test_price ?? 0);
                $discount = isset($item->model->test_discount) && $item->model->test_discount !== '' ? (float) $item->model->test_discount : null;
                return $discount !== null ? $discount : $price;
            }
            $price = (float) ($item->model->price ?? 0);
            $discount = isset($item->model->discount) && $item->model->discount !== '' ? (float) $item->model->discount : null;
            return $discount !== null ? $discount : $price;
        });
        $pharmacyTotal = $this->includesPharmacy && $this->pharmacyAmount !== '' && $this->pharmacyAmount !== null
            ? (float) $this->pharmacyAmount
            : 0;

        $subtotal = $this->getSubtotal($proceduresTotal, $labTotal, $pharmacyTotal);
        $pcts = $this->getChargePercentages();
        $totalGst = round($subtotal * ($pcts['gst'] / 100), 2);
        $serviceCharges = round($subtotal * ($pcts['service_charges'] / 100), 2);
        $paymentGatewayCharges = round($subtotal * ($pcts['payment_gateway_charges'] / 100), 2);
        $totalAmount = round($subtotal + $totalGst + $serviceCharges + $paymentGatewayCharges, 2);
        $discountPrice = $this->getTotalSavedAmount($selectedProcedures, $selectedLabTests);

        $serviceTypes = $this->buildServiceTypes();
        $invoiceDetails = $this->buildInvoiceDetails();
        if (empty($serviceTypes)) {
            $this->dispatch('toast', type: 'error', message: 'Please add at least one billable service.');
            return;
        }

        try {
            /** @var PaymentApiService $service */
            $service = app(PaymentApiService::class);
            $result = $service->createInvoiceForPayment([
                'primary_person_id' => $primaryPersonId,
                'person_id' => $person->id,
                'service_types' => $serviceTypes,
                'invoice_details' => $invoiceDetails,
                'subtotal' => $subtotal,
                'total_gst' => $totalGst,
                'service_charges' => $serviceCharges,
                'payment_gateway_charges' => $paymentGatewayCharges,
                'total_amount' => $totalAmount,
                'discount_price' => $discountPrice,
                'prescription_file' => $this->prescriptionFile,
                'device_id' => $this->deviceId,
            ]);

            /** @var Invoice $invoice */
            $invoice = $result['invoice'];
            $this->lastInvoiceId = $invoice->id;
            $this->lastInvoiceTotal = $totalAmount;
            $this->lastTransactionId = 'TXN-' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT);
            $this->coinsEarned = (int) ($result['coins_earned'] ?? 0);
            $this->step = 6;

            $this->dispatch('toast', type: 'success', message: 'Payment request saved successfully.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Failed to save payment: ' . $e->getMessage());
        }
    }

    /**
     * Go to a step (only back navigation: step must be <= current step).
     */
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

            $availableProcedures = $query
                ->whereNotIn('id', $this->selectedProcedureIds)
                ->orderBy('procedure_name')
                ->paginate(10, ['*'], 'proceduresPage');

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
                $availableLabTests = $testsQuery
                    ->orderBy('test_name')
                    ->paginate(10, ['*'], 'labTestsPage');
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
                $availableLabTests = $packagesQuery
                    ->orderBy('name')
                    ->paginate(10, ['*'], 'labPackagesPage');
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

        // Review: selected member (required)
        $selectedMember = null;
        if ($this->selectedMemberId && $this->member) {
            if ((string) $this->member->id === (string) $this->selectedMemberId) {
                $p = $this->member;
                $selectedMember = [
                    'name' => trim($p->first_name . ' ' . $p->last_name),
                    'member_id' => $this->personDisplayId($p),
                    'phone' => $p->mobile ?? '—',
                    'photo' => $p->image ? asset('storage/users/' . $p->image) : null,
                    'is_primary' => (bool) ($p->is_primary ?? false),
                ];
            } else {
                $p = collect($this->familyMembers)->firstWhere('id', (string) $this->selectedMemberId);
                if ($p) {
                    $selectedMember = [
                        'name' => trim($p->first_name . ' ' . $p->last_name),
                        'member_id' => $this->personDisplayId($p),
                        'phone' => $p->mobile ?? '—',
                        'photo' => $p->image ? asset('storage/users/' . $p->image) : null,
                        'is_primary' => (bool) ($p->is_primary ?? false),
                    ];
                }
            }
        }

        // Review: totals use discount price when set, else regular price
        $proceduresTotal = $selectedProcedures->sum(function ($p) {
            $cost = (float) ($p->cost ?? 0);
            $discount = isset($p->discount) && $p->discount !== '' ? (float) $p->discount : null;
            return $discount !== null ? $discount : $cost;
        });
        $labTotal = $selectedLabTests->sum(function ($item) {
            if ($item->type === 'test') {
                $price = (float) ($item->model->test_price ?? 0);
                $discount = isset($item->model->test_discount) && $item->model->test_discount !== '' ? (float) $item->model->test_discount : null;
                return $discount !== null ? $discount : $price;
            }
            $price = (float) ($item->model->price ?? 0);
            $discount = isset($item->model->discount) && $item->model->discount !== '' ? (float) $item->model->discount : null;
            return $discount !== null ? $discount : $price;
        });

        // Total saved from all discounts (procedures + lab tests + packages)
        $totalSaved = 0;
        foreach ($selectedProcedures as $p) {
            $cost = (float) ($p->cost ?? 0);
            $discount = isset($p->discount) && $p->discount !== '' ? (float) $p->discount : null;
            if ($discount !== null) {
                $totalSaved += $cost - $discount;
            }
        }
        foreach ($selectedLabTests as $item) {
            if ($item->type === 'test') {
                $price = (float) ($item->model->test_price ?? 0);
                $discount = isset($item->model->test_discount) && $item->model->test_discount !== '' ? (float) $item->model->test_discount : null;
                if ($discount !== null) {
                    $totalSaved += $price - $discount;
                }
            } else {
                $price = (float) ($item->model->price ?? 0);
                $discount = isset($item->model->discount) && $item->model->discount !== '' ? (float) $item->model->discount : null;
                if ($discount !== null) {
                    $totalSaved += $price - $discount;
                }
            }
        }

        $prescriptionFileName = $this->prescriptionFile
            ? (is_object($this->prescriptionFile) ? $this->prescriptionFile->getClientOriginalName() : '')
            : null;

        $pharmacyTotal = ($this->includesPharmacy && $this->pharmacyAmount !== '' && $this->pharmacyAmount !== null)
            ? (float) $this->pharmacyAmount
            : 0;
        $subtotal = $this->getSubtotal($proceduresTotal, $labTotal, $pharmacyTotal);
        $pcts = $this->getChargePercentages();
        $totalGst = round($subtotal * ($pcts['gst'] / 100), 2);
        $serviceChargesAmount = round($subtotal * ($pcts['service_charges'] / 100), 2);
        $paymentGatewayChargesAmount = round($subtotal * ($pcts['payment_gateway_charges'] / 100), 2);
        $grandTotal = round($subtotal + $totalGst + $serviceChargesAmount + $paymentGatewayChargesAmount, 2);

        return view('livewire.receptionist-admin.payments.create-payment', [
            'nextStepNumber' => $this->getNextStepNumber(),
            'availableProcedures' => $availableProcedures,
            'selectedProcedures' => $selectedProcedures,
            'availableLabTests' => $availableLabTests,
            'selectedLabTests' => $selectedLabTests,
            'selectedMember' => $selectedMember,
            'proceduresTotal' => $proceduresTotal,
            'labTotal' => $labTotal,
            'totalSaved' => $totalSaved,
            'prescriptionFileName' => $prescriptionFileName,
            'subtotal' => $subtotal,
            'totalGst' => $totalGst,
            'serviceChargesAmount' => $serviceChargesAmount,
            'paymentGatewayChargesAmount' => $paymentGatewayChargesAmount,
            'grandTotal' => $grandTotal,
            'gstPercent' => $pcts['gst'],
            'serviceChargesPercent' => $pcts['service_charges'],
            'paymentGatewayChargesPercent' => $pcts['payment_gateway_charges'],
        ]);
    }
}

