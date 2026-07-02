<?php

namespace App\Livewire\TechnicianAdmin\DiagnosticTestBooking;

use App\Models\DiagnosticTestBookingStatus;
use App\Models\Document;
use App\Services\DiagnosticBookingDocumentService;
use App\Services\NotificationService;
use App\Services\TechnicianDiagnosticScopeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class AppointmentDetails extends Component
{
    use WithFileUploads;

    public $id;

    public $diagnosticBooking;

    public $statuses;

    public $statusHistory;

    public $notesHistory;

    public $notes;

    public $deleteNoteId;

    public string $clinicalNotes = '';

    public bool $showUploadDocumentModal = false;

    public bool $showEditDocumentModal = false;

    public bool $showViewDocumentModal = false;

    public $documentFile;

    public ?string $documentType = null;

    public ?string $documentTitle = null;

    public ?string $documentNotes = null;

    public ?string $uploadedFileName = null;

    public ?string $uploadedFileSize = null;

    public ?int $editingDocumentId = null;

    public ?string $editDocumentType = null;

    public ?string $editDocumentTitle = null;

    public ?string $editDocumentNotes = null;

    public $editDocumentFile;

    public ?array $viewingDocument = null;

    public ?int $deleteDocumentId = null;

    public function mount($id): void
    {
        $this->id = $id;
        $this->loadData();
    }

    protected function scopeService(): TechnicianDiagnosticScopeService
    {
        return app(TechnicianDiagnosticScopeService::class);
    }

    protected function documentService(): DiagnosticBookingDocumentService
    {
        return app(DiagnosticBookingDocumentService::class);
    }

    public function loadData(): void
    {
        $this->diagnosticBooking = $this->scopeService()
            ->scopedBookingsQuery()
            ->with(['member', 'patient', 'diagnosticCenter', 'branch', 'statuses.changedBy', 'statuses.notesBy', 'documents'])
            ->findOrFail($this->id);

        $this->clinicalNotes = (string) ($this->diagnosticBooking->clinical_notes ?? '');

        $this->statuses = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->latest()
            ->first();

        $this->statusHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $this->notesHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderByDesc('created_at')
            ->get();

        $this->notes = $this->diagnosticBooking->notes()->latest()->first();
    }

    public function saveClinicalNotes(): void
    {
        $this->validate([
            'clinicalNotes' => 'nullable|string|max:5000',
        ]);

        $this->diagnosticBooking->update([
            'clinical_notes' => filled($this->clinicalNotes) ? trim($this->clinicalNotes) : null,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Clinical advice & notes saved successfully!');
        $this->loadData();
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

    public function uploadDocument(): void
    {
        $this->validate([
            'documentFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'documentType' => 'nullable|string|in:lab_report,imaging,scan_report,prescription,discharge_summary,consultation_notes,clinical_notes,insurance,other',
            'documentTitle' => 'nullable|string|max:255',
            'documentNotes' => 'nullable|string|max:2000',
        ], [
            'documentFile.required' => 'Please select a document to upload.',
        ]);

        $this->documentService()->store(
            $this->diagnosticBooking,
            $this->documentFile,
            $this->documentType,
            $this->documentTitle,
            $this->documentNotes
        );

        $this->closeUploadDocument();
        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Document uploaded and patient notified.');
    }

    public function openEditDocument(int $documentId): void
    {
        $document = $this->findBookingDocument($documentId);

        if (! $document) {
            return;
        }

        $this->editingDocumentId = $document->id;
        $this->editDocumentType = $document->document_type;
        $this->editDocumentTitle = $document->document_name;
        $this->editDocumentNotes = $document->notes;
        $this->editDocumentFile = null;
        $this->showEditDocumentModal = true;
    }

    public function closeEditDocument(): void
    {
        $this->showEditDocumentModal = false;
        $this->editingDocumentId = null;
        $this->editDocumentFile = null;
        $this->resetValidation();
    }

    public function updateDocument(): void
    {
        $document = $this->editingDocumentId ? $this->findBookingDocument($this->editingDocumentId) : null;

        if (! $document) {
            return;
        }

        $this->validate([
            'editDocumentFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'editDocumentType' => 'nullable|string|in:lab_report,imaging,scan_report,prescription,discharge_summary,consultation_notes,clinical_notes,insurance,other',
            'editDocumentTitle' => 'nullable|string|max:255',
            'editDocumentNotes' => 'nullable|string|max:2000',
        ]);

        $this->documentService()->update(
            $document,
            $this->diagnosticBooking,
            $this->editDocumentFile,
            $this->editDocumentType,
            $this->editDocumentTitle,
            $this->editDocumentNotes
        );

        $this->closeEditDocument();
        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Document updated successfully!');
    }

    public function openViewDocument(int $documentId): void
    {
        $document = $this->findBookingDocument($documentId);

        if (! $document) {
            return;
        }

        $this->viewingDocument = [
            'id' => $document->id,
            'name' => $document->document_name,
            'type' => $this->formatDocumentType($document->document_type),
            'notes' => $document->notes,
            'url' => $document->document_url,
            'is_image' => $this->isImagePath($document->document_path),
        ];
        $this->showViewDocumentModal = true;
    }

    public function closeViewDocument(): void
    {
        $this->showViewDocumentModal = false;
        $this->viewingDocument = null;
    }

    public function openDeleteDocumentModal(int $documentId): void
    {
        if (! $this->findBookingDocument($documentId)) {
            return;
        }

        $this->deleteDocumentId = $documentId;
        Flux::modal('delete-document')->show();
    }

    public function closeDeleteDocumentModal(): void
    {
        $this->deleteDocumentId = null;
        Flux::modal('delete-document')->close();
    }

    public function deleteDocument(): void
    {
        $document = $this->deleteDocumentId ? $this->findBookingDocument($this->deleteDocumentId) : null;

        if (! $document) {
            $this->closeDeleteDocumentModal();

            return;
        }

        $this->documentService()->delete($document);
        $this->closeDeleteDocumentModal();
        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Document deleted successfully!');
    }

    protected function findBookingDocument(int $documentId): ?Document
    {
        return Document::query()
            ->where('diagnostic_test_booking_id', $this->diagnosticBooking->id)
            ->find($documentId);
    }

    public function openAddNoteModal(): void
    {
        $this->dispatch('openAddNoteModal', id: $this->id);
    }

    public function openEditNoteModal($noteId): void
    {
        $this->dispatch('openEditNoteModal', noteId: $noteId);
    }

    public function openDeleteNoteModal($id): void
    {
        $note = DiagnosticTestBookingStatus::find($id);

        if ($note && $note->notes_by == Auth::id()) {
            Flux::modal('delete-note')->show();
            $this->deleteNoteId = $id;
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    public function closeDeleteNoteModal(): void
    {
        $this->deleteNoteId = null;
        Flux::modal('delete-note')->close();
    }

    public function deleteNote(): void
    {
        $note = DiagnosticTestBookingStatus::find($this->deleteNoteId);

        if ($note && $note->notes_by == Auth::id()) {
            $note->delete();
            $this->loadData();
            $this->closeDeleteNoteModal();
            $this->dispatch('toast', type: 'success', message: 'Note deleted successfully!');
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    #[On('refreshAppointmentDetails')]
    public function refresh(): void
    {
        $this->loadData();
    }

    public function updateStatusInstant($status): void
    {
        $booking = $this->diagnosticBooking;
        $oldStatus = $booking->status;

        $booking->status = $status;
        $booking->save();

        DiagnosticTestBookingStatus::create([
            'diagnostic_test_booking_id' => $booking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::id(),
        ]);

        if ($status === 'confirmed') {
            $this->sendConfirmationNotification($booking);
        }

        if ($status === 'cancelled') {
            $this->sendCancellationNotification($booking);
        }

        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $booking->name . ' successfully!');
    }

    protected function sendConfirmationNotification($booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        app(NotificationService::class)->notifyUser((string) $booking->member_id, 'Your Package booking is confirmed!', 'Your diagnostic booking has been confirmed.', [
            'type' => 'package_confirmed',
            'screen' => 'booking_history',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    protected function sendCancellationNotification($booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        app(NotificationService::class)->notifyUser((string) $booking->member_id, 'Your Package booking is cancelled!', 'Your diagnostic booking has been cancelled.', [
            'type' => 'package_cancelled',
            'screen' => 'booking_history',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
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

    protected function formatDocumentType(?string $type): string
    {
        return ucwords(str_replace(['_', '-'], ' ', (string) ($type ?: 'clinical_notes')));
    }

    protected function isImagePath(?string $path): bool
    {
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function render()
    {
        $documents = $this->diagnosticBooking?->documents()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Document $doc) => [
                'id' => $doc->id,
                'name' => $doc->document_name,
                'type' => $this->formatDocumentType($doc->document_type),
                'type_raw' => $doc->document_type,
                'notes' => $doc->notes,
                'size' => $this->formatFileSize((int) ($doc->document_size ?: 0)),
                'date' => $doc->created_at?->format('d M Y'),
                'url' => $doc->document_url,
                'is_image' => $this->isImagePath($doc->document_path),
            ]) ?? collect();

        return view('livewire.technician-admin.diagnostic-test-booking.appointment-details', [
            'documents' => $documents,
        ]);
    }
}
