<?php

namespace App\Livewire\DoctorAdmin\PatientDocument;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\Document;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use App\Models\Prescription;
use App\Support\CurrentDoctor;
use App\Support\PatientRecordScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewDocument extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public ?int $bookingId = null;

    public string $documentTypeFilter = 'all';

    public string $dateRangeFilter = '6_months';

    public string $branchFilter = 'all';

    public ?int $selectedDocumentId = null;

    public ?array $previewDoc = null;

    public bool $showPatientProfilePanel = false;

    public ?array $profilePatient = null;

    public int $perPage = 10;

    public function mount(?string $patient_id = null): void
    {
        $this->bookingId = filled($patient_id) ? (int) $patient_id : null;
    }

    public function updatingDocumentTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateRangeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->documentTypeFilter = 'all';
        $this->dateRangeFilter = '6_months';
        $this->branchFilter = 'all';
        $this->resetPage();
    }

    public function previewDocument(int $documentId): void
    {
        $document = $this->findDocument($documentId);

        if (! $document) {
            return;
        }

        $this->selectedDocumentId = $document->id;
        $this->previewDoc = $this->mapDocumentPreview($document);
    }

    public function closePreview(): void
    {
        $this->selectedDocumentId = null;
        $this->previewDoc = null;
    }

    public function downloadDocument(int $documentId): ?StreamedResponse
    {
        $document = $this->findDocument($documentId);

        if (! $document || ! filled($document->document_path)) {
            $this->dispatch('toast', type: 'error', message: 'Document file is unavailable.');

            return null;
        }

        if (! Storage::disk('public')->exists($document->document_path)) {
            $this->dispatch('toast', type: 'error', message: 'Document file was not found on the server.');

            return null;
        }

        $fileName = $document->document_name ?: basename($document->document_path);

        return Storage::disk('public')->download($document->document_path, $fileName);
    }

    public function viewPatientProfile(): void
    {
        $booking = $this->findBooking();

        if (! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Patient profile is unavailable.');

            return;
        }

        $this->profilePatient = $this->buildPatientProfile($booking);
        $this->showPatientProfilePanel = true;
    }

    public function closePatientProfile(): void
    {
        $this->showPatientProfilePanel = false;
        $this->profilePatient = null;
    }

    public function bookFollowUp(): void
    {
        $this->dispatch('toast', type: 'info', message: 'Follow-up booking is coming soon.');
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

    protected function branches(Doctor $doctor): Collection
    {
        $hospitalIds = $this->hospitalIds($doctor);

        if ($hospitalIds === []) {
            return collect();
        }

        return Hospital::query()
            ->whereIn('id', $hospitalIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function findBooking(): ?DoctorBooking
    {
        $doctor = $this->doctor();

        if (! $doctor || ! $this->bookingId) {
            return null;
        }

        $hospitalIds = $this->hospitalIds($doctor);

        return DoctorBooking::query()
            ->with(['patient.hipUser', 'member', 'hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->when($hospitalIds !== [], function ($query) use ($hospitalIds) {
                $query->where(function ($scoped) use ($hospitalIds) {
                    $scoped->whereIn('hospital_id', $hospitalIds)
                        ->orWhereIn('branch_id', $hospitalIds);
                });
            })
            ->find($this->bookingId);
    }

    protected function findDocument(int $documentId): ?Document
    {
        $booking = $this->findBooking();

        if (! $booking) {
            return null;
        }

        return $this->documentsQuery($booking)->find($documentId);
    }

    protected function documentsQuery(DoctorBooking $booking): Builder
    {
        $query = Document::query()
            ->with(['patient', 'member'])
            ->where(fn (Builder $scoped) => PatientRecordScope::apply(
                $scoped,
                $booking->patient_id,
                $booking->member_id
            ));

        if ($this->documentTypeFilter !== 'all') {
            $query = $this->applyDocumentTypeFilter($query, $this->documentTypeFilter);
        }

        if ($this->dateRangeFilter !== 'all') {
            $fromDate = match ($this->dateRangeFilter) {
                '30_days' => now()->subDays(30)->startOfDay(),
                '1_year' => now()->subYear()->startOfDay(),
                default => now()->subMonths(6)->startOfDay(),
            };

            $query->where('created_at', '>=', $fromDate);
        }

        if ($this->branchFilter !== 'all') {
            $branchId = (int) $this->branchFilter;
            $documentIds = Prescription::query()
                ->where('hospital_id', $branchId)
                ->where(fn (Builder $scoped) => PatientRecordScope::apply(
                    $scoped,
                    $booking->patient_id,
                    $booking->member_id
                ))
                ->pluck('document_ids')
                ->flatten()
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($documentIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $documentIds);
            }
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    protected function applyDocumentTypeFilter(Builder $query, string $filter): Builder
    {
        $aliases = match ($filter) {
            'lab_report' => ['lab_report', 'lab report', 'labreport'],
            'scan_report' => ['scan_report', 'scan report', 'scanreport', 'imaging', 'radiology'],
            'prescription' => ['prescription'],
            'discharge_summary' => ['discharge_summary', 'discharge summary', 'dischargesummary', 'summary'],
            default => [],
        };

        if ($aliases === []) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($aliases) {
            foreach ($aliases as $alias) {
                $compact = str_replace([' ', '_'], '', strtolower($alias));
                $inner->orWhereRaw('LOWER(document_type) = ?', [strtolower($alias)])
                    ->orWhereRaw("REPLACE(REPLACE(LOWER(document_type), ' ', ''), '_', '') = ?", [$compact]);
            }
        });
    }

    protected function buildPatientSummary(DoctorBooking $booking): array
    {
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
        $gender = ucfirst((string) ($patient?->gender ?: $member?->gender ?: '—'));
        $uhid = $member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—');

        $lastVisit = DoctorBooking::query()
            ->where('doctor_id', $booking->doctor_id)
            ->where(fn (Builder $scoped) => PatientRecordScope::apply(
                $scoped,
                $booking->patient_id,
                $booking->member_id
            ))
            ->whereDate('booking_date', '<', now()->toDateString())
            ->orderByDesc('booking_date')
            ->value('booking_date');

        $avatarUrl = null;
        if (filled($patient?->image) && Storage::disk('public')->exists('users/'.ltrim((string) $patient->image, '/'))) {
            $avatarUrl = asset('storage/users/'.ltrim((string) $patient->image, '/'));
        }

        return [
            'name' => $name,
            'uhid' => $uhid,
            'age' => $age,
            'gender' => $gender,
            'last_visit' => $lastVisit ? Carbon::parse($lastVisit)->format('d M Y') : '—',
            'status' => 'Active Patient',
            'avatar_url' => $avatarUrl,
            'initials' => strtoupper(substr(preg_replace('/\s+/', ' ', trim($name)), 0, 2)) ?: 'P',
        ];
    }

    protected function mapDocumentRow(Document $document): array
    {
        $extension = strtolower(pathinfo((string) $document->document_path, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);

        return [
            'id' => $document->id,
            'name' => $document->document_name ?: 'Untitled Document',
            'size' => $this->formatFileSize($document->document_size),
            'type' => $this->displayDocumentType($document->document_type),
            'uploaded_by' => $this->resolveUploadedBy($document),
            'date' => $document->created_at?->format('d M Y') ?? '—',
            'status' => 'Uploaded',
            'is_image' => $isImage,
        ];
    }

    protected function mapDocumentPreview(Document $document): array
    {
        $extension = strtolower(pathinfo((string) $document->document_path, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
        $url = $document->document_url;

        return [
            'id' => $document->id,
            'document_id' => 'DOC-'.$document->id,
            'name' => $document->document_name ?: 'Untitled Document',
            'type' => $this->displayDocumentType($document->document_type),
            'date' => $document->created_at?->format('d M Y') ?? '—',
            'uploaded_by' => $this->resolveUploadedBy($document),
            'lab_location' => '—',
            'reference_no' => 'REF-'.$document->id,
            'signed_by' => $this->resolveUploadedBy($document),
            'notes' => $document->notes ?: 'No physician notes added for this document.',
            'url' => $url,
            'is_image' => $isImage,
            'is_pdf' => $extension === 'pdf',
        ];
    }

    protected function displayDocumentType(?string $type): string
    {
        $value = strtolower(trim(str_replace('_', ' ', (string) $type)));

        return match (true) {
            str_contains($value, 'lab') => 'Lab Report',
            str_contains($value, 'scan') || str_contains($value, 'imaging') || str_contains($value, 'radiology') => 'Scan Report',
            str_contains($value, 'prescription') => 'Prescription',
            str_contains($value, 'discharge') || $value === 'summary' => 'Discharge Summary',
            $value === '' => 'Document',
            default => ucwords($value),
        };
    }

    protected function resolveUploadedBy(Document $document): string
    {
        return \App\Support\DocumentLabelResolver::resolveUploadedBy(
            $document->loadMissing(['member', 'patient', 'diagnosticTestBooking.diagnosticCenter'])
        );
    }

    protected function formatFileSize(mixed $size): string
    {
        if ($size === null || $size === '') {
            return '—';
        }

        if (is_numeric($size)) {
            $bytes = (int) $size;

            if ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 1).' MB';
            }

            if ($bytes >= 1024) {
                return number_format($bytes / 1024, 0).' KB';
            }

            return $bytes.' B';
        }

        return (string) $size;
    }

    protected function buildPatientProfile(DoctorBooking $booking): array
    {
        $doctor = $this->doctor();
        $patient = $booking->patient;
        $member = $booking->member;
        $hipUser = $patient?->hipUser;

        $name = trim(collect([
            $patient?->first_name,
            $patient?->last_name,
        ])->filter()->join(' '));

        if ($name === '') {
            $name = trim((string) ($booking->name ?: $member?->name ?: 'Patient'));
        }

        $dob = $patient?->dob ?: $member?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $gender = ucfirst((string) ($patient?->gender ?: $member?->gender ?: ''));
        $uhid = $member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—');
        $bloodGroup = $member?->blood_group ?: $hipUser?->blood_group;

        $mobile = $booking->mobile_number
            ?: $patient?->mobile
            ?: $member?->mobile_num
            ?: '—';

        $email = $patient?->email ?: $member?->email ?: '—';
        $address = $this->formatPatientAddress($member);
        $avatarUrl = $this->resolveProfileAvatarUrl($patient, $member);
        $initials = strtoupper(substr(preg_replace('/\s+/', ' ', trim($name)), 0, 2)) ?: 'P';

        $emergencyContact = collect([
            $member?->emergency_contact_person_name,
            $member?->emergency_contact_person_relationship
                ? '('.$member->emergency_contact_person_relationship.')'
                : null,
        ])->filter()->implode(' ');

        $patientBookings = $this->patientBookingsForProfile($booking, $doctor);

        $appointmentTimeline = $patientBookings
            ->filter(function (DoctorBooking $item) {
                return $item->booking_date
                    && $item->status !== 'cancelled'
                    && $item->appointment_status !== DoctorBooking::APPOINTMENT_STATUS_CANCELLED;
            })
            ->sortBy(fn (DoctorBooking $item) => $item->booking_date?->timestamp ?? PHP_INT_MAX)
            ->map(fn (DoctorBooking $item) => $this->formatTimelineEntry($item))
            ->values()
            ->all();

        $latestPrescription = $this->latestPrescriptionForPatient($booking, $doctor);
        $latestLab = $this->latestLabDocumentForPatient($booking);
        $currentMedications = $this->formatMedicationSummary($latestPrescription?->medications ?? []);
        $medicalHistory = filled($latestPrescription?->diagnosis)
            ? (string) $latestPrescription->diagnosis
            : null;

        $prescriptionDoctor = $latestPrescription?->doctor?->name
            ? 'Dr. '.$latestPrescription->doctor->name
            : ($doctor?->name ? 'Dr. '.$doctor->name : '—');

        return [
            'booking_id' => $booking->id,
            'name' => $name,
            'uhid' => $uhid,
            'age' => $age,
            'gender' => $gender,
            'blood_group' => $bloodGroup,
            'mobile' => $mobile,
            'email' => $email,
            'address' => $address,
            'emergency_contact' => $emergencyContact ?: '—',
            'emergency_mobile' => $member?->emergency_contact_person_phone ?: '—',
            'allergies' => null,
            'medical_history' => $medicalHistory,
            'current_medications' => $currentMedications ?: '—',
            'avatar_url' => $avatarUrl,
            'initials' => $initials,
            'recent_lab' => [
                'title' => 'Lab Reports',
                'subtitle' => $latestLab?->document_name ?: 'No recent lab reports',
                'when' => $latestLab?->created_at?->diffForHumans() ?? '—',
            ],
            'recent_prescription' => [
                'title' => 'Prescription',
                'subtitle' => $prescriptionDoctor,
                'when' => $latestPrescription?->created_at?->format('d M Y') ?? '—',
            ],
            'appointment_timeline' => $appointmentTimeline,
        ];
    }

    protected function patientBookingsForProfile(DoctorBooking $booking, ?Doctor $doctor): Collection
    {
        if (! $doctor) {
            return collect([$booking]);
        }

        return DoctorBooking::query()
            ->with(['hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->where(fn ($query) => PatientRecordScope::apply(
                $query,
                $booking->patient_id,
                $booking->member_id
            ))
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get();
    }

    protected function latestPrescriptionForPatient(DoctorBooking $booking, ?Doctor $doctor): ?Prescription
    {
        if (! $doctor) {
            return null;
        }

        return Prescription::query()
            ->with('doctor')
            ->where('doctor_id', $doctor->id)
            ->where(fn ($query) => PatientRecordScope::apply(
                $query,
                $booking->patient_id,
                $booking->member_id
            ))
            ->orderByDesc('created_at')
            ->first();
    }

    protected function latestLabDocumentForPatient(DoctorBooking $booking): ?Document
    {
        return Document::query()
            ->where(fn (Builder $scoped) => PatientRecordScope::apply(
                $scoped,
                $booking->patient_id,
                $booking->member_id
            ))
            ->where(function (Builder $inner) {
                $inner->whereRaw('LOWER(document_type) LIKE ?', ['%lab%'])
                    ->orWhereRaw("REPLACE(REPLACE(LOWER(document_type), ' ', ''), '_', '') LIKE ?", ['%lab%']);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    protected function formatPatientAddress(?HIPUser $member): ?string
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

    protected function formatMedicationSummary(array $medications): ?string
    {
        $items = collect($medications)
            ->map(function (array $med) {
                $name = trim((string) ($med['name'] ?? ''));
                if ($name === '') {
                    return null;
                }

                $dosage = trim((string) ($med['dosage'] ?? ''));
                $frequency = $this->medicationFrequencyShort($med['frequency'] ?? null);
                $label = $name;

                if ($dosage !== '') {
                    $label .= ' '.$dosage;
                }

                if ($frequency !== '') {
                    $label .= ' ('.$frequency.')';
                }

                return $label;
            })
            ->filter()
            ->values();

        return $items->isNotEmpty() ? $items->implode(', ') : null;
    }

    protected function medicationFrequencyShort(?string $frequency): string
    {
        $value = strtolower(trim((string) $frequency));

        return match (true) {
            str_contains($value, 'once') || $value === 'daily' => 'QD',
            str_contains($value, 'twice') => 'BD',
            str_contains($value, 'thrice') || str_contains($value, 'three') => 'TDS',
            default => $frequency ?? '',
        };
    }

    protected function formatTimelineEntry(DoctorBooking $booking): array
    {
        $today = now()->startOfDay();
        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $time = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        $branch = $booking->branch?->name ?: $booking->hospital?->name ?: 'OPD';
        $visitLabel = $booking->is_follow_up
            ? 'Follow-up Visit'
            : ($booking->isOnlineConsultation() ? 'Online Consultation' : 'Consultation');

        $isUpcoming = $booking->booking_date
            && $booking->booking_date->greaterThanOrEqualTo($today)
            && $booking->status !== 'cancelled'
            && $booking->appointment_status !== DoctorBooking::APPOINTMENT_STATUS_CANCELLED;

        if ($isUpcoming) {
            return [
                'label' => 'Upcoming Appointment',
                'line' => ($booking->booking_date?->format('d M Y') ?? '—').' • '.$time.' • '.$branch,
                'class' => 'upcoming',
            ];
        }

        return [
            'label' => 'Consultation',
            'line' => ($booking->booking_date?->format('d M Y') ?? '—').' • '.$time.' • '.$visitLabel,
            'class' => 'past',
        ];
    }

    protected function resolveProfileAvatarUrl(?Persons $patient, ?HIPUser $member): ?string
    {
        $candidates = array_filter([
            filled($patient?->image) ? 'users/'.ltrim((string) $patient->image, '/') : null,
            filled($member?->profile_image) ? 'users/'.ltrim((string) $member->profile_image, '/') : null,
        ]);

        foreach ($candidates as $path) {
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/'.$path);
            }
        }

        return null;
    }

    public function render()
    {
        $doctor = $this->doctor();
        $booking = $this->findBooking();
        $branches = $doctor ? $this->branches($doctor) : collect();

        $patient = $booking
            ? $this->buildPatientSummary($booking)
            : [
                'name' => 'Patient',
                'uhid' => '—',
                'age' => null,
                'gender' => '—',
                'last_visit' => '—',
                'status' => '—',
                'avatar_url' => null,
                'initials' => 'P',
            ];

        if (! $booking) {
            $paginatedDocuments = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        } else {
            $paginatedDocuments = $this->documentsQuery($booking)->paginate($this->perPage);
            $paginatedDocuments->setCollection(
                $paginatedDocuments->getCollection()->map(fn (Document $document) => $this->mapDocumentRow($document))
            );
        }

        return view('livewire.doctor-admin.patient-document.view-document', [
            'patient' => $patient,
            'branches' => $branches,
            'documents' => $paginatedDocuments,
        ]);
    }
}
