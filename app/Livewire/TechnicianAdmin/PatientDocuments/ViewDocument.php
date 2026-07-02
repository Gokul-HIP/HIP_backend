<?php

namespace App\Livewire\TechnicianAdmin\PatientDocuments;

use App\Models\DiagnosticTestBooking;
use App\Models\Document;
use App\Services\TechnicianDiagnosticScopeService;
use App\Support\TechnicianPatientViewData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    public ?int $selectedDocumentId = null;

    public ?array $previewDoc = null;

    public bool $showPatientProfilePanel = false;

    public ?array $profilePatient = null;

    public bool $showBookingHistoryModal = false;

    public ?array $historyPatient = null;

    public array $historyStats = [];

    public int $perPage = 10;

    public function mount(?string $booking_id = null): void
    {
        $this->bookingId = filled($booking_id) ? (int) $booking_id : null;
    }

    public function updatingDocumentTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateRangeFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->documentTypeFilter = 'all';
        $this->dateRangeFilter = '6_months';
        $this->resetPage();
    }

    protected function scopeService(): TechnicianDiagnosticScopeService
    {
        return app(TechnicianDiagnosticScopeService::class);
    }

    protected function findBooking(): ?DiagnosticTestBooking
    {
        if (! $this->bookingId) {
            return null;
        }

        return $this->scopeService()
            ->scopedBookingsQuery()
            ->with(['member', 'patient', 'diagnosticCenter', 'branch'])
            ->find($this->bookingId);
    }

    protected function documentsQuery(DiagnosticTestBooking $booking): Builder
    {
        $bookingIds = DiagnosticTestBooking::query()
            ->when($booking->patient_id, fn ($q) => $q->where('patient_id', $booking->patient_id))
            ->when(! $booking->patient_id && $booking->member_id, fn ($q) => $q->where('member_id', $booking->member_id))
            ->whereIn('diagnostic_center_id', $this->scopeService()->diagnosticCenterIds())
            ->pluck('id');

        $query = Document::query()
            ->whereIn('diagnostic_test_booking_id', $bookingIds)
            ->when($this->documentTypeFilter !== 'all', fn ($q) => $q->where('document_type', $this->documentTypeFilter))
            ->when($this->dateRangeFilter !== 'all', function ($q) {
                $fromDate = match ($this->dateRangeFilter) {
                    '30_days' => now()->subDays(30)->startOfDay(),
                    '1_year' => now()->subYear()->startOfDay(),
                    default => now()->subMonths(6)->startOfDay(),
                };

                $q->where('created_at', '>=', $fromDate);
            });

        return $query->orderByDesc('created_at')->orderByDesc('id');
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
        $booking = $this->findBooking();

        if (! $booking) {
            return null;
        }

        return $this->documentsQuery($booking)->find($documentId);
    }

    protected function mapDocumentPreview(Document $document): array
    {
        $extension = strtolower(pathinfo((string) $document->document_path, PATHINFO_EXTENSION));

        return [
            'id' => $document->id,
            'name' => $document->document_name,
            'type' => ucwords(str_replace(['_', '-'], ' ', (string) ($document->document_type ?: 'clinical_notes'))),
            'notes' => $document->notes ?: '—',
            'date' => $document->created_at?->format('d M Y') ?: '—',
            'uploaded_by' => 'Diagnostic Center',
            'document_id' => 'DOC-' . str_pad((string) $document->id, 5, '0', STR_PAD_LEFT),
            'url' => $document->document_url,
            'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
            'is_pdf' => $extension === 'pdf',
            'status' => 'Completed',
            'size' => $document->document_size ? $this->formatFileSize((int) $document->document_size) : '—',
        ];
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    public function viewPatientProfile(): void
    {
        $booking = $this->findBooking();

        if (! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Patient profile is unavailable.');

            return;
        }

        $this->profilePatient = TechnicianPatientViewData::buildPatientProfile($booking);
        $this->showPatientProfilePanel = true;
    }

    public function closePatientProfile(): void
    {
        $this->showPatientProfilePanel = false;
        $this->profilePatient = null;
    }

    public function openBookingHistory(): void
    {
        $booking = $this->findBooking();

        if (! $booking) {
            return;
        }

        $allBookings = TechnicianPatientViewData::patientBookings($booking);
        $this->historyPatient = TechnicianPatientViewData::buildPatientSummary($booking);
        $this->historyStats = TechnicianPatientViewData::buildHistoryStats($allBookings);
        $this->showBookingHistoryModal = true;
    }

    public function closeBookingHistory(): void
    {
        $this->showBookingHistoryModal = false;
        $this->historyPatient = null;
    }

    public function render()
    {
        $booking = $this->findBooking();

        if (! $booking) {
            abort(404);
        }

        $patient = TechnicianPatientViewData::buildPatientSummary($booking);
        $documents = $this->documentsQuery($booking)->paginate($this->perPage);
        $documentRows = $documents->through(fn (Document $doc) => $this->mapDocumentPreview($doc));

        return view('livewire.technician-admin.patient-documents.view-document', [
            'patient' => $patient,
            'documents' => $documentRows,
            'bookingHistory' => TechnicianPatientViewData::patientBookings($booking),
        ]);
    }
}
