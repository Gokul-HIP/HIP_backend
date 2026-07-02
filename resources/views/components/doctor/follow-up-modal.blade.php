@if($showBookFollowUpModal ?? false)
<div class="bfu-overlay" wire:click.self="closeBookFollowUp">
    <div class="bfu-modal" role="dialog" aria-modal="true">

        {{-- ── Header (fixed, never scrolls) ── --}}
        <div class="bfu-header">
            <div>
                <h2 class="bfu-title">Book Follow-up Appointment</h2>
                <p class="bfu-subtitle">Schedule the patient's next consultation</p>
            </div>
            <button class="bfu-close" wire:click="closeBookFollowUp" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- ── Scrollable body ── --}}
        <div class="bfu-body">

            {{-- Patient Info --}}
            <div class="bfu-patient-card">
                @if(!empty($followUpPatient['avatar_url'] ?? null))
                    <img src="{{ $followUpPatient['avatar_url'] }}"
                         alt="{{ $followUpPatient['name'] ?? '' }}"
                         class="bfu-patient-avatar">
                @else
                    <div class="bfu-patient-avatar bfu-patient-avatar-init">
                        <i class="fas fa-user"></i>
                    </div>
                @endif

                <div class="bfu-patient-fields">
                    <div class="bfu-patient-col">
                        <div class="bfu-field-label">Patient Name</div>
                        <div class="bfu-field-value">{{ $followUpPatient['name'] ?? '—' }}</div>
                        <div class="bfu-field-sub">{{ $followUpPatient['age'] ?? '—' }} Yrs &bull; {{ $followUpPatient['gender'] ?? '—' }}</div>
                    </div>
                    <div class="bfu-patient-col">
                        <div class="bfu-field-label">UHID</div>
                        <div class="bfu-field-value">{{ $followUpPatient['uhid'] ?? '—' }}</div>
                        <div class="bfu-field-sub">Prev Visit: {{ $followUpPatient['prev_visit'] ?? '—' }}</div>
                    </div>
                    <div class="bfu-patient-col">
                        <div class="bfu-field-label">Contact</div>
                        <div class="bfu-field-value">{{ $followUpPatient['mobile'] ?? '—' }}</div>
                        <div class="bfu-field-sub">{{ $followUpPatient['primary_doctor'] ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="bfu-content-row">

                {{-- Left column: form --}}
                <div class="bfu-form-col">

                    <div class="bfu-form-row">
                        <div class="bfu-field-group">
                            <label class="bfu-field-label-top">Follow-up Type</label>
                            <select class="bfu-select" wire:model.live="followUpType">
                                <option value="in-clinic">In-Clinic Consultation</option>
                                <option value="online">Online Consultation</option>
                            </select>
                        </div>
                        <div class="bfu-field-group">
                            <label class="bfu-field-label-top">Hospital Branch</label>
                            <select class="bfu-select" wire:model.live="followUpBranch">
                                @forelse($followUpBranches ?? [] as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @empty
                                    <option value="">No branches available</option>
                                @endforelse
                            </select>
                            @error('followUpBranch') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="bfu-form-row">
                        <div class="bfu-field-group">
                            <label class="bfu-field-label-top">Preferred Date</label>
                            <div class="bfu-input-icon">
                                <input type="date" wire:model.live="followUpDate" class="bfu-input">
                                <i class="far fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="bfu-field-group">
                            <label class="bfu-field-label-top">Preferred Time</label>
                            <div class="bfu-input-icon">
                                <input type="time"
                                       wire:model.live="followUpTime"
                                       class="bfu-input"
                                       min="06:00"
                                       max="22:00"
                                       step="300">
                                <i class="far fa-clock"></i>
                            </div>
                            @error('followUpTime') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="bfu-field-group bfu-field-full">
                        <label class="bfu-field-label-top">Reason for Follow-up</label>
                        <select class="bfu-select" wire:model.live="followUpReason">
                            @foreach($followUpReasons ?? [] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('followUpReason') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="bfu-field-group bfu-field-full">
                        <label class="bfu-field-label-top">Clinical Notes</label>
                        <textarea class="bfu-textarea"
                                  wire:model.live="followUpNotes"
                                  placeholder="Add specific observation or instructions for the next visit..."></textarea>
                    </div>

                    <div class="bfu-field-group bfu-field-full">
                        <label class="bfu-field-label-top">Notification Reminder</label>
                        <label class="bfu-check">
                            <input type="checkbox" wire:model.live="sendNotificationReminder">
                            <span class="bfu-check-box"><i class="fas fa-check"></i></span>
                            Send appointment confirmation &amp; reminder to patient
                        </label>
                    </div>

                </div>

                {{-- Right column: booking summary --}}
                <div class="bfu-summary-col">
                    <div class="bfu-summary-card">
                        <div class="bfu-summary-header">Booking Summary</div>

                        <div class="bfu-summary-doctor">
                            <div class="bfu-summary-doctor-icon"><i class="fas fa-briefcase-medical"></i></div>
                            <div>
                                <div class="bfu-summary-doctor-name">{{ $followUpPatient['primary_doctor'] ?? 'Dr. —' }}</div>
                                <div class="bfu-summary-doctor-dept">{{ $followUpPatient['department'] ?? 'Department' }}</div>
                            </div>
                        </div>

                        <div class="bfu-summary-rows">
                            <div class="bfu-summary-row">
                                <span>Branch</span>
                                <strong>{{ $followUpBranchLabel ?? '—' }}</strong>
                            </div>
                            <div class="bfu-summary-row">
                                <span>Visit Type</span>
                                <span class="bfu-pill">{{ $followUpTypeLabel ?? 'In-Clinic' }}</span>
                            </div>
                            <div class="bfu-summary-row">
                                <span>Date</span>
                                <strong>{{ $followUpDateLabel ?? '—' }}</strong>
                            </div>
                            <div class="bfu-summary-row">
                                <span>Time Slot</span>
                                <strong class="bfu-time-highlight">{{ $followUpTimeLabel ?? '—' }}</strong>
                            </div>
                        </div>

                        <div class="bfu-summary-note">
                            Please ensure patient records are updated before the next visit.
                        </div>
                    </div>
                </div>

            </div>

        </div>

        {{-- ── Footer (fixed, never scrolls) ── --}}
        <div class="bfu-footer">
            <button type="button" class="bfu-btn-cancel" wire:click="closeBookFollowUp">
                Cancel
            </button>
            <button type="button"
                    class="bfu-btn-book"
                    wire:click="bookFollowUpAppointment"
                    wire:loading.attr="disabled">
                <i class="far fa-calendar-plus"></i>
                <span wire:loading.remove wire:target="bookFollowUpAppointment" style="color:#ffffff !important;">Book Follow-up</span>
                <span wire:loading wire:target="bookFollowUpAppointment" style="color:#ffffff !important;">Booking...</span>
            </button>
        </div>

    </div>
</div>

<style>
/* ── Overlay ── */
.bfu-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}

/* ── Modal ── */
.bfu-modal {
    width: 100%;
    max-width: 980px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.20);
    display: flex;
    flex-direction: column;
    height: 90vh;
    max-height: 90vh;
    overflow: hidden;
    flex-shrink: 0;
}

/* ── Header ── */
.bfu-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 22px 28px 18px;
    background: #fff;
    border-radius: 20px 20px 0 0;
    border-bottom: 1px solid #f0f4f8;
    flex-shrink: 0;
}
.bfu-title { font-size: 24px; font-weight: 800; color: var(--button-color); line-height: 1.2; margin-bottom: 4px; }
.bfu-subtitle { font-size: 13.5px; color: #6b7280; }
.bfu-close {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; color: #9ca3af;
    cursor: pointer; font-size: 17px; border-radius: 8px;
    transition: background 0.15s, color 0.15s; flex-shrink: 0;
}
.bfu-close:hover { background: #f4f6f9; color: #374151; }

/* ── Body ── */
.bfu-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 22px 28px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* ── Patient Card ── */
.bfu-patient-card {
    background: #eef0fb;
    border-radius: 14px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.bfu-patient-avatar {
    width: 56px; height: 56px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.bfu-patient-avatar-init {
    display: flex; align-items: center; justify-content: center;
    background: #fbdada;
    color: var(--button-color); font-size: 22px;
}
.bfu-patient-fields { display: flex; gap: 40px; flex-wrap: wrap; flex: 1; }
.bfu-patient-col { display: flex; flex-direction: column; }
.bfu-field-label {
    font-size: 10.5px; font-weight: 700; color: #6b7280;
    letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 4px;
}
.bfu-field-value { font-size: 17px; font-weight: 700; color: #1a1a2e; }
.bfu-field-sub { font-size: 13px; color: #6b7280; margin-top: 3px; }

/* ── Content layout ── */
.bfu-content-row {
    display: flex;
    gap: 22px;
    align-items: flex-start;
}
.bfu-form-col { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 18px; }
.bfu-summary-col { width: 290px; flex-shrink: 0; }

.bfu-form-row { display: flex; gap: 18px; }
.bfu-field-group { flex: 1; display: flex; flex-direction: column; gap: 7px; min-width: 0; }
.bfu-field-full { width: 100%; }
.bfu-field-label-top {
    font-size: 11.5px; font-weight: 700; color: #6b7280;
    letter-spacing: 0.05em; text-transform: uppercase;
}

.bfu-select, .bfu-input {
    height: 44px;
    border: 1.5px solid #e8ecf0;
    border-radius: 10px;
    padding: 0 14px;
    font-size: 14px;
    color: #1a1a2e;
    background: #fff;
    font-family: inherit;
    outline: none !important;
    width: 100%;
    transition: border-color 0.15s;
}
.bfu-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%239ca3af' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    appearance: none;
    cursor: pointer;
    padding-right: 32px;
}
.bfu-select:focus, .bfu-input:focus { border-color: var(--button-color); }

.bfu-input-icon { position: relative; }
.bfu-input-icon input { padding-right: 38px; }
.bfu-input-icon i {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: 14px; pointer-events: none;
}

.bfu-textarea {
    border: 1.5px solid #e8ecf0;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14px;
    color: #1a1a2e;
    background: #fff;
    font-family: inherit;
    outline: none !important;
    width: 100%;
    min-height: 100px;
    resize: vertical;
    transition: border-color 0.15s;
}
.bfu-textarea:focus { border-color: var(--button-color); }
.bfu-textarea::placeholder { color: #9ca3af; }

/* ── Checkboxes ── */
.bfu-checks-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px 24px;
}
.bfu-check {
    display: flex; align-items: center; gap: 10px;
    font-size: 14px; color: #374151; cursor: pointer; user-select: none;
}
.bfu-check input { display: none; }
.bfu-check-box {
    width: 22px; height: 22px;
    border: 1.5px solid #e8ecf0;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; flex-shrink: 0;
    transition: background 0.15s, border-color 0.15s;
}
.bfu-check-box i { font-size: 11px; color: #fff; opacity: 0; transition: opacity 0.1s; }
.bfu-check input:checked + .bfu-check-box {
    background: var(--button-color); border-color: var(--button-color);
}
.bfu-check input:checked + .bfu-check-box i { opacity: 1; }

/* ── Booking Summary ── */
.bfu-summary-card {
    border: 1.5px solid #fbdada;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}
.bfu-summary-header {
    background: #fdeaea;
    color: var(--button-color);
    font-size: 17px;
    font-weight: 800;
    padding: 14px 18px;
}
.bfu-summary-doctor {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 18px;
}
.bfu-summary-doctor-icon {
    width: 38px; height: 38px;
    border-radius: 9px;
    background: #eaf2fb;
    color: var(--button-color);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.bfu-summary-doctor-name { font-size: 14.5px; font-weight: 700; color: #1a1a2e; }
.bfu-summary-doctor-dept { font-size: 12.5px; color: #6b7280; margin-top: 2px; }

.bfu-summary-rows {
    padding: 4px 18px 14px;
    border-top: 1px solid #f5e3e3;
    display: flex;
    flex-direction: column;
}
.bfu-summary-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 0;
    border-bottom: 1px solid #f5f5f7;
    font-size: 13.5px; color: #6b7280;
}
.bfu-summary-row:last-child { border-bottom: none; }
.bfu-summary-row strong { color: #1a1a2e; font-weight: 700; font-size: 13.5px; }
.bfu-time-highlight { color: var(--button-color) !important; }
.bfu-pill {
    background: #eef0f3; color: #374151;
    font-size: 12px; font-weight: 600;
    padding: 3px 10px; border-radius: 16px;
}

.bfu-summary-note {
    margin: 0 18px 18px;
    background: #f4f6f9;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 12.5px;
    color: #6b7280;
    text-align: center;
    line-height: 1.5;
}

/* ── Footer ── */
.bfu-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 28px 20px;
    background: #f4f6f9;
    border-radius: 0 0 20px 20px;
    border-top: 1px solid #e8ecf0;
    flex-shrink: 0;
}
.bfu-btn-cancel {
    padding: 11px 24px;
    border: none;
    border-radius: 10px;
    background: transparent;
    font-size: 14.5px; font-weight: 600;
    color: #6b7280; cursor: pointer; font-family: inherit;
}
.bfu-btn-cancel:hover { color: #374151; }
.bfu-btn-book {
    display: flex; align-items: center; gap: 9px;
    padding: 11px 26px;
    border: none; border-radius: 10px;
    background: var(--button-color); font-size: 14.5px; font-weight: 700;
    color: #fff; cursor: pointer; font-family: inherit;
    transition: background 0.15s;
}
.bfu-btn-book:hover { background: var(--button-hover); }

/* ── Responsive ── */
@media (max-width: 760px) {
    .bfu-content-row { flex-direction: column; }
    .bfu-summary-col { width: 100%; }
    .bfu-form-row { flex-direction: column; }
    .bfu-checks-grid { grid-template-columns: 1fr; }
    .bfu-patient-fields { gap: 18px; }
    .bfu-modal { height: 96vh; max-height: 96vh; }
}
</style>
@endif