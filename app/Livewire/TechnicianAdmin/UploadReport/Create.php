<?php

namespace App\Livewire\TechnicianAdmin\UploadReport;

use App\Models\DiagnosticTestBooking;
use App\Models\Document;
use App\Services\DiagnosticBookingDocumentService;
use App\Services\TechnicianDiagnosticScopeService;
use App\Support\DocumentLabelResolver;
use App\Support\TechnicianPatientViewData;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Create extends Component
{
    use WithFileUploads;

    public ?int $bookingId = null;

    public string $clinicalNotes = '';

    public array $uploadedDocuments = [];

    public bool $showUploadDocumentModal = false;

    public $documentFile;

    public ?string $documentType = null;

    public ?string $documentTitle = null;

    public ?string $documentNotes = null;

    public ?string $uploadedFileName = null;

    public ?string $uploadedFileSize = null;

    public ?int $selectedDocumentId = null;

    public ?array $previewDoc = null;

    public function mount(?string $booking_id = null): void
    {
        $this->bookingId = filled($booking_id) ? (int) $booking_id : null;

        $booking = $this->findBooking();

        if (! $booking) {
            abort(404);
        }

        $this->clinicalNotes = (string) ($booking->clinical_notes ?? '');
    }

    protected function scopeService(): TechnicianDiagnosticScopeService
    {
        return app(TechnicianDiagnosticScopeService::class);
    }

    protected function documentService(): DiagnosticBookingDocumentService
    {
        return app(DiagnosticBookingDocumentService::class);
    }

    protected function findBooking(): ?DiagnosticTestBooking
    {
        if (! $this->bookingId) {
            return null;
        }

        return $this->scopeService()
            ->scopedBookingsQuery()
            ->with(['member', 'patient', 'diagnosticCenter', 'branch', 'documents'])
            ->find($this->bookingId);
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

    public function resetDocumentForm(): void
    {
        $this->documentFile = null;
        $this->documentType = null;
        $this->documentTitle = null;
        $this->documentNotes = null;
        $this->uploadedFileName = null;
        $this->uploadedFileSize = null;
        $this->resetValidation();
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
    }

    public function handleDocumentDrop($file): void
    {
        $this->documentFile = $file;
        $this->updatedDocumentFile();
    }

    public function addDocumentToQueue(): void
    {
        $this->validate([
            'documentFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'documentType' => 'nullable|string|in:lab_report,imaging,scan_report,prescription,discharge_summary,consultation_notes,clinical_notes,insurance,other',
            'documentTitle' => 'nullable|string|max:255',
            'documentNotes' => 'nullable|string|max:2000',
        ], [
            'documentFile.required' => 'Please select a document to upload.',
        ]);

        $storedPath = $this->documentFile->store('patient-documents/' . now()->format('Y/m'), 'public');

        $this->uploadedDocuments[] = [
            'name' => filled($this->documentTitle) ? trim($this->documentTitle) : $this->uploadedFileName,
            'type' => filled($this->documentType) ? $this->documentType : 'clinical_notes',
            'notes' => filled($this->documentNotes) ? trim($this->documentNotes) : null,
            'file_name' => $this->uploadedFileName,
            'file_size' => $this->uploadedFileSize,
            'file_size_bytes' => (string) $this->documentFile->getSize(),
            'stored_path' => $storedPath,
        ];

        $this->closeUploadDocument();
        $this->dispatch('toast', type: 'success', message: 'Document added. Click Save Reports to upload.');
    }

    public function removeQueuedDocument(int $index): void
    {
        if (! isset($this->uploadedDocuments[$index])) {
            return;
        }

        $path = $this->uploadedDocuments[$index]['stored_path'] ?? null;

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        unset($this->uploadedDocuments[$index]);
        $this->uploadedDocuments = array_values($this->uploadedDocuments);
    }

    public function saveReports(): void
    {
        $booking = $this->findBooking();

        if (! $booking) {
            return;
        }

        $this->validate([
            'clinicalNotes' => 'nullable|string|max:5000',
        ]);

        $booking->update([
            'clinical_notes' => filled($this->clinicalNotes) ? trim($this->clinicalNotes) : null,
        ]);

        $savedDocuments = [];

        foreach ($this->uploadedDocuments as $doc) {
            $path = (string) ($doc['stored_path'] ?? '');

            if ($path === '' || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $savedDocuments[] = $this->documentService()->persistFromPath(
                $booking,
                $path,
                (string) ($doc['file_name'] ?? basename($path)),
                $doc['type'] ?? null,
                $doc['name'] ?? null,
                $doc['notes'] ?? null,
                (string) ($doc['file_size_bytes'] ?? ''),
                false
            );
        }

        if ($savedDocuments !== []) {
            $this->documentService()->notifyMember($booking, $savedDocuments);
        }

        $this->uploadedDocuments = [];
        $booking->refresh();

        $this->dispatch('toast', type: 'success', message: count($savedDocuments) > 0
            ? 'Reports saved and patient notified successfully.'
            : 'Clinical notes saved successfully.');

        if (count($savedDocuments) > 0 && $savedDocuments[0]) {
            $this->previewDocument($savedDocuments[0]->id);
        }
    }

    public function previewDocument(int $documentId): void
    {
        $document = $this->findSavedDocument($documentId);

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
        $document = $this->findSavedDocument($documentId);

        if (! $document || ! filled($document->document_path)) {
            $this->dispatch('toast', type: 'error', message: 'Document file is unavailable.');

            return null;
        }

        if (! Storage::disk('public')->exists($document->document_path)) {
            $this->dispatch('toast', type: 'error', message: 'Document file was not found on the server.');

            return null;
        }

        return Storage::disk('public')->download(
            $document->document_path,
            $document->document_name ?: basename($document->document_path)
        );
    }

    protected function findSavedDocument(int $documentId): ?Document
    {
        $booking = $this->findBooking();

        if (! $booking) {
            return null;
        }

        return Document::query()
            ->where('diagnostic_test_booking_id', $booking->id)
            ->find($documentId);
    }

    protected function mapDocumentPreview(Document $document): array
    {
        $extension = strtolower(pathinfo((string) $document->document_path, PATHINFO_EXTENSION));

        return [
            'id' => $document->id,
            'name' => $document->document_name,
            'type' => ucwords(str_replace(['_', '-'], ' ', (string) ($document->document_type ?: 'clinical_notes'))),
            'notes' => $document->notes ?: '—',
            'date' => $document->created_at?->format('d M Y, h:i A') ?: '—',
            'uploaded_by' => DocumentLabelResolver::resolveUploadedBy($document->loadMissing(['diagnosticTestBooking.diagnosticCenter'])),
            'document_id' => 'DOC-' . str_pad((string) $document->id, 5, '0', STR_PAD_LEFT),
            'url' => $document->document_url,
            'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
            'is_pdf' => $extension === 'pdf',
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

    protected function patientContext(DiagnosticTestBooking $booking): array
    {
        $summary = TechnicianPatientViewData::buildPatientSummary($booking);
        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $appointmentTime = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first() . ' - ' . $slots->last(),
        };

        return array_merge($summary, [
            'appointment_date' => $booking->booking_date?->format('d M Y') ?? '—',
            'appointment_time' => $appointmentTime,
            'centre_name' => $booking->diagnosticCenter?->name ?: '—',
            'appointment_id' => (string) $booking->id,
        ]);
    }

    public function render()
    {
        $booking = $this->findBooking();

        if (! $booking) {
            abort(404);
        }

        $savedDocuments = $booking->documents()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Document $doc) => $this->mapDocumentPreview($doc));

        return view('livewire.technician-admin.upload-report.create', [
            'booking' => $booking,
            'patient' => $this->patientContext($booking),
            'savedDocuments' => $savedDocuments,
        ]);
    }
}
