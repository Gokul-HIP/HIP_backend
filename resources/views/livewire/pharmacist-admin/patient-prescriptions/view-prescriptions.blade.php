<div style="padding: 28px 32px;">
<link rel="stylesheet" href="{{ asset('assets/doctor-prescription.css') }}">
<style>
    .pd-table tbody tr.latest-row {
        background: #f0fdf4;
        box-shadow: inset 3px 0 0 #22c55e;
    }
    .pd-table tbody tr.latest-row.active-row {
        background: #fff5f6;
        box-shadow: inset 3px 0 0 var(--button-color);
    }
    .pd-latest-badge {
        display: inline-block;
        margin-left: 8px;
        padding: 2px 8px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #15803d;
        background: #dcfce7;
        border-radius: 999px;
        vertical-align: middle;
    }
    .pd-rx-meds-list { margin: 0; padding-left: 18px; font-size: 13px; color: #374151; }
    .pd-rx-meds-list li { margin-bottom: 6px; }
    .pd-rx-section { margin-bottom: 16px; }
    .pd-rx-section-title { font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: #9ca3af; margin-bottom: 8px; }
    .rx-meds-expand-row td {
        padding: 0 18px 18px;
        background: #fff5f6;
        border-bottom: 1px solid #f0f4f8;
    }
    .rx-meds-expand-row .cp-card {
        margin-bottom: 0;
        border-color: #f3d4d8;
    }
</style>

<div class="mb-4">
    <a href="{{ route('pharmacist.patient-prescriptions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Back to Patient Prescription
    </a>
</div>

<div class="pd-patient-card">
    <div class="pd-patient-left">
        <div class="pd-patient-avatar-init">{{ $patient['initials'] ?? 'P' }}</div>
        <div style="min-width:0;">
            <div class="pd-patient-name-row">
                <span class="pd-patient-name">{{ $patient['name'] ?? '—' }}</span>
                <span class="pd-active-pill">{{ $patient['status'] ?? 'Active Patient' }}</span>
            </div>
            <div class="pd-patient-meta">
                <span><strong>UHID:</strong> {{ $patient['uhid'] ?? '—' }}</span>
                <span><strong>Age/Gender:</strong> {{ $patient['age'] ?? '—' }} / {{ $patient['gender'] ?? '—' }}</span>
                <span><strong>Last Prescription:</strong> {{ $patient['last_visit'] ?? '—' }}</span>
                <span><strong>Total:</strong> {{ $totalPrescriptions }}</span>
            </div>
        </div>
    </div>
</div>

<div class="pd-filters-bar">
    <div class="pd-filters-label"><i class="fas fa-sliders-h"></i> Filters:</div>
    <select class="pd-select" wire:model.live="statusFilter">
        <option value="all">All Status</option>
        <option value="draft">Draft</option>
        <option value="sent">Sent</option>
        <option value="completed">Completed</option>
    </select>
    <select class="pd-select" wire:model.live="dateRangeFilter">
        <option value="6_months">Date Range: Last 6 Months</option>
        <option value="30_days">Date Range: Last 30 Days</option>
        <option value="1_year">Date Range: Last 1 Year</option>
        <option value="all">Date Range: All Time</option>
    </select>
    <div class="pd-filters-spacer"></div>
    <button type="button" class="pd-reset-link" wire:click="resetFilters">Reset Filters</button>
</div>

<div class="pd-main-layout">
    <div class="pd-table-card">
        <table class="pd-table">
            <thead>
                <tr>
                    <th>Prescription</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Medicines</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prescriptions as $rx)
                    @php
                        $rowClass = trim(implode(' ', array_filter([
                            ($selectedPrescriptionId ?? null) === $rx['id'] ? 'active-row' : '',
                            !empty($rx['is_latest']) ? 'latest-row' : '',
                        ])));
                    @endphp
                    <tr class="{{ $rowClass }}" wire:key="rx-{{ $rx['id'] }}">
                        <td>
                            <div class="pd-doc-name-cell">
                                <div class="pd-doc-icon icon-pdf">
                                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                                </div>
                                <div>
                                    <div class="pd-doc-name">
                                        {{ $rx['prescription_id'] }}
                                        @if(!empty($rx['is_latest']))
                                            <span class="pd-latest-badge">Latest</span>
                                        @endif
                                    </div>
                                    <div class="pd-doc-size">{{ $rx['documents_count'] }} prescription file(s)</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $rx['uploaded_by'] }}</td>
                        <td>{{ $rx['date'] }}</td>
                        <td>{{ $rx['medications_count'] }}</td>
                        <td>
                            <span class="pd-status-pill status-completed-doc">
                                <span class="dot"></span> {{ $rx['status'] }}
                            </span>
                        </td>
                        <td>
                            <div class="pd-row-actions">
                                <button type="button" class="pd-row-action-btn" wire:click="selectPrescription({{ $rx['id'] }})" title="Preview">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @if(($selectedPrescriptionId ?? null) === $rx['id'])
                        <tr class="rx-meds-expand-row" wire:key="rx-meds-{{ $rx['id'] }}">
                            <td colspan="6">
                                <div class="cp-card">
                                    <div class="cp-card-header">
                                        <div class="cp-card-title">
                                            <i class="fas fa-capsules"></i> Medications
                                        </div>
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($rx['medications'] ?? [] as $med)
                                                <tr wire:key="rx-{{ $rx['id'] }}-med-{{ $loop->index }}">
                                                    <td><span class="cp-med-name">{{ $med['name'] }}</span></td>
                                                    <td>{{ $med['dosage'] }}</td>
                                                    <td>
                                                        <span class="cp-freq-badge">{{ $med['frequency'] }}</span>
                                                    </td>
                                                    <td>{{ $med['duration'] }}</td>
                                                    <td>{{ $med['when_to_take'] }}</td>
                                                    <td>{{ $med['quantity'] }}</td>
                                                    <td>{{ $med['special_instruction'] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" style="text-align:center; color:#9ca3af; padding:28px 20px;">
                                                        No medicines in this prescription.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; color:#9ca3af; padding:40px 20px;">
                            No prescriptions found for this patient.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($prescriptions->total() > 0)
            <div class="p-4">{{ $prescriptions->links() }}</div>
        @endif
    </div>

    <div class="pd-preview-card">
        <div class="pd-preview-header">
            <div class="pd-preview-title">Prescription Preview</div>
            <div class="pd-preview-header-actions">
                @if(!empty($prescriptionPreview['is_latest']))
                    <span class="pd-latest-badge">Latest</span>
                @endif
                <button type="button" wire:click="closePreview" title="Close" style="border:none;background:transparent;cursor:pointer;color:#6b7280;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="pd-preview-body">
            @if($prescriptionPreview)
                @php $firstDoc = $prescriptionPreview['documents'][0] ?? null; @endphp
                @if($firstDoc)
                    <div class="pd-preview-thumb">
                        @if(!empty($firstDoc['is_image']) && !empty($firstDoc['url']))
                            <img src="{{ $firstDoc['url'] }}" alt="{{ $firstDoc['name'] }}" style="width:100%;max-height:220px;object-fit:contain;border-radius:8px;">
                        @elseif(!empty($firstDoc['is_pdf']) && !empty($firstDoc['url']))
                            <iframe src="{{ $firstDoc['url'] }}" style="width:100%;height:220px;border:1px solid #e8ecf0;border-radius:8px;" title="PDF Preview"></iframe>
                        @else
                            <div style="text-align:center;padding:32px 20px;color:#9ca3af;">
                                <i class="fa-solid fa-prescription-bottle-medical" style="font-size:42px;margin-bottom:12px;"></i>
                                <div>Structured prescription preview below.</div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="pd-preview-thumb">
                        <div style="text-align:center;padding:32px 20px;color:#9ca3af;">
                            <i class="fa-solid fa-prescription-bottle-medical" style="font-size:42px;margin-bottom:12px;"></i>
                            <div>No prescription document attached.</div>
                        </div>
                    </div>
                @endif

                <div class="pd-meta-title">Metadata</div>
                <div class="pd-meta-grid">
                    <div>
                        <div class="pd-meta-label">Prescription ID</div>
                        <div class="pd-meta-value">{{ $prescriptionPreview['prescription_id'] }}</div>
                    </div>
                    <div>
                        <div class="pd-meta-label">Doctor</div>
                        <div class="pd-meta-value">{{ $prescriptionPreview['doctor_name'] }}</div>
                    </div>
                    <div>
                        <div class="pd-meta-label">Hospital</div>
                        <div class="pd-meta-value">{{ $prescriptionPreview['hospital_name'] }}</div>
                    </div>
                    <div>
                        <div class="pd-meta-label">Date</div>
                        <div class="pd-meta-value">{{ $prescriptionPreview['date'] }}</div>
                    </div>
                    <div>
                        <div class="pd-meta-label">Status</div>
                        <div class="pd-meta-value">{{ $prescriptionPreview['status'] }}</div>
                    </div>
                    <div>
                        <div class="pd-meta-label">Follow-up</div>
                        <div class="pd-meta-value">{{ $prescriptionPreview['follow_up_date'] }}</div>
                    </div>
                </div>

                @if(count($prescriptionPreview['lab_tests'] ?? []) > 0)
                    <div class="pd-rx-section">
                        <div class="pd-rx-section-title">Lab Tests</div>
                        <ul class="pd-rx-meds-list">
                            @foreach($prescriptionPreview['lab_tests'] as $test)
                                <li>{{ $test }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="pd-notes-title">Clinical Notes</div>
                <div class="pd-notes-text">{{ $prescriptionPreview['clinical_notes'] }}</div>

                @if(count($prescriptionPreview['documents'] ?? []) > 1)
                    <div class="pd-rx-section" style="margin-top:16px;">
                        <div class="pd-rx-section-title">Prescription Documents</div>
                        @foreach($prescriptionPreview['documents'] as $doc)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;font-size:13px;">
                                <span>{{ $doc['name'] }}</span>
                                <button type="button" class="pd-row-action-btn" wire:click="downloadDocument({{ $doc['id'] }})" title="Download">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div style="text-align:center;padding:48px 20px;color:#9ca3af;">
                    <i class="far fa-eye" style="font-size:36px;margin-bottom:12px;"></i>
                    <div>Select a prescription to preview.</div>
                </div>
            @endif
        </div>

        @if($prescriptionPreview && !empty($prescriptionPreview['documents'][0]['id']))
            <div class="pd-preview-footer">
                <button type="button" class="btn-pd-download" wire:click="downloadDocument({{ $prescriptionPreview['documents'][0]['id'] }})">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        @endif
    </div>
</div>
</div>
