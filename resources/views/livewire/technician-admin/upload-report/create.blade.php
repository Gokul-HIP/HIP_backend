<div style="padding: 28px 32px;">
<link rel="stylesheet" href="{{ asset('assets/doctor-prescription.css') }}">

<div class="cp-header">
    <div class="cp-header-left">
        <a href="{{ route('technician.upload-report.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Back to Upload Report
        </a>
        <h1>Upload Report – {{ $patient['name'] }}</h1>
        <p>Appointment ID: #{{ $patient['appointment_id'] }} | {{ $patient['centre_name'] }}</p>
    </div>
    <div class="cp-header-actions">
        <a href="{{ route('technician.patient-documents.view', ['booking_id' => $booking->id]) }}" class="btn-view-history">
            <i class="far fa-folder"></i> View Documents
        </a>
    </div>
</div>

<div class="cp-patient-bar">
    <div class="cp-patient-avatar" style="background:#e8f6fc; color:#1A9FD4; font-size:14px; font-weight:700;">
        {{ $patient['initials'] }}
    </div>
    <div class="cp-patient-block">
        <div class="cp-patient-block-label">Patient</div>
        <div class="cp-patient-block-value">
            {{ ucfirst($patient['name']) }}@if($patient['age']), {{ $patient['age'] }}@endif / {{ $patient['gender'] }}
        </div>
    </div>
    <div class="cp-patient-divider"></div>
    <div class="cp-patient-block">
        <div class="cp-patient-block-label">UHID</div>
        <div class="cp-patient-block-value">{{ $patient['uhid'] }}</div>
    </div>
    <div class="cp-patient-divider"></div>
    <div class="cp-patient-block">
        <div class="cp-patient-block-label">Appointment</div>
        <div class="cp-patient-block-value">{{ $patient['appointment_date'] }} • {{ $patient['appointment_time'] }}</div>
    </div>
</div>

<div class="pd-main-layout" style="margin-top:24px;">
    <div>
        <div class="cp-advice-card" style="margin-bottom:20px;">
            <h3>Clinical Advice &amp; Notes</h3>
            <textarea class="cp-textarea" wire:model.live.debounce.500ms="clinicalNotes"
                placeholder="Enter general advice, dietary restrictions, or clinical observations..."></textarea>
        </div>

        <div class="cp-upload-card">
            <h3>Upload Documents</h3>
            <div class="cp-dropzone" role="button" tabindex="0" wire:click="openUploadDocument" wire:keydown.enter="openUploadDocument">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Drop PDF or Images here</p>
                <span>Supports JPG, PNG, PDF up to 10MB</span>
            </div>

            @forelse($uploadedDocuments as $i => $doc)
                <div class="cp-file-item" wire:key="queued-doc-{{ $i }}">
                    <div class="cp-file-item-left">
                        <i class="fas fa-file-pdf"></i>
                        <span class="cp-file-item-name">{{ $doc['name'] }}</span>
                    </div>
                    <button type="button" class="btn-remove-file" wire:click="removeQueuedDocument({{ $i }})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @empty
                <p class="cp-patient-null" style="font-size:13px; margin-top:8px;">No documents uploaded yet.</p>
            @endforelse
        </div>

        @if(count($savedDocuments) > 0)
            <div class="cp-card" style="margin-top:20px;">
                <div class="cp-card-header">
                    <div class="cp-card-title"><i class="far fa-folder-open"></i> Saved Documents</div>
                </div>
                <div style="padding:0 20px 20px;">
                    @foreach($savedDocuments as $doc)
                        <button type="button" wire:click="previewDocument({{ $doc['id'] }})"
                            class="w-full text-left flex items-center justify-between py-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 px-2 rounded">
                            <span class="text-sm font-medium text-gray-800">{{ $doc['name'] }}</span>
                            <span class="text-xs text-gray-500">{{ $doc['date'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div style="margin-top:24px; display:flex; justify-content:flex-end; gap:12px;">
            <a href="{{ route('technician.upload-report.index') }}" class="btn-am-cancel" style="text-decoration:none;">Cancel</a>
            <button type="button" class="btn-am-add" wire:click="saveReports" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveReports">Save Reports</span>
                <span wire:loading wire:target="saveReports">Saving...</span>
            </button>
        </div>
    </div>

    <div class="pd-preview-card">
        <div class="pd-preview-header">
            <div class="pd-preview-title">Document Preview</div>
            <div class="pd-preview-header-actions">
                @if(!empty($previewDoc['url']))
                    <a href="{{ $previewDoc['url'] }}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i></a>
                @endif
                <button type="button" wire:click="closePreview" style="border:none;background:transparent;cursor:pointer;color:#6b7280;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="pd-preview-body">
            @if($previewDoc)
                <div class="pd-preview-thumb">
                    @if(!empty($previewDoc['is_image']) && !empty($previewDoc['url']))
                        <img src="{{ $previewDoc['url'] }}" alt="{{ $previewDoc['name'] }}" style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;">
                    @elseif(!empty($previewDoc['is_pdf']) && !empty($previewDoc['url']))
                        <iframe src="{{ $previewDoc['url'] }}" style="width:100%;height:280px;border:1px solid #e8ecf0;border-radius:8px;"></iframe>
                    @else
                        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                            <i class="far fa-file-alt" style="font-size:42px;margin-bottom:12px;"></i>
                            <div>Preview not available for this file type.</div>
                        </div>
                    @endif
                </div>
                <div class="pd-meta-title">Metadata</div>
                <div class="pd-meta-grid">
                    <div><div class="pd-meta-label">Document ID</div><div class="pd-meta-value">{{ $previewDoc['document_id'] }}</div></div>
                    <div><div class="pd-meta-label">Document Type</div><div class="pd-meta-value">{{ $previewDoc['type'] }}</div></div>
                    <div><div class="pd-meta-label">Uploaded On</div><div class="pd-meta-value">{{ $previewDoc['date'] }}</div></div>
                    <div><div class="pd-meta-label">Uploaded By</div><div class="pd-meta-value">{{ $previewDoc['uploaded_by'] }}</div></div>
                </div>
                <div class="pd-notes-title">Clinical Notes</div>
                <div class="pd-notes-text">{{ $previewDoc['notes'] }}</div>
            @else
                <div style="text-align:center;padding:48px 20px;color:#9ca3af;">
                    <i class="far fa-eye" style="font-size:36px;margin-bottom:12px;"></i>
                    <div>Select a document to preview.</div>
                </div>
            @endif
        </div>
        @if($previewDoc)
            <div class="pd-preview-footer">
                <button type="button" class="btn-pd-download" wire:click="downloadDocument({{ $previewDoc['id'] }})">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        @endif
    </div>
</div>

@if($showUploadDocumentModal)
<div class="ud-overlay" wire:click.self="closeUploadDocument">
    <div class="ud-modal" role="dialog" aria-modal="true">
        <div class="ud-header">
            <div>
                <h2 class="ud-title">Upload Patient Document</h2>
                <p class="ud-subtitle">Upload and attach a medical document to the patient's record.</p>
            </div>
            <button class="ud-close" wire:click="closeUploadDocument"><i class="fas fa-times"></i></button>
        </div>
        <div class="ud-body">
            <div class="ud-patient-bar">
                <div class="ud-patient-avatar"><i class="fas fa-user"></i></div>
                <div class="ud-patient-info">
                    <div class="ud-patient-top">
                        <span class="ud-patient-name">{{ ucfirst($patient['name']) }}</span>
                        <span class="ud-patient-uhid">{{ $patient['uhid'] }}</span>
                    </div>
                    <div class="ud-patient-meta">
                        <span><i class="far fa-hospital"></i> {{ $patient['centre_name'] }}</span>
                        <span class="ud-meta-sep">|</span>
                        <span><i class="far fa-calendar"></i> {{ $patient['appointment_date'] }} • {{ $patient['appointment_time'] }}</span>
                    </div>
                </div>
            </div>
            <div class="ud-dropzone" x-data
                @click="$refs.udFileInput.click()"
                @dragover.prevent="$el.classList.add('ud-dropzone-drag')"
                @dragleave.prevent="$el.classList.remove('ud-dropzone-drag')"
                @drop.prevent="$el.classList.remove('ud-dropzone-drag'); $wire.handleDocumentDrop($event.dataTransfer.files[0])">
                <div class="ud-dropzone-icon"><i class="fas fa-file-arrow-down"></i></div>
                <p class="ud-dropzone-title">Upload Document</p>
                <p class="ud-dropzone-hint">PDF, JPG, PNG up to 10MB</p>
                <input type="file" x-ref="udFileInput" style="display:none;" accept=".pdf,.jpg,.jpeg,.png" wire:model="documentFile">
            </div>
            @if($uploadedFileName)
                <p class="text-sm text-gray-600 mt-2">{{ $uploadedFileName }} ({{ $uploadedFileSize }})</p>
            @endif
            <div class="ud-row">
                <div class="ud-field">
                    <label class="ud-label">DOCUMENT TYPE</label>
                    <select class="ud-select" wire:model.live="documentType">
                        <option value="">Clinical Notes (default)</option>
                        <option value="lab_report">Lab Report</option>
                        <option value="imaging">Imaging / Radiology</option>
                        <option value="scan_report">Scan Report</option>
                        <option value="prescription">Prescription</option>
                        <option value="discharge_summary">Discharge Summary</option>
                        <option value="consultation_notes">Consultation Notes</option>
                        <option value="clinical_notes">Clinical Notes</option>
                        <option value="insurance">Insurance Document</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="ud-field">
                    <label class="ud-label">DOCUMENT TITLE</label>
                    <input type="text" class="ud-input" wire:model.live="documentTitle" placeholder="e.g., Blood Test Report – May 2026">
                </div>
            </div>
            <div class="ud-field">
                <label class="ud-label">CLINICAL NOTES</label>
                <textarea class="ud-textarea" wire:model.live="documentNotes" placeholder="Enter clinical observations, key findings, or remarks..."></textarea>
            </div>
        </div>
        <div class="ud-footer">
            <button type="button" class="btn-ud-cancel" wire:click="closeUploadDocument">Cancel</button>
            <button type="button" class="btn-ud-upload" wire:click="addDocumentToQueue" wire:loading.attr="disabled">
                <i class="fas fa-upload"></i> Upload Document
            </button>
        </div>
    </div>
</div>
@endif
</div>
