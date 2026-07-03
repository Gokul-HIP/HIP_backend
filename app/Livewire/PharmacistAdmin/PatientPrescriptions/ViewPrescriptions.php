<?php

namespace App\Livewire\PharmacistAdmin\PatientPrescriptions;

use App\Models\Document;
use App\Models\Prescription;
use App\Services\PharmacistScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewPrescriptions extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public ?string $patientId = null;

    public ?string $memberId = null;

    public string $statusFilter = 'all';

    public string $dateRangeFilter = 'all';

    public ?int $selectedPrescriptionId = null;

    public ?array $prescriptionPreview = null;

    public int $perPage = 10;

    public ?int $latestPrescriptionId = null;

    public function mount(?string $patient_id = null, ?string $member_id = null): void
    {
        $this->patientId = filled($patient_id) ? (string) $patient_id : null;
        $this->memberId = filled($member_id) ? (string) $member_id : null;

        $latest = $this->prescriptionsQuery()->first();
        if ($latest) {
            $this->latestPrescriptionId = $latest->id;
            $this->selectPrescription($latest->id);
        }
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->refreshLatestSelection();
    }

    public function updatingDateRangeFilter(): void
    {
        $this->resetPage();
        $this->refreshLatestSelection();
    }

    public function resetFilters(): void
    {
        $this->statusFilter = 'all';
        $this->dateRangeFilter = 'all';
        $this->resetPage();
        $this->refreshLatestSelection();
    }

    protected function refreshLatestSelection(): void
    {
        $latest = $this->prescriptionsQuery()->first();

        if ($latest) {
            $this->latestPrescriptionId = $latest->id;
            $this->selectPrescription($latest->id);
        } else {
            $this->latestPrescriptionId = null;
            $this->closePreview();
        }
    }

    protected function scopeService(): PharmacistScopeService
    {
        return app(PharmacistScopeService::class);
    }

    protected function prescriptionsQuery(): Builder
    {
        $hospitalIds = $this->scopeService()->hospitalIds('all');

        if ($hospitalIds === []) {
            return Prescription::query()->whereRaw('0 = 1');
        }

        return Prescription::query()
            ->with(['patient', 'member', 'doctor', 'hospital'])
            ->whereIn('hospital_id', $hospitalIds)
            ->when(filled($this->patientId), fn ($q) => $q->where('patient_id', $this->patientId))
            ->when(! filled($this->patientId) && filled($this->memberId), fn ($q) => $q->where('member_id', $this->memberId))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateRangeFilter !== 'all', function ($q) {
                $fromDate = match ($this->dateRangeFilter) {
                    '30_days' => now()->subDays(30)->startOfDay(),
                    '6_months' => now()->subMonths(6)->startOfDay(),
                    '1_year' => now()->subYear()->startOfDay(),
                    default => null,
                };

                if ($fromDate) {
                    $q->where('created_at', '>=', $fromDate);
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    protected function findPrescription(int $id): ?Prescription
    {
        return $this->prescriptionsQuery()->find($id);
    }

    public function selectPrescription(int $prescriptionId): void
    {
        $prescription = $this->findPrescription($prescriptionId);

        if (! $prescription) {
            return;
        }

        $this->selectedPrescriptionId = $prescription->id;
        $this->prescriptionPreview = $this->mapPrescriptionPreview($prescription);
    }

    public function closePreview(): void
    {
        $this->selectedPrescriptionId = null;
        $this->prescriptionPreview = null;
    }

    public function downloadDocument(int $documentId): ?StreamedResponse
    {
        $document = $this->findDocument($documentId);

        if (! $document || ! filled($document->document_path) || ! Storage::disk('public')->exists($document->document_path)) {
            $this->dispatch('toast', type: 'error', message: 'Document file is unavailable.');

            return null;
        }

        return Storage::disk('public')->download(
            $document->document_path,
            $document->document_name ?: basename($document->document_path)
        );
    }

    protected function findDocument(int $documentId): ?Document
    {
        $prescriptionIds = $this->prescriptionsQuery()->pluck('id');

        if ($prescriptionIds->isEmpty()) {
            return null;
        }

        $documentIds = Prescription::query()
            ->whereIn('id', $prescriptionIds)
            ->pluck('document_ids')
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (! $documentIds->contains($documentId)) {
            return null;
        }

        return Document::query()->find($documentId);
    }

    protected function mapPrescriptionPreview(Prescription $prescription): array
    {
        $patient = $prescription->patient;
        $member = $prescription->member;
        $patientName = $patient
            ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))
            : ($member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '—');

        $documents = $prescription->documents()->map(function (Document $document) {
            $extension = strtolower(pathinfo((string) $document->document_path, PATHINFO_EXTENSION));

            return [
                'id' => $document->id,
                'name' => $document->document_name,
                'type' => ucwords(str_replace(['_', '-'], ' ', (string) ($document->document_type ?: 'prescription'))),
                'url' => $document->document_url,
                'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                'is_pdf' => $extension === 'pdf',
            ];
        })->values()->all();

        $medications = collect($prescription->medications ?? [])->map(function ($med) {
            if (! is_array($med)) {
                return null;
            }

            return [
                'name' => $med['name'] ?? $med['medicine_name'] ?? '—',
                'dosage' => $med['dosage'] ?? '—',
                'frequency' => $med['frequency'] ?? '—',
                'duration' => $med['duration'] ?? '—',
                'when_to_take' => $med['when_to_take'] ?? '—',
                'quantity' => $med['quantity'] ?? '—',
                'special_instruction' => $med['special_instruction'] ?? $med['special_instructions'] ?? '—',
            ];
        })->filter()->values()->all();

        $labTests = collect($prescription->lab_tests ?? [])->map(function ($test) {
            if (is_array($test)) {
                return $test['name'] ?? $test['test_name'] ?? json_encode($test);
            }

            return (string) $test;
        })->filter()->values()->all();

        return [
            'id' => $prescription->id,
            'prescription_id' => 'RX-' . str_pad((string) $prescription->id, 5, '0', STR_PAD_LEFT),
            'patient_name' => $patientName !== '' ? $patientName : '—',
            'member_hip_id' => $member?->hip_id ?: '—',
            'doctor_name' => $prescription->doctor?->name ? 'Dr ' . $prescription->doctor->name : '—',
            'hospital_name' => $prescription->hospital?->name ?: '—',
            'date' => $prescription->created_at?->format('d M Y, h:i A') ?: '—',
            'status' => ucfirst((string) ($prescription->status ?? 'draft')),
            'diagnosis' => $prescription->diagnosis ?: '—',
            'clinical_notes' => $prescription->clinical_notes ?: '—',
            'follow_up_date' => $prescription->follow_up_date?->format('d M Y') ?: '—',
            'medications' => $medications,
            'lab_tests' => $labTests,
            'documents' => $documents,
            'is_latest' => $this->latestPrescriptionId === $prescription->id,
        ];
    }

    protected function mapPrescriptionRow(Prescription $prescription): array
    {
        $preview = $this->mapPrescriptionPreview($prescription);

        return [
            'id' => $prescription->id,
            'prescription_id' => $preview['prescription_id'],
            'name' => $preview['prescription_id'] . ' — ' . $preview['doctor_name'],
            'type' => 'Prescription',
            'uploaded_by' => $preview['doctor_name'],
            'date' => $prescription->created_at?->format('d M Y') ?: '—',
            'status' => $preview['status'],
            'medications_count' => count($preview['medications']),
            'documents_count' => count($preview['documents']),
            'is_latest' => $preview['is_latest'],
            'medications' => $preview['medications'],
        ];
    }

    protected function buildPatientSummary(): array
    {
        $latest = $this->prescriptionsQuery()->first();

        if (! $latest) {
            return [
                'name' => '—',
                'uhid' => '—',
                'age' => '—',
                'gender' => '—',
                'last_visit' => '—',
                'status' => 'Active Patient',
                'initials' => 'P',
            ];
        }

        $patient = $latest->patient;
        $member = $latest->member;
        $name = $patient
            ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))
            : ($member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '—');

        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return [
            'name' => $name !== '' ? $name : '—',
            'uhid' => $member?->hip_id ?: '—',
            'age' => $patient?->age ?? ($member?->age ?? '—'),
            'gender' => $patient?->gender ?? ($member?->gender ?? '—'),
            'last_visit' => $latest->created_at?->format('d M Y') ?: '—',
            'status' => 'Active Patient',
            'initials' => collect($parts)->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('') ?: 'P',
        ];
    }

    public function render()
    {
        if (! filled($this->patientId) && ! filled($this->memberId)) {
            abort(404);
        }

        $prescriptionsPaginator = $this->prescriptionsQuery()->paginate($this->perPage);

        if ($this->latestPrescriptionId === null && $prescriptionsPaginator->isNotEmpty()) {
            $this->latestPrescriptionId = $prescriptionsPaginator->first()->id;
        }

        $prescriptionRows = $prescriptionsPaginator->through(fn (Prescription $rx) => $this->mapPrescriptionRow($rx));

        return view('livewire.pharmacist-admin.patient-prescriptions.view-prescriptions', [
            'patient' => $this->buildPatientSummary(),
            'prescriptions' => $prescriptionRows,
            'totalPrescriptions' => $prescriptionsPaginator->total(),
        ]);
    }
}
