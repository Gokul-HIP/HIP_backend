<?php

namespace App\Livewire\DoctorAdmin\Prescription;

use App\Models\DiagnosticLabTest;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\MedicineMaster;
use App\Models\Prescription;
use App\Support\CurrentDoctor;
use App\Support\PatientRecordScope;
use App\Services\PrescriptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreatePrescription extends Component
{
    use WithFileUploads;

    public ?int $bookingId = null;

    public bool $showAddMedicineModal = false;

    public bool $showLabTestPicker = false;

    public bool $showUploadDocumentModal = false;

    public string $medicineSearch = '';

    public array $medicineSuggestions = [];

    public ?int $selectedMedicineId = null;

    public bool $showMedicineDropdown = false;

    public string $medicineDosage = '500 mg';

    public string $medicineFrequency = 'Twice Daily';

    public string $medicineDuration = '5 Days';

    public string $medicineQuantity = '';

    public array $whenToTake = ['After Food'];

    public string $medicineInstructions = '';

    public string $labTestSearch = '';

    public string $clinicalNotes = '';

    public string $followUpDate = '';

    public $documentFile = null;

    public string $documentType = '';

    public string $documentTitle = '';

    public string $documentNotes = '';

    public bool $makeAvailableInApp = true;

    public ?string $uploadedFileName = null;

    public ?string $uploadedFileSize = null;

    /** @var array<int, array<string, mixed>> */
    public array $medications = [];

    /** @var array<int, array<string, mixed>> */
    public array $labTests = [];

    /** @var array<int, array<string, mixed>> */
    public array $uploadedDocuments = [];

    /** @var array<int, array<string, mixed>> */
    public array $labTestGroups = [];

    public bool $showPatientHistoryPanel = false;

    public string $selectedPrescriptionHistoryId = '';

    public string $selectedAppointmentHistoryId = '';

    /** @var array<int, array<string, mixed>> */
    public array $prescriptionHistoryOptions = [];

    /** @var array<int, array<string, mixed>> */
    public array $appointmentHistoryOptions = [];

    public function mount(?int $patient_id = null): void
    {
        $this->bookingId = $patient_id ? (int) $patient_id : null;
    }

    public function addMedicineRow(): void
    {
        $this->resetMedicineForm();
        $this->showAddMedicineModal = true;
    }

    public function closeAddMedicine(): void
    {
        $this->showAddMedicineModal = false;
        $this->closeMedicineDropdown();
    }

    public function openMedicineDropdown(): void
    {
        $this->showMedicineDropdown = true;
        $this->medicineSuggestions = $this->searchMedicines($this->medicineSearch);
    }

    public function closeMedicineDropdown(): void
    {
        $this->showMedicineDropdown = false;
    }

    public function openLabTestPicker(): void
    {
        $doctor = $this->doctor();

        if (! $doctor) {
            return;
        }

        $this->labTestSearch = '';
        $this->labTestGroups = $this->buildLabTestGroups($doctor);
        $this->showLabTestPicker = true;
    }

    public function closeLabTestPicker(): void
    {
        $this->showLabTestPicker = false;
        $this->labTestSearch = '';
    }

    public function openUploadDocument(): void
    {
        $this->resetDocumentForm();
        $this->showUploadDocumentModal = true;
    }

    public function closeUploadDocument(): void
    {
        $this->showUploadDocumentModal = false;
        $this->resetDocumentForm();
    }

    public function openPatientHistory(): void
    {
        $doctor = $this->doctor();
        $booking = $this->findBooking();

        if (! $doctor || ! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Patient history is unavailable for this booking.');

            return;
        }

        $this->loadPatientHistory($booking, $doctor);
        $this->showPatientHistoryPanel = true;
    }

    public function closePatientHistory(): void
    {
        $this->showPatientHistoryPanel = false;
    }

    public function quickPrint(): void
    {
        if ($this->medications === []) {
            $this->dispatch('toast', type: 'warning', message: 'Please add at least one medication to quick print.');

            return;
        }

        $this->dispatch('quick-print');
    }

    public function updatedDocumentFile(): void
    {
        if (! $this->documentFile) {
            $this->uploadedFileName = null;
            $this->uploadedFileSize = null;

            return;
        }

        $this->uploadedFileName = $this->documentFile->getClientOriginalName();
        $this->uploadedFileSize = $this->formatFileSize((int) $this->documentFile->getSize());

        if (trim($this->documentTitle) === '') {
            $this->documentTitle = pathinfo($this->uploadedFileName, PATHINFO_FILENAME);
        }
    }

    public function handleDocumentDrop($file): void
    {
        $this->documentFile = $file;
        $this->updatedDocumentFile();
    }

    public function removeDocumentFile(): void
    {
        $this->documentFile = null;
        $this->uploadedFileName = null;
        $this->uploadedFileSize = null;
    }

    public function uploadDocument(): void
    {
        $this->validate([
            'documentFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'documentType' => 'required|string|in:lab_report,imaging,prescription,discharge_summary,consultation_notes,insurance,other',
            'documentTitle' => 'required|string|max:255',
            'documentNotes' => 'nullable|string|max:2000',
            'makeAvailableInApp' => 'boolean',
        ], [
            'documentFile.required' => 'Please select a document to upload.',
            'documentType.required' => 'Please select a document type.',
            'documentTitle.required' => 'Please enter a document title.',
        ]);

        $storedPath = $this->documentFile->store('patient-documents/'.now()->format('Y/m'), 'public');

        $this->uploadedDocuments[] = [
            'name' => $this->documentTitle,
            'type' => $this->documentType,
            'notes' => $this->documentNotes,
            'available_in_app' => $this->makeAvailableInApp,
            'file_name' => $this->uploadedFileName,
            'file_size' => $this->uploadedFileSize,
            'file_size_bytes' => (string) $this->documentFile->getSize(),
            'stored_path' => $storedPath,
        ];

        $this->documentFile = null;

        $this->closeUploadDocument();
        $this->dispatch('toast', type: 'success', message: 'Document added to prescription.');
    }

    public function updatedMedicineSearch(): void
    {
        $this->selectedMedicineId = null;

        if ($this->showMedicineDropdown) {
            $this->medicineSuggestions = $this->searchMedicines($this->medicineSearch);
        }
    }

    protected function searchMedicines(string $search): array
    {
        $query = MedicineMaster::query()
            ->where('status', 'active')
            ->orderBy('name');

        $search = trim($search);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand_name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('strength', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('dosage_form', 'like', '%'.$search.'%');
            });
        }

        return $query
            ->limit(100)
            ->get(['id', 'name', 'brand_name', 'strength', 'code', 'dosage_form'])
            ->map(fn (MedicineMaster $medicine) => [
                'id' => $medicine->id,
                'name' => $medicine->name,
                'brand_name' => $medicine->brand_name,
                'strength' => $medicine->strength,
                'code' => $medicine->code,
                'dosage_form' => $medicine->dosage_form,
                'label' => $this->medicineLabel($medicine),
            ])
            ->values()
            ->all();
    }

    protected function medicineLabel(MedicineMaster $medicine): string
    {
        $parts = array_filter([
            $medicine->name,
            $medicine->brand_name ? '('.$medicine->brand_name.')' : null,
            $medicine->strength,
        ]);

        return implode(' ', $parts);
    }

    public function selectMedicine(int $index): void
    {
        if (! isset($this->medicineSuggestions[$index])) {
            return;
        }

        $medicine = $this->medicineSuggestions[$index];

        $this->selectedMedicineId = (int) $medicine['id'];
        $this->medicineSearch = (string) $medicine['name'];

        if (filled($medicine['strength'])) {
            $this->medicineDosage = (string) $medicine['strength'];
        }

        $this->closeMedicineDropdown();
    }

    public function toggleWhenToTake(string $option): void
    {
        if (in_array($option, $this->whenToTake, true)) {
            $this->whenToTake = array_values(array_filter(
                $this->whenToTake,
                fn (string $item) => $item !== $option
            ));
        } else {
            $this->whenToTake[] = $option;
        }
    }

    public function appendInstruction(string $chip): void
    {
        $current = trim($this->medicineInstructions);

        if ($current === '') {
            $this->medicineInstructions = $chip;

            return;
        }

        if (! str_contains($current, $chip)) {
            $this->medicineInstructions = $current.', '.$chip;
        }
    }

    public function saveMedicine(): void
    {
        $name = trim($this->medicineSearch);

        if ($name === '') {
            $this->dispatch('toast', type: 'error', message: 'Please enter a medicine name.');

            return;
        }

        $whenToTake = $this->whenToTake !== [] ? implode(', ', $this->whenToTake) : null;
        $specialInstruction = trim($this->medicineInstructions);

        $this->medications[] = [
            'medicine_id' => $this->selectedMedicineId,
            'name' => $name,
            'dosage' => $this->medicineDosage ?: null,
            'frequency' => $this->medicineFrequency ?: null,
            'duration' => $this->medicineDuration ?: null,
            'when_to_take' => $whenToTake,
            'quantity' => filled($this->medicineQuantity) ? (string) $this->medicineQuantity : null,
            'special_instruction' => $specialInstruction !== '' ? $specialInstruction : null,
        ];

        $this->closeAddMedicine();
        $this->resetMedicineForm();
        $this->dispatch('toast', type: 'success', message: 'Medicine added to prescription.');
    }

    public function removeMedicine(int $index): void
    {
        if (! isset($this->medications[$index])) {
            return;
        }

        unset($this->medications[$index]);
        $this->medications = array_values($this->medications);
    }

    public function toggleLabTest(int $hospitalId, int $testId): void
    {
        $existingIndex = collect($this->labTests)->search(
            fn (array $test) => (int) $test['hospital_id'] === $hospitalId && (int) $test['test_id'] === $testId
        );

        if ($existingIndex !== false) {
            unset($this->labTests[$existingIndex]);
            $this->labTests = array_values($this->labTests);

            return;
        }

        $test = $this->findLabTestInGroups($hospitalId, $testId);

        if (! $test) {
            return;
        }

        $this->labTests[] = [
            'hospital_id' => $hospitalId,
            'hospital_name' => $test['hospital_name'],
            'test_id' => $testId,
            'test_name' => $test['test_name'],
            'label' => $test['test_name'],
        ];
    }

    public function isLabTestSelected(int $hospitalId, int $testId): bool
    {
        return collect($this->labTests)->contains(
            fn (array $test) => (int) $test['hospital_id'] === $hospitalId && (int) $test['test_id'] === $testId
        );
    }

    public function removeLabTest(int $index): void
    {
        if (! isset($this->labTests[$index])) {
            return;
        }

        unset($this->labTests[$index]);
        $this->labTests = array_values($this->labTests);
    }

    public function removeDocument(int $index): void
    {
        if (! isset($this->uploadedDocuments[$index])) {
            return;
        }

        $doc = $this->uploadedDocuments[$index];
        $storedPath = (string) ($doc['stored_path'] ?? '');

        if ($storedPath !== '' && empty($doc['document_id']) && Storage::disk('public')->exists($storedPath)) {
            Storage::disk('public')->delete($storedPath);
        }

        unset($this->uploadedDocuments[$index]);
        $this->uploadedDocuments = array_values($this->uploadedDocuments);
    }

    public function saveDraft(PrescriptionService $prescriptionService): void
    {
        $this->persistPrescription($prescriptionService, Prescription::STATUS_DRAFT, false);
    }

    public function sendPrescription(PrescriptionService $prescriptionService): void
    {
        $this->persistPrescription($prescriptionService, Prescription::STATUS_SENT, false);
    }

    public function completeConsultation(PrescriptionService $prescriptionService): void
    {
        $this->persistPrescription($prescriptionService, Prescription::STATUS_COMPLETED, true);
    }

    protected function persistPrescription(
        PrescriptionService $prescriptionService,
        string $status,
        bool $completeCurrentBooking
    ): void {
        $doctor = $this->doctor();
        $booking = $this->findBooking();

        if (! $doctor || ! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Unable to save prescription. Booking not found.');

            return;
        }

        if (filled($this->followUpDate) && Carbon::parse($this->followUpDate)->lt(now()->startOfDay())) {
            $this->dispatch('toast', type: 'error', message: 'Follow-up date must be today or a future date.');

            return;
        }

        try {
            $prescriptionService->store(
                $booking,
                $this->medications,
                $this->labTests,
                $this->uploadedDocuments,
                $this->clinicalNotes,
                $this->followUpDate ?: null,
                $status,
                $completeCurrentBooking
            );
        } catch (\InvalidArgumentException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: 'Failed to save prescription. Please try again.');

            return;
        }

        $message = match ($status) {
            Prescription::STATUS_SENT => 'Prescription sent to patient successfully.',
            Prescription::STATUS_COMPLETED => 'Consultation completed and prescription saved.',
            default => 'Prescription saved as draft.',
        };

        $this->dispatch('toast', type: 'success', message: $message);

        $this->redirectRoute('doctor.upload-prescription.index', navigate: true);
    }

    protected function resetMedicineForm(): void
    {
        $this->medicineSearch = '';
        $this->medicineSuggestions = [];
        $this->selectedMedicineId = null;
        $this->showMedicineDropdown = false;
        $this->medicineDosage = '500 mg';
        $this->medicineFrequency = 'Twice Daily';
        $this->medicineDuration = '5 Days';
        $this->medicineQuantity = '';
        $this->whenToTake = ['After Food'];
        $this->medicineInstructions = '';
    }

    protected function resetDocumentForm(): void
    {
        $this->documentFile = null;
        $this->documentType = '';
        $this->documentTitle = '';
        $this->documentNotes = '';
        $this->makeAvailableInApp = true;
        $this->uploadedFileName = null;
        $this->uploadedFileSize = null;
        $this->resetValidation();
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    protected function doctor(): ?Doctor
    {
        return CurrentDoctor::resolve();
    }

    protected function hospitalIds(Doctor $doctor): array
    {
        return collect($doctor->hospital_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    protected function findBooking(): ?DoctorBooking
    {
        $doctor = $this->doctor();

        if (! $doctor || ! $this->bookingId) {
            return null;
        }

        $hospitalIds = $this->hospitalIds($doctor);

        return DoctorBooking::query()
            ->with(['patient', 'member', 'hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->when($hospitalIds !== [], function ($query) use ($hospitalIds) {
                $query->where(function ($scoped) use ($hospitalIds) {
                    $scoped->whereIn('hospital_id', $hospitalIds)
                        ->orWhereIn('branch_id', $hospitalIds);
                });
            })
            ->find($this->bookingId);
    }

    protected function patientContext(?DoctorBooking $booking, ?Doctor $doctor = null): array
    {
        if (! $booking) {
            return [
                'patientName' => 'Patient',
                'patientAge' => null,
                'patientGender' => null,
                'patientGenderFull' => null,
                'patientInitials' => 'P',
                'patientUhid' => '—',
                'patientMobile' => null,
                'patientEmail' => null,
                'patientAddress' => null,
                'patientBloodGroup' => null,
                'doctorName' => $doctor?->name ? 'Dr. '.$doctor->name : '—',
                'appointmentDate' => '—',
                'appointmentTime' => '—',
                'appointmentId' => $this->bookingId ? (string) $this->bookingId : '—',
                'room' => null,
                'vitals' => null,
                'diagnosis' => null,
            ];
        }

        $patient = $booking->patient;
        $member = $booking->member;

        $name = trim(collect([
            $patient?->first_name,
            $patient?->last_name,
        ])->filter()->join(' '));

        if ($name === '') {
            $name = trim((string) ($booking->name ?: $member?->name ?: 'Patient'));
        }

        $dob = $patient?->dob ?: $member?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $gender = $patient?->gender ?: $member?->gender;
        $genderLabel = $gender ? ucfirst(substr((string) $gender, 0, 1)) : null;

        $branchName = $booking->branch?->name ?: $booking->hospital?->name;
        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $appointmentTime = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        return [
            'patientName' => $name,
            'patientAge' => $age,
            'patientGender' => $genderLabel,
            'patientGenderFull' => $gender ? ucfirst((string) $gender) : null,
            'patientInitials' => strtoupper(substr(preg_replace('/\s+/', ' ', trim($name)), 0, 2)) ?: 'P',
            'patientUhid' => $member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—'),
            'patientMobile' => $patient?->mobile ?: $member?->mobile_num ?: $booking->mobile_number,
            'patientEmail' => $patient?->email ?: $member?->email,
            'patientAddress' => $this->formatPatientAddress($member, $patient),
            'patientBloodGroup' => null,
            'doctorName' => $doctor?->name ? 'Dr. '.$doctor->name : '—',
            'appointmentDate' => $booking->booking_date?->format('d M Y') ?? '—',
            'appointmentTime' => $appointmentTime,
            'appointmentId' => (string) $booking->id,
            'room' => $branchName,
            'vitals' => null,
            'diagnosis' => null,
        ];
    }

    protected function formatPatientAddress($member, $patient): ?string
    {
        $parts = array_filter([
            $member?->house_number,
            $member?->street,
            $member?->city,
            $member?->state,
            $member?->zip_code,
        ]);

        return $parts !== [] ? implode(', ', $parts) : null;
    }

    protected function scopePatientHistory($query, DoctorBooking $booking)
    {
        return PatientRecordScope::apply(
            $query,
            $booking->patient_id,
            $booking->member_id
        );
    }

    protected function loadPatientHistory(DoctorBooking $booking, Doctor $doctor): void
    {
        $this->prescriptionHistoryOptions = Prescription::query()
            ->where('doctor_id', $doctor->id)
            ->tap(fn ($query) => $this->scopePatientHistory($query, $booking))
            ->when($this->bookingId, fn ($query) => $query->where('doctor_booking_id', '!=', $this->bookingId))
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Prescription $prescription) {
                return [
                    'id' => $prescription->id,
                    'label' => ($prescription->created_at?->format('d M Y') ?? '—').' • '.ucfirst($prescription->status),
                    'date' => $prescription->created_at?->format('d M Y') ?? '—',
                    'status' => ucfirst($prescription->status),
                    'medications' => $prescription->medications ?? [],
                    'clinical_notes' => $prescription->clinical_notes,
                ];
            })
            ->values()
            ->all();

        $this->appointmentHistoryOptions = DoctorBooking::query()
            ->with(['hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->tap(fn ($query) => $this->scopePatientHistory($query, $booking))
            ->when($this->bookingId, fn ($query) => $query->where('id', '!=', $this->bookingId))
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (DoctorBooking $appointment) {
                $slots = collect($appointment->required_time_slots ?? [])->filter()->values();
                $time = match ($slots->count()) {
                    0 => '—',
                    1 => (string) $slots->first(),
                    default => $slots->first().' - '.$slots->last(),
                };

                return [
                    'id' => $appointment->id,
                    'label' => ($appointment->booking_date?->format('d M Y') ?? '—').' • '.ucfirst((string) $appointment->status),
                    'date' => $appointment->booking_date?->format('d M Y') ?? '—',
                    'time' => $time,
                    'status' => ucfirst((string) $appointment->status),
                    'visit_type' => $appointment->is_follow_up
                        ? 'Follow-up'
                        : ($appointment->isOnlineConsultation() ? 'Online' : 'New'),
                    'branch' => $appointment->branch?->name ?: $appointment->hospital?->name ?: '—',
                    'reason' => $appointment->reason_of_visit ?: $appointment->purpose,
                ];
            })
            ->values()
            ->all();

        $this->selectedPrescriptionHistoryId = (string) ($this->prescriptionHistoryOptions[0]['id'] ?? '');
        $this->selectedAppointmentHistoryId = (string) ($this->appointmentHistoryOptions[0]['id'] ?? '');
    }

    protected function selectedPrescriptionHistory(): ?array
    {
        if ($this->selectedPrescriptionHistoryId === '') {
            return null;
        }

        return collect($this->prescriptionHistoryOptions)
            ->firstWhere('id', (int) $this->selectedPrescriptionHistoryId);
    }

    protected function selectedAppointmentHistory(): ?array
    {
        if ($this->selectedAppointmentHistoryId === '') {
            return null;
        }

        return collect($this->appointmentHistoryOptions)
            ->firstWhere('id', (int) $this->selectedAppointmentHistoryId);
    }

    protected function buildLabTestGroups(Doctor $doctor): array
    {
        $hospitalIds = $this->hospitalIds($doctor);

        if ($hospitalIds === []) {
            return [];
        }

        $hospitals = Hospital::query()
            ->whereIn('id', $hospitalIds)
            ->whereNotNull('diagnostic_center_id')
            ->orderBy('name')
            ->get(['id', 'name', 'diagnostic_center_id']);

        $groups = [];

        foreach ($hospitals as $hospital) {
            $tests = DiagnosticLabTest::query()
                ->where('diagnostic_id', $hospital->diagnostic_center_id)
                ->where('test_status', 'active')
                ->orderBy('test_name')
                ->get(['id', 'test_name', 'test_code', 'test_price']);

            if ($tests->isEmpty()) {
                continue;
            }

            $groups[] = [
                'hospital_id' => $hospital->id,
                'hospital_name' => $hospital->name,
                'tests' => $tests->map(fn (DiagnosticLabTest $test) => [
                    'id' => $test->id,
                    'test_name' => $test->test_name,
                    'test_code' => $test->test_code,
                    'test_price' => $test->test_price,
                ])->all(),
            ];
        }

        return $groups;
    }

    protected function findLabTestInGroups(int $hospitalId, int $testId): ?array
    {
        foreach ($this->labTestGroups as $group) {
            if ((int) $group['hospital_id'] !== $hospitalId) {
                continue;
            }

            foreach ($group['tests'] as $test) {
                if ((int) $test['id'] === $testId) {
                    return [
                        'test_name' => $test['test_name'],
                        'hospital_name' => $group['hospital_name'],
                    ];
                }
            }
        }

        foreach ($this->filteredLabTestGroups() as $group) {
            if ((int) $group['hospital_id'] !== $hospitalId) {
                continue;
            }

            foreach ($group['tests'] as $test) {
                if ((int) $test['id'] === $testId) {
                    return [
                        'test_name' => $test['test_name'],
                        'hospital_name' => $group['hospital_name'],
                    ];
                }
            }
        }

        return null;
    }

    protected function filteredLabTestGroups(): array
    {
        $search = mb_strtolower(trim($this->labTestSearch));

        if ($search === '') {
            return $this->labTestGroups;
        }

        return collect($this->labTestGroups)
            ->map(function (array $group) use ($search) {
                $tests = collect($group['tests'])->filter(function (array $test) use ($search) {
                    return str_contains(mb_strtolower($test['test_name']), $search)
                        || str_contains(mb_strtolower((string) ($test['test_code'] ?? '')), $search);
                })->values()->all();

                if ($tests === []) {
                    return null;
                }

                return [
                    'hospital_id' => $group['hospital_id'],
                    'hospital_name' => $group['hospital_name'],
                    'tests' => $tests,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function render()
    {
        $booking = $this->findBooking();
        $doctor = $this->doctor();
        $patient = $this->patientContext($booking, $doctor);
        $showHospitalOnTags = $doctor ? count($this->hospitalIds($doctor)) > 1 : false;

        $labTestGroups = $this->showLabTestPicker
            ? $this->filteredLabTestGroups()
            : [];

        return view('livewire.doctor-admin.prescription.create-prescription', [
            ...$patient,
            'showHospitalOnTags' => $showHospitalOnTags,
            'filteredLabTestGroups' => $labTestGroups,
            'selectedPrescriptionHistory' => $this->selectedPrescriptionHistory(),
            'selectedAppointmentHistory' => $this->selectedAppointmentHistory(),
        ]);
    }
}
