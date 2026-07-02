<?php

namespace App\Services;

use App\Models\DiagnosticTestBooking;
use App\Models\Document;
use App\Support\DocumentLabelResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DiagnosticBookingDocumentService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function store(
        DiagnosticTestBooking $booking,
        UploadedFile $file,
        ?string $documentType,
        ?string $documentTitle,
        ?string $clinicalNotes = null
    ): Document {
        $storedPath = $file->store('patient-documents/' . now()->format('Y/m'), 'public');
        $originalName = $file->getClientOriginalName();

        return $this->persistFromPath(
            $booking,
            $storedPath,
            $originalName,
            $documentType,
            $documentTitle,
            $clinicalNotes,
            (string) $file->getSize()
        );
    }

    public function persistFromPath(
        DiagnosticTestBooking $booking,
        string $storedPath,
        string $originalName,
        ?string $documentType,
        ?string $documentTitle,
        ?string $clinicalNotes = null,
        ?string $fileSize = null,
        bool $notify = true
    ): Document {
        $document = Document::create([
            'member_id' => $booking->member_id,
            'patient_id' => DocumentLabelResolver::resolvePatientIdForBooking($booking),
            'diagnostic_test_booking_id' => $booking->id,
            'document_name' => filled($documentTitle) ? trim($documentTitle) : $originalName,
            'notes' => filled($clinicalNotes) ? trim($clinicalNotes) : null,
            'document_path' => $storedPath,
            'document_type' => filled($documentType) ? trim($documentType) : 'clinical_notes',
            'document_size' => $fileSize,
        ]);

        if ($notify) {
            $this->notifyMember($booking, [$document]);
        }

        return $document;
    }

    public function update(
        Document $document,
        DiagnosticTestBooking $booking,
        ?UploadedFile $file = null,
        ?string $documentType = null,
        ?string $documentTitle = null,
        ?string $clinicalNotes = null
    ): Document {
        $updates = [];

        if ($file) {
            if ($document->document_path) {
                Storage::disk('public')->delete($document->document_path);
            }

            $storedPath = $file->store('patient-documents/' . now()->format('Y/m'), 'public');
            $updates['document_path'] = $storedPath;
            $updates['document_size'] = (string) $file->getSize();

            if (! filled($documentTitle)) {
                $updates['document_name'] = $file->getClientOriginalName();
            }
        }

        if (filled($documentTitle)) {
            $updates['document_name'] = trim($documentTitle);
        }

        if ($documentType !== null) {
            $updates['document_type'] = filled($documentType) ? trim($documentType) : 'clinical_notes';
        }

        if ($clinicalNotes !== null) {
            $updates['notes'] = filled($clinicalNotes) ? trim($clinicalNotes) : null;
        }

        if ($updates !== []) {
            $document->update($updates);
        }

        return $document->fresh();
    }

    public function delete(Document $document): void
    {
        if ($document->document_path) {
            Storage::disk('public')->delete($document->document_path);
        }

        $document->delete();
    }

    /**
     * @param  list<Document>  $documents
     */
    public function notifyMember(DiagnosticTestBooking $booking, array $documents): void
    {
        if (! $booking->member_id || $documents === []) {
            return;
        }

        try {
            $primaryType = (string) ($documents[0]->document_type ?? 'report');
            $label = DocumentLabelResolver::formatDocumentTypeLabel($primaryType);
            $count = count($documents);

            $title = 'Diagnostic Center uploaded a report';
            $body = $count > 1
                ? 'Diagnostic Center uploaded ' . $count . ' ' . $label . ' reports'
                : 'Diagnostic Center uploaded a ' . $label;

            $documentIds = collect($documents)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all();

            $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, [
                'type' => 'report',
                'screen' => 'records',
                'document_type' => $primaryType,
                'patient_id' => (string) ($booking->patient_id ?? ''),
                'patient_name' => (string) ($booking->name ?? ''),
                'relationship' => $booking->relationship,
                'document_ids' => $documentIds,
                'documents_count' => $count,
                'diagnostic_test_booking_id' => (string) $booking->id,
                'url' => '/records',
                'route' => '/records',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Diagnostic booking document notification failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
