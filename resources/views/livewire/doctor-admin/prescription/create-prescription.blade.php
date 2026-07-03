<div style="padding: 28px 32px;">
<style>
    @import '../../../assets/doctor-prescription.css';
</style>

{{-- ── Page Header ── --}}
<div class="cp-header">
    <div class="cp-header-left">
        <h1>Create New Prescription – {{ $patientName }}</h1>
        <p>
            Appointment ID: #{{ $appointmentId }}
            @if(filled($room))
                | {{ $room }}
            @endif
        </p>
    </div>
    <div class="cp-header-actions">
        <button type="button" class="btn-view-history" wire:click="openPatientHistory">
            <i class="fas fa-history"></i> View History
        </button>
        <button type="button" class="btn-quick-print" wire:click="quickPrint">
            <i class="fas fa-print"></i> Quick Print
        </button>
    </div>
</div>

{{-- ── Patient Info Bar ── --}}
<div class="cp-patient-bar">
    <div class="cp-patient-avatar" style="background:#fff0f2; color:#c8102e; font-size:14px; font-weight:700;">
        {{ $patientInitials }}
    </div>
    <div class="cp-patient-block">
        <div class="cp-patient-block-label">Patient</div>
        <div class="cp-patient-block-value">
            {{ ucfirst($patientName) }}@if($patientAge), {{ $patientAge }}@endif@if($patientGender) / {{ $patientGender }}@endif
        </div>
    </div>
    <div class="cp-patient-divider"></div>
    <div class="cp-patient-block">
        <div class="cp-patient-block-label">Vitals (Last Recorded)</div>
        <div class="cp-patient-block-value cp-patient-null">
            @if(is_array($vitals) && ($vitals['bp'] ?? null || $vitals['spo2'] ?? null))
                BP: {{ $vitals['bp'] ?? '—' }} | SpO2: {{ $vitals['spo2'] ?? '—' }}
            @else
                —
            @endif
        </div>
    </div>
    <div class="cp-patient-divider"></div>
    <div class="cp-patient-block">
        <div class="cp-patient-block-label">Diagnosis</div>
        <div class="cp-patient-block-value cp-patient-null">{{ ucfirst($diagnosis) ?? '—' }}</div>
    </div>
    <span class="cp-patient-status">In-Consultation</span>
</div>

{{-- ── Medications Card ── --}}
<div class="cp-card">
    <div class="cp-card-header">
        <div class="cp-card-title">
            <i class="fas fa-capsules"></i> Medications
        </div>
        <button class="btn-add-medicine" wire:click="addMedicineRow">
            <i class="fas fa-plus-circle"></i> Add Medicine
        </button>
    </div>
    <table class="cp-med-table">
        <thead>
            <tr>
                <th>Medicine Name</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>When to Take</th>
                <th>Qty</th>
                <th>Special Instructions</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($medications as $i => $med)
                <tr wire:key="med-{{ $i }}">
                    <td><span class="cp-med-name">{{ $med['name'] }}</span></td>
                    <td>{{ $med['dosage'] }}</td>
                    <td>
                        <span class="cp-freq-badge">{{ $med['frequency'] }}</span>
                    </td>
                    <td>{{ $med['duration'] ?? '—' }}</td>
                    <td>{{ $med['when_to_take'] ?? '—' }}</td>
                    <td>{{ $med['quantity'] ?? '—' }}</td>
                    <td>{{ $med['special_instruction'] ?? '—' }}</td>
                    <td>
                        <button class="btn-del-med" wire:click="removeMedicine({{ $i }})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#9ca3af; padding:28px 20px;">
                        No medicines added yet. Click <strong>Add Medicine</strong> to start.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Include inside your create-prescription blade, toggle with $showAddMedicineModal --}}
 
@if($showAddMedicineModal)
<div class="am-overlay" wire:click.self="closeAddMedicine">
    <div class="am-modal" role="dialog" aria-modal="true">
 
        {{-- ── Header ── --}}
        <div class="am-header">
            <div>
                <h2 class="am-title">Add Medicine</h2>
                <p class="am-subtitle">Add medicine details to the patient's prescription</p>
            </div>
            <button class="am-close" wire:click="closeAddMedicine" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
 
        {{-- ── Body ── --}}
        <div class="am-body">
 
            {{-- Medicine Name --}}
            <div class="am-field am-medicine-search-wrap" wire:click.away="closeMedicineDropdown">
                <label class="am-label">MEDICINE NAME</label>
                <div class="am-search">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           wire:model.live.debounce.300ms="medicineSearch"
                           wire:focus="openMedicineDropdown"
                           wire:click="openMedicineDropdown"
                           placeholder="Search by name, brand, code, or strength..."
                           autocomplete="off">
                </div>
                @if($showMedicineDropdown)
                    @if(!empty($medicineSuggestions))
                        <div class="am-suggestions">
                            <div class="am-suggestions-count">
                                {{ count($medicineSuggestions) }} medicine{{ count($medicineSuggestions) === 1 ? '' : 's' }} found
                            </div>
                            @foreach($medicineSuggestions as $index => $medicine)
                                <button type="button"
                                        class="am-suggestion-item {{ (int) $selectedMedicineId === (int) $medicine['id'] ? 'is-selected' : '' }}"
                                        wire:click="selectMedicine({{ $index }})"
                                        wire:key="medicine-suggestion-{{ $medicine['id'] }}">
                                    <div class="am-suggestion-name">{{ $medicine['name'] }}</div>
                                    @if(!empty($medicine['brand_name']) || !empty($medicine['strength']) || !empty($medicine['code']))
                                        <div class="am-suggestion-meta">
                                            @if(!empty($medicine['code']))
                                                {{ $medicine['code'] }}
                                            @endif
                                            @if(!empty($medicine['brand_name']))
                                                @if(!empty($medicine['code'])) · @endif
                                                {{ $medicine['brand_name'] }}
                                            @endif
                                            @if(!empty($medicine['strength']))
                                                @if(!empty($medicine['code']) || !empty($medicine['brand_name'])) · @endif
                                                {{ $medicine['strength'] }}
                                            @endif
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="am-suggestions">
                            <div class="am-suggestions-empty">
                                @if(trim($medicineSearch) !== '')
                                    No medicines found for "{{ $medicineSearch }}".
                                @else
                                    No medicines available in master.
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
 
            {{-- Dosage + Frequency --}}
            <div class="am-row">
                <div class="am-field">
                    <label class="am-label">DOSAGE</label>
                    <select class="am-select" wire:model.live="medicineDosage">
                        <option value="">Select dosage</option>
                        @if(filled($medicineDosage) && !in_array($medicineDosage, ['250 mg', '500 mg', '750 mg', '1 g', '2.5 mg', '5 mg', '10 mg', '20 mg', '25 mg', '50 mg', '100 mg'], true))
                            <option value="{{ $medicineDosage }}">{{ $medicineDosage }}</option>
                        @endif
                        <option value="250 mg">250 mg</option>
                        <option value="500 mg">500 mg</option>
                        <option value="750 mg">750 mg</option>
                        <option value="1 g">1 g</option>
                        <option value="2.5 mg">2.5 mg</option>
                        <option value="5 mg">5 mg</option>
                        <option value="10 mg">10 mg</option>
                        <option value="20 mg">20 mg</option>
                        <option value="25 mg">25 mg</option>
                        <option value="50 mg">50 mg</option>
                        <option value="100 mg">100 mg</option>
                    </select>
                </div>
                <div class="am-field">
                    <label class="am-label">FREQUENCY</label>
                    <select class="am-select" wire:model.live="medicineFrequency">
                        <option value="">Select frequency</option>
                        <option value="Once Daily">Once Daily</option>
                        <option value="Twice Daily">Twice Daily</option>
                        <option value="Thrice Daily">Thrice Daily</option>
                        <option value="Every 4 Hours">Every 4 Hours</option>
                        <option value="Every 6 Hours">Every 6 Hours</option>
                        <option value="Every 8 Hours">Every 8 Hours</option>
                        <option value="As Needed">As Needed</option>
                        <option value="Weekly">Weekly</option>
                    </select>
                </div>
            </div>
 
            {{-- Duration + Quantity --}}
            <div class="am-row">
                <div class="am-field">
                    <label class="am-label">DURATION</label>
                    <select class="am-select" wire:model.live="medicineDuration">
                        <option value="">Select duration</option>
                        <option value="1 Day">1 Day</option>
                        <option value="3 Days">3 Days</option>
                        <option value="5 Days">5 Days</option>
                        <option value="7 Days">7 Days</option>
                        <option value="10 Days">10 Days</option>
                        <option value="15 Days">15 Days</option>
                        <option value="30 Days">30 Days</option>
                        <option value="2 Months">2 Months</option>
                        <option value="3 Months">3 Months</option>
                        <option value="Ongoing">Ongoing</option>
                    </select>
                </div>
                <div class="am-field">
                    <label class="am-label">QUANTITY</label>
                    <input type="number"
                           class="am-input"
                           wire:model.live="medicineQuantity"
                           placeholder="Example: 10"
                           min="1">
                </div>
            </div>
 
            {{-- When to Take --}}
            <div class="am-field">
                <label class="am-label">WHEN TO TAKE</label>
                <div class="am-toggle-group">
                    @foreach(['Before Food', 'After Food', 'With Food', 'Morning', 'Afternoon', 'Night', 'Bedtime'] as $option)
                        <button type="button"
                                class="am-toggle {{ in_array($option, $whenToTake) ? 'am-toggle-active' : '' }}"
                                wire:click="toggleWhenToTake('{{ $option }}')">
                            {{ $option }}
                        </button>
                    @endforeach
                </div>
            </div>
 
            {{-- Special Instructions --}}
            <div class="am-field">
                <label class="am-label">SPECIAL INSTRUCTIONS</label>
                <textarea class="am-textarea"
                          wire:model.live="medicineInstructions"
                          placeholder="Example: Take after breakfast..."></textarea>
                {{-- Quick instruction chips --}}
                <div class="am-quick-chips">
                    @foreach(['Complete Full Course', 'Drink Plenty of Water', 'Avoid Alcohol', 'Before Meals'] as $chip)
                        <button type="button"
                                class="am-chip"
                                wire:click="appendInstruction('{{ $chip }}')">
                            {{ $chip }}
                        </button>
                    @endforeach
                </div>
            </div>
 
        </div>
 
        {{-- ── Footer ── --}}
        <div class="am-footer">
            <button type="button" class="btn-am-cancel" wire:click="closeAddMedicine">Cancel</button>
            <button type="button" class="btn-am-add" wire:click="saveMedicine">Add Medicine</button>
        </div>
 
    </div>
</div>
@endif

@if($showLabTestPicker)
<div class="am-overlay" wire:click.self="closeLabTestPicker">
    <div class="am-modal lt-modal" role="dialog" aria-modal="true">
        <div class="am-header">
            <div>
                <h2 class="am-title">Select Lab Tests</h2>
                <p class="am-subtitle">Choose tests from your assigned hospital diagnostic centers</p>
            </div>
            <button type="button" class="am-close" wire:click="closeLabTestPicker" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="am-body">
            <div class="am-field">
                <label class="am-label">SEARCH TESTS</label>
                <div class="am-search">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           wire:model.live.debounce.300ms="labTestSearch"
                           placeholder="Search by test name or code..."
                           autocomplete="off">
                </div>
            </div>

            @forelse($filteredLabTestGroups as $group)
                <div class="lt-hospital-group" wire:key="hospital-lab-{{ $group['hospital_id'] }}">
                    <div class="lt-hospital-name">{{ $group['hospital_name'] }}</div>
                    <div class="lt-test-list">
                        @foreach($group['tests'] as $test)
                            @php
                                $isSelected = $this->isLabTestSelected($group['hospital_id'], $test['id']);
                            @endphp
                            <button type="button"
                                    class="lt-test-item {{ $isSelected ? 'is-selected' : '' }}"
                                    wire:click="toggleLabTest({{ $group['hospital_id'] }}, {{ $test['id'] }})"
                                    wire:key="lab-test-option-{{ $group['hospital_id'] }}-{{ $test['id'] }}">
                                <div>
                                    <div class="lt-test-name">{{ $test['test_name'] }}</div>
                                    @if(!empty($test['test_code']) || filled($test['test_price']))
                                        <div class="lt-test-meta">
                                            @if(!empty($test['test_code']))
                                                {{ $test['test_code'] }}
                                            @endif
                                            @if(filled($test['test_price']))
                                                @if(!empty($test['test_code'])) · @endif
                                                ₹{{ number_format((float) $test['test_price'], 2) }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <span class="lt-test-check">
                                    @if($isSelected)
                                        <i class="fas fa-check"></i>
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="lt-empty">
                    No lab tests found for your assigned hospitals.
                </div>
            @endforelse
        </div>

        <div class="am-footer">
            <button type="button" class="btn-am-cancel" wire:click="closeLabTestPicker">Cancel</button>
            <button type="button" class="btn-am-add" wire:click="closeLabTestPicker">
                Done ({{ count($labTests) }} selected)
            </button>
        </div>
    </div>
</div>
@endif

{{-- ── Bottom Grid ── --}}
<div class="cp-bottom-grid">

    {{-- Left column --}}
    <div>
        {{-- Clinical Advice --}}
        <div class="cp-advice-card">
            <h3>Clinical Advice &amp; Notes</h3>
            <textarea class="cp-textarea"
                      wire:model.live.debounce.500ms="clinicalNotes"
                      placeholder="Enter general advice, dietary restrictions, or clinical observations..."></textarea>
        </div>

        {{-- Lab Tests --}}
        <div class="cp-lab-card">
            <h3>Recommended Lab Tests</h3>
            <div class="cp-lab-tags">
                @forelse($labTests as $i => $test)
                    <span class="cp-lab-tag" wire:key="lab-test-{{ $test['hospital_id'] }}-{{ $test['test_id'] }}">
                        @if($showHospitalOnTags)
                            {{ $test['test_name'] }} ({{ $test['hospital_name'] }})
                        @else
                            {{ $test['test_name'] }}
                        @endif
                        <button type="button" wire:click="removeLabTest({{ $i }})">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                @empty
                    <span class="cp-patient-null" style="font-size:13px;">No lab tests selected yet.</span>
                @endforelse
                <button type="button" class="btn-select-test" wire:click="openLabTestPicker">
                    + Select Test
                </button>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="cp-right-col">

        {{-- Follow-up Date --}}
        <div class="cp-followup-card">
            <h3>Follow-up Date</h3>
            <input type="date"
                   class="cp-date-input"
                   wire:model.live="followUpDate">
            <p class="cp-followup-hint">*Reminder will be sent to patient automatedly.</p>
        </div>

        {{-- Upload Documents --}}
        <div class="cp-upload-card">
            <h3>Upload Documents</h3>
            <div class="cp-dropzone"
                 role="button"
                 tabindex="0"
                 wire:click="openUploadDocument"
                 wire:keydown.enter="openUploadDocument">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Drop PDF or Images here</p>
                <span>Supports JPG, PNG, PDF up to 10MB</span>
            </div>

            @forelse($uploadedDocuments as $i => $doc)
                <div class="cp-file-item" wire:key="doc-{{ $documentUploadKey }}-{{ $i }}">
                    <div class="cp-file-item-left">
                        <i class="fas fa-file-pdf"></i>
                        <span class="cp-file-item-name">{{ $doc['name'] }}</span>
                    </div>
                    <button class="btn-remove-file" wire:click="removeDocument({{ $i }})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @empty
                <p class="cp-patient-null" style="font-size:13px; margin-top:8px;">No documents uploaded yet.</p>
            @endforelse
        </div>

        {{-- Toggle with $showUploadDocumentModal in your Livewire component --}}
 
        @if($showUploadDocumentModal)
        <div class="ud-overlay" wire:click.self="closeUploadDocument">
            <div class="ud-modal" role="dialog" aria-modal="true">
        
                {{-- ── Header ── --}}
                <div class="ud-header">
                    <div>
                        <h2 class="ud-title">Upload Patient Document</h2>
                        <p class="ud-subtitle">
                            Upload and attach medical documents to the patient's record.
                            @if(count($uploadedDocuments) > 0)
                                <strong>{{ count($uploadedDocuments) }} document(s) added.</strong>
                            @endif
                        </p>
                    </div>
                    <button class="ud-close" wire:click="closeUploadDocument" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
        
                {{-- ── Body ── --}}
                <div class="ud-body">
        
                    {{-- Patient Info Bar --}}
                    <div class="ud-patient-bar">
                        <div class="ud-patient-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="ud-patient-info">
                            <div class="ud-patient-top">
                                <span class="ud-patient-name">{{ ucfirst($patientName) }}</span>
                                <span class="ud-patient-uhid">{{ $patientUhid }}</span>
                            </div>
                            <div class="ud-patient-meta">
                                <span><i class="far fa-user-circle"></i> {{ $doctorName }}</span>
                                <span class="ud-meta-sep">|</span>
                                <span><i class="far fa-calendar"></i> {{ $appointmentDate }} • {{ $appointmentTime }}</span>
                            </div>
                        </div>
                    </div>
        
                    {{-- Dropzone --}}
                    <div class="ud-dropzone"
                        x-data
                        x-on:prescription-document-added.window="if ($refs.udFileInput) { $refs.udFileInput.value = ''; }"
                        @click="$refs.udFileInput.click()"
                        @dragover.prevent="$el.classList.add('ud-dropzone-drag')"
                        @dragleave.prevent="$el.classList.remove('ud-dropzone-drag')"
                        @drop.prevent="
                            $el.classList.remove('ud-dropzone-drag');
                            if ($event.dataTransfer.files?.length) {
                                $wire.upload('documentFile', $event.dataTransfer.files[0]);
                            }
                        ">
                        <div class="ud-dropzone-icon">
                            <i class="fas fa-file-arrow-down"></i>
                        </div>
                        <p class="ud-dropzone-title">Upload Document</p>
                        <p class="ud-dropzone-hint">PDF, JPG, PNG up to 10MB</p>
                        <input type="file"
                            x-ref="udFileInput"
                            style="display:none;"
                            accept=".pdf,.jpg,.jpeg,.png"
                            wire:model="documentFile"
                            wire:key="prescription-doc-input-{{ $documentUploadKey }}">
                    </div>

                    @error('documentFile')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
        
                    {{-- Document Type + Title --}}
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
                            <input type="text"
                                class="ud-input"
                                wire:model.live="documentTitle"
                                placeholder="e.g., Blood Test Report – May 2026">
                        </div>
                    </div>
        
                    {{-- Clinical Notes --}}
                    <div class="ud-field">
                        <label class="ud-label">CLINICAL NOTES</label>
                        <textarea class="ud-textarea"
                                wire:model.live="documentNotes"
                                placeholder="Enter clinical observations, key findings, or physician remarks..."></textarea>
                    </div>
        
                    {{-- Uploaded File Preview --}}
                    @if(filled($uploadedFileName))
                    <div class="ud-file-item">
                        <div class="ud-file-icon">
                            <i class="fas fa-file-pdf"></i>
                            <span class="ud-file-type-badge">{{ strtoupper(pathinfo($uploadedFileName, PATHINFO_EXTENSION) ?: 'FILE') }}</span>
                        </div>
                        <div class="ud-file-meta">
                            <div class="ud-file-name">{{ $uploadedFileName }}</div>
                            <div class="ud-file-size">{{ $uploadedFileSize }} • Ready to upload</div>
                        </div>
                        <button class="ud-file-remove" wire:click="removeDocumentFile" type="button">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    @endif
        
                    {{-- HIP App Checkbox --}}
                    <label class="ud-checkbox-row">
                        <div class="ud-checkbox-wrap">
                            <input type="checkbox"
                                wire:model.live="makeAvailableInApp"
                                class="ud-checkbox"
                                id="ud-hip-app">
                            <span class="ud-checkmark"><i class="fas fa-check"></i></span>
                        </div>
                        <div class="ud-checkbox-text">
                            <span class="ud-checkbox-label">Make this document available in the patient's HIP app.</span>
                            <span class="ud-checkbox-hint">Patient will be notified and can view this in their health timeline.</span>
                        </div>
                    </label>
        
                </div>
        
                {{-- ── Footer ── --}}
                <div class="ud-footer">
                    <button type="button" class="btn-ud-cancel" wire:click="closeUploadDocument">Cancel</button>
                    @if(count($uploadedDocuments) > 0)
                        <button type="button" class="btn-ud-cancel" wire:click="finishUploadDocuments">Done</button>
                    @endif
                    <button type="button"
                            class="btn-ud-upload"
                            wire:click="uploadDocument"
                            wire:loading.attr="disabled"
                            wire:target="documentFile,uploadDocument">
                        <span wire:loading.remove wire:target="documentFile,uploadDocument">
                            <i class="fas fa-cloud-upload-alt"></i> Add Document
                        </span>
                        <span wire:loading wire:target="documentFile,uploadDocument">
                            <i class="fas fa-spinner fa-spin"></i> Uploading...
                        </span>
                    </button>
                </div>
        
            </div>
        </div>
        @endif


    </div>
</div>

{{-- ── Bottom Action Bar ── --}}
<div class="cp-action-bar">
    <div class="cp-autosave-info">
        <i class="fas fa-info-circle"></i>
        All changes are being autosaved as draft.
    </div>
    {{-- <button class="btn-save-draft" wire:click="saveDraft">Save Draft</button> --}}
    <button class="btn-send-prescription" wire:click="sendPrescription">Send Prescription</button>
    <button class="btn-complete-consultation" wire:click="completeConsultation">Complete Consultation</button>
</div>

@if($showPatientHistoryPanel)
    <div class="ph-overlay" wire:click="closePatientHistory"></div>
    <aside class="ph-panel ph-panel-open" role="dialog" aria-label="Patient Profile Quick View">
        <div class="ph-panel-header">
            <h2>Patient Profile Quick View</h2>
            <button type="button" class="ph-panel-close" wire:click="closePatientHistory" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="ph-panel-body">
            <div class="ph-patient-card">
                <div class="ph-patient-top">
                    <div class="ph-patient-avatar">{{ $patientInitials }}</div>
                    <div>
                        <div class="ph-patient-name">{{ ucfirst($patientName) }}</div>
                        <div class="ph-patient-uhid">UHID: {{ $patientUhid }}</div>
                    </div>
                </div>
                <div class="ph-badges">
                    @if($patientBloodGroup)
                        <span class="ph-badge blood"><i class="fas fa-tint"></i> {{ $patientBloodGroup }}</span>
                    @endif
                    @if($patientAge)
                        <span class="ph-badge">{{ $patientAge }} Years</span>
                    @endif
                    @if($patientGenderFull)
                        <span class="ph-badge">{{ $patientGenderFull }}</span>
                    @endif
                </div>
            </div>

            <div class="ph-section">
                <div class="ph-section-title">Contact Details</div>
                <div class="ph-detail-row">
                    <i class="fas fa-phone"></i>
                    <div>
                        <div class="ph-detail-label">Mobile</div>
                        {{ $patientMobile ?: '—' }}
                    </div>
                </div>
                <div class="ph-detail-row">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <div class="ph-detail-label">Email</div>
                        {{ $patientEmail ?: '—' }}
                    </div>
                </div>
                <div class="ph-detail-row">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <div class="ph-detail-label">Address</div>
                        {{ $patientAddress ?: '—' }}
                    </div>
                </div>
            </div>

            <div class="ph-section">
                <div class="ph-section-title">Previous Medication History</div>
                <label class="ph-select-label" for="prescription-history-select">Select prescription</label>
                <select id="prescription-history-select"
                        class="ph-select"
                        wire:model.live="selectedPrescriptionHistoryId">
                    @forelse($prescriptionHistoryOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @empty
                        <option value="">No previous prescriptions</option>
                    @endforelse
                </select>

                @if($selectedPrescriptionHistory)
                    <ul class="ph-med-list">
                        @forelse($selectedPrescriptionHistory['medications'] ?? [] as $med)
                            <li class="ph-med-item">
                                <i class="fas fa-pills"></i>
                                <div>
                                    <strong>{{ $med['name'] ?? '—' }}</strong>
                                    @if(!empty($med['dosage']) || !empty($med['frequency']))
                                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                                            {{ collect([$med['dosage'] ?? null, $med['frequency'] ?? null, $med['duration'] ?? null])->filter()->implode(' • ') }}
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="ph-empty">No medicines recorded for this prescription.</li>
                        @endforelse
                    </ul>
                    @if(!empty($selectedPrescriptionHistory['clinical_notes']))
                        <div style="margin-top:10px;font-size:12.5px;color:#4b5563;">
                            <strong>Notes:</strong> {{ $selectedPrescriptionHistory['clinical_notes'] }}
                        </div>
                    @endif
                @else
                    <p class="ph-empty">No previous medication history with this doctor.</p>
                @endif
            </div>

            <div class="ph-section">
                <div class="ph-section-title">Previous Appointment History</div>
                <label class="ph-select-label" for="appointment-history-select">Select appointment</label>
                <select id="appointment-history-select"
                        class="ph-select"
                        wire:model.live="selectedAppointmentHistoryId">
                    @forelse($appointmentHistoryOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @empty
                        <option value="">No previous appointments</option>
                    @endforelse
                </select>

                @if($selectedAppointmentHistory)
                    <div class="ph-appt-card">
                        <div class="ph-appt-row">
                            <span>Date</span>
                            <strong>{{ $selectedAppointmentHistory['date'] }}</strong>
                        </div>
                        <div class="ph-appt-row">
                            <span>Time</span>
                            <strong>{{ $selectedAppointmentHistory['time'] }}</strong>
                        </div>
                        <div class="ph-appt-row">
                            <span>Visit Type</span>
                            <strong>{{ $selectedAppointmentHistory['visit_type'] }}</strong>
                        </div>
                        <div class="ph-appt-row">
                            <span>Branch</span>
                            <strong>{{ $selectedAppointmentHistory['branch'] }}</strong>
                        </div>
                        <div class="ph-appt-row">
                            <span>Status</span>
                            <strong>{{ $selectedAppointmentHistory['status'] }}</strong>
                        </div>
                        @if(!empty($selectedAppointmentHistory['reason']))
                            <div class="ph-appt-row">
                                <span>Reason</span>
                                <strong>{{ $selectedAppointmentHistory['reason'] }}</strong>
                            </div>
                        @endif
                    </div>

                    <div class="ph-timeline">
                        <div class="ph-timeline-item past">
                            {{ $selectedAppointmentHistory['date'] }} • {{ $selectedAppointmentHistory['time'] }}
                            <div class="ph-timeline-date">
                                {{ $selectedAppointmentHistory['visit_type'] }} • {{ $selectedAppointmentHistory['branch'] }}
                            </div>
                        </div>
                    </div>
                @else
                    <p class="ph-empty">No previous appointments with this doctor.</p>
                @endif
            </div>
        </div>
    </aside>
@endif

<div class="qp-print-sheet" aria-hidden="true">
    <h1 class="qp-print-title">Medications</h1>
    <table class="qp-print-table">
        <thead>
            <tr>
                <th>Medicine Name</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>When to Take</th>
                <th>Qty</th>
                <th>Special Instructions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($medications as $med)
                <tr>
                    <td>{{ $med['name'] ?? '—' }}</td>
                    <td>{{ $med['dosage'] ?? '—' }}</td>
                    <td>{{ $med['frequency'] ?? '—' }}</td>
                    <td>{{ $med['duration'] ?? '—' }}</td>
                    <td>{{ $med['when_to_take'] ?? '—' }}</td>
                    <td>{{ $med['quantity'] ?? '—' }}</td>
                    <td>{{ $med['special_instruction'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    (function () {
        if (window.__qpPrintListenerBound) {
            return;
        }

        window.__qpPrintListenerBound = true;

        document.addEventListener('livewire:init', () => {
            Livewire.on('quick-print', () => {
                document.body.classList.add('qp-printing');
                setTimeout(() => window.print(), 150);
            });
        });

        window.addEventListener('afterprint', () => {
            document.body.classList.remove('qp-printing');
        });
    })();
</script>

</div>