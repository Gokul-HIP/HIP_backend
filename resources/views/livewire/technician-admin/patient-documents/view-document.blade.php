<div style="padding: 28px 32px;">
<link rel="stylesheet" href="{{ asset('assets/doctor-prescription.css') }}">
    
    {{-- ── Patient Summary Card ── --}}
    <div class="pd-patient-card">
        <div class="pd-patient-left">
            @if(!empty($patient['avatar_url']))
                <img src="{{ $patient['avatar_url'] }}" alt="{{ $patient['name'] }}" class="pd-patient-avatar">
            @else
                <div class="pd-patient-avatar-init">{{ $patient['initials'] ?? 'P' }}</div>
            @endif
            <div style="min-width:0;">
                <div class="pd-patient-name-row">
                    <span class="pd-patient-name">{{ $patient['name'] ?? '—' }}</span>
                    <span class="pd-active-pill">{{ $patient['status'] ?? 'Active Patient' }}</span>
                </div>
                <div class="pd-patient-meta">
                    <span><strong>UHID:</strong> {{ $patient['uhid'] ?? '—' }}</span>
                    <span><strong>Age/Gender:</strong> {{ $patient['age'] ?? '—' }} / {{ $patient['gender'] ?? '—' }}</span>
                    <span><strong>Last Visit:</strong> {{ $patient['last_visit'] ?? '—' }}</span>
                </div>
            </div>
        </div>
        <div class="pd-patient-actions">
            <button type="button" class="btn-pd-outline" wire:click="viewPatientProfile">View Patient Profile</button>
            <button type="button" class="btn-pd-outline" wire:click="openBookingHistory">Booking History</button>
        </div>
    </div>
    
    {{-- ── Filters Bar ── --}}
    <div class="pd-filters-bar">
        <div class="pd-filters-label"><i class="fas fa-sliders-h"></i> Filters:</div>
        <select class="pd-select" wire:model.live="documentTypeFilter">
            <option value="all">All Document Types</option>
            <option value="lab_report">Lab Report</option>
            <option value="scan_report">Scan Report</option>
            <option value="prescription">Prescription</option>
            <option value="discharge_summary">Discharge Summary</option>
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

    @if($showPatientProfilePanel && $profilePatient)
        <div class="pp-overlay" wire:click="closePatientProfile"></div>
        <aside class="pp-panel" role="dialog" aria-label="Patient Profile Quick View">
            <div class="pp-panel-header">
                <h2>Patient Profile Quick View</h2>
                <button type="button" class="pp-panel-close" wire:click="closePatientProfile" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="pp-panel-body">
                <div class="pp-patient-card">
                    <div class="pp-patient-top">
                        <div class="pp-patient-avatar">
                            @if(!empty($profilePatient['avatar_url']))
                                <img src="{{ $profilePatient['avatar_url'] }}" alt="{{ $profilePatient['name'] }}">
                            @else
                                {{ $profilePatient['initials'] }}
                            @endif
                        </div>
                        <div>
                            <div class="pp-patient-name">{{ ucfirst($profilePatient['name']) }}</div>
                            <div class="pp-patient-uhid">UHID: {{ $profilePatient['uhid'] }}</div>
                        </div>
                    </div>
                    <div class="pp-badges">
                        @if(filled($profilePatient['blood_group']))
                            <span class="pp-badge blood"><i class="fas fa-tint"></i> {{ $profilePatient['blood_group'] }}</span>
                        @endif
                        @if($profilePatient['age'])
                            <span class="pp-badge">{{ $profilePatient['age'] }} Years</span>
                        @endif
                        @if(filled($profilePatient['gender']))
                            <span class="pp-badge">{{ $profilePatient['gender'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="pp-section">
                    <div class="pp-section-title">Contact Details</div>
                    <div class="pp-contact-grid">
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Mobile</div>
                            <div class="pp-contact-value">{{ $profilePatient['mobile'] }}</div>
                        </div>
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Email</div>
                            <div class="pp-contact-value">{{ $profilePatient['email'] }}</div>
                        </div>
                        <div class="pp-contact-item full">
                            <div class="pp-contact-label">Address</div>
                            <div class="pp-contact-value">{{ $profilePatient['address'] ?: '—' }}</div>
                        </div>
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Emergency Contact</div>
                            <div class="pp-contact-value">{{ $profilePatient['emergency_contact'] }}</div>
                        </div>
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Emergency Mobile</div>
                            <div class="pp-contact-value">{{ $profilePatient['emergency_mobile'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="pp-section">
                    <div class="pp-section-title">Clinical Highlights</div>
                    <div class="pp-clinical-item">
                        <div class="pp-clinical-icon warn"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <div class="pp-clinical-label">Known Allergies</div>
                            <div class="pp-clinical-value danger">{{ $profilePatient['allergies'] ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="pp-clinical-item">
                        <div class="pp-clinical-icon med"><i class="fas fa-briefcase-medical"></i></div>
                        <div>
                            <div class="pp-clinical-label">Medical History</div>
                            <div class="pp-clinical-value">{{ $profilePatient['medical_history'] ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="pp-clinical-item">
                        <div class="pp-clinical-icon pill"><i class="fas fa-pills"></i></div>
                        <div>
                            <div class="pp-clinical-label">Current Medications</div>
                            <div class="pp-clinical-value">{{ $profilePatient['current_medications'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="pp-section">
                    <div class="pp-section-title">Diagnostic Booking Timeline</div>
                    <div class="pp-timeline">
                        @forelse($profilePatient['appointment_timeline'] ?? [] as $appointment)
                            <div class="pp-timeline-item {{ $appointment['class'] }}">
                                <div class="pp-timeline-label">{{ $appointment['label'] }}</div>
                                <div class="pp-timeline-line">{{ $appointment['line'] }}</div>
                            </div>
                        @empty
                            <div class="pp-timeline-line" style="color:#9ca3af; font-style:italic;">No booking history available.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="pp-panel-footer">
                <button type="button" class="pp-footer-btn outline" wire:click="openBookingHistory">Booking History</button>
                <a href="{{ route('technician.upload-report.create', ['booking_id' => $profilePatient['booking_id']]) }}" class="pp-footer-btn primary">
                    Upload Report
                </a>
            </div>
        </aside>
    @endif

    @if($showBookingHistoryModal && $historyPatient)
        <div class="pp-overlay" wire:click="closeBookingHistory"></div>
        <aside class="pp-panel" role="dialog" aria-label="Booking History">
            <div class="pp-panel-header">
                <h2>Diagnostic Booking History</h2>
                <button type="button" class="pp-panel-close" wire:click="closeBookingHistory"><i class="fas fa-times"></i></button>
            </div>
            <div class="pp-panel-body">
                <p class="text-sm text-gray-600 mb-4">{{ $historyPatient['name'] }} • {{ $historyPatient['uhid'] }}</p>
                <div class="pp-timeline">
                    @foreach($bookingHistory as $item)
                        <div class="pp-timeline-item">
                            <div class="pp-timeline-label">{{ $item->booking_date?->format('d M Y') ?: '—' }}</div>
                            <div class="pp-timeline-line">
                                APT-{{ str_pad((string) $item->id, 4, '0', STR_PAD_LEFT) }} •
                                {{ ucfirst((string) $item->status) }} •
                                {{ $item->diagnosticCenter?->name ?: 'Diagnostic Center' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    @endif
    
    {{-- ── Main Layout: Table + Preview ── --}}
    <div class="pd-main-layout">
    
        {{-- Documents Table --}}
        <div class="pd-table-card">
            <table class="pd-table">
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Type</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr class="{{ ($selectedDocumentId ?? null) === $doc['id'] ? 'active-row' : '' }}"
                            wire:key="doc-{{ $doc['id'] }}">
                            <td>
                                <div class="pd-doc-name-cell">
                                    <div class="pd-doc-icon {{ $doc['is_image'] ? 'icon-img' : 'icon-pdf' }}">
                                        <i class="{{ $doc['is_image'] ? 'far fa-image' : 'far fa-file-pdf' }}"></i>
                                    </div>
                                    <div>
                                        <div class="pd-doc-name">{{ $doc['name'] }}</div>
                                        <div class="pd-doc-size">{{ $doc['size'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $doc['type'] }}</td>
                            <td>{{ $doc['uploaded_by'] }}</td>
                            <td>{{ $doc['date'] }}</td>
                            <td>
                                <span class="pd-status-pill status-completed-doc">
                                    <span class="dot"></span> {{ $doc['status'] }}
                                </span>
                            </td>
                            <td>
                                <div class="pd-row-actions">
                                    <button type="button" class="pd-row-action-btn" wire:click="previewDocument({{ $doc['id'] }})" title="Preview">
                                        <i class="far fa-eye"></i>
                                    </button>
                                    <button type="button" class="pd-row-action-btn" wire:click="downloadDocument({{ $doc['id'] }})" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; color:#9ca3af; padding:40px 20px;">
                                No documents found for this patient.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($documents->total() > 0)
                <div class="p-4">{{ $documents->links() }}</div>
            @endif
        </div>
    
        {{-- Document Preview Panel --}}
        <div class="pd-preview-card">
            <div class="pd-preview-header">
                <div class="pd-preview-title">Document Preview</div>
                <div class="pd-preview-header-actions">
                    @if(!empty($previewDoc['url']))
                        <a href="{{ $previewDoc['url'] }}" target="_blank" rel="noopener" title="Open in new tab">
                            <i class="fas fa-up-right-from-square"></i>
                        </a>
                    @endif
                    <button type="button" wire:click="closePreview" title="Close" style="border:none;background:transparent;cursor:pointer;color:#6b7280;">
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
                            <iframe src="{{ $previewDoc['url'] }}" style="width:100%;height:280px;border:1px solid #e8ecf0;border-radius:8px;" title="PDF Preview"></iframe>
                        @else
                            <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                                <i class="far fa-file-alt" style="font-size:42px;margin-bottom:12px;"></i>
                                <div>Preview not available for this file type.</div>
                            </div>
                        @endif
                    </div>

                    <div class="pd-meta-title">Metadata</div>
                    <div class="pd-meta-grid">
                        <div>
                            <div class="pd-meta-label">Document ID</div>
                            <div class="pd-meta-value">{{ $previewDoc['document_id'] }}</div>
                        </div>
                        <div>
                            <div class="pd-meta-label">Document Type</div>
                            <div class="pd-meta-value">{{ $previewDoc['type'] }}</div>
                        </div>
                        <div>
                            <div class="pd-meta-label">Uploaded On</div>
                            <div class="pd-meta-value">{{ $previewDoc['date'] }}</div>
                        </div>
                        <div>
                            <div class="pd-meta-label">Uploaded By</div>
                            <div class="pd-meta-value">{{ $previewDoc['uploaded_by'] }}</div>
                        </div>
                    </div>

                    <div class="pd-notes-title">Notes</div>
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
    </div>