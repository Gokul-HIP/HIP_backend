<div>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    .cnp-wrap {
        font-family: 'Inter', -apple-system, sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* ═══════════════════════════════
       HEADER
    ═══════════════════════════════ */
    .cnp-header {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 20px 28px 0;
    }

    .cnp-header-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .cnp-title {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 4px;
        line-height: 1.2;
    }

    .cnp-subtitle {
        font-size: 13px;
        color: #94a3b8;
        margin: 0;
    }

    .cnp-add-member-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #0da2e7;
        color: #fff;
        font-size: 13.5px;
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.17s, box-shadow 0.17s, transform 0.12s;
        box-shadow: 0 4px 14px rgba(13,162,231,0.35);
        flex-shrink: 0;
    }
    .cnp-add-member-btn:hover {
        background: #0891cc;
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(13,162,231,0.5);
    }

    /* ── Step Tab Bar ── */
    .cnp-steps {
        display: flex;
        align-items: center;
        gap: 0;
        overflow-x: auto;
        padding-bottom: 0;
        scrollbar-width: none;
    }
    .cnp-steps::-webkit-scrollbar { display: none; }

    .cnp-step-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        font-size: 13px;
        font-weight: 500;
        color: #94a3b8;
        border-bottom: 2.5px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        transition: color 0.15s, border-color 0.15s;
        user-select: none;
        background: none;
        border-top: none;
        border-left: none;
        border-right: none;
        text-decoration: none;
    }
    .cnp-step-tab:hover { color: #475569; }

    .cnp-step-tab.active {
        color: #0da2e7;
        font-weight: 700;
        border-bottom-color: #0da2e7;
    }
    .cnp-step-tab.done {
        color: #10b981;
    }

    .cnp-step-num {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        background: #e2e8f0;
        color: #64748b;
        flex-shrink: 0;
        transition: background 0.15s, color 0.15s;
    }
    .cnp-step-tab.active .cnp-step-num {
        background: #0da2e7;
        color: #fff;
    }
    .cnp-step-tab.done .cnp-step-num {
        background: #10b981;
        color: #fff;
    }

    /* ═══════════════════════════════
       BODY
    ═══════════════════════════════ */
    .cnp-body {
        flex: 1;
        padding: 28px;
        display: flex;
        flex-direction: column;
        gap: 24px;
        background: #f8fafc;
    }

    /* ── Section Cards ── */
    .cnp-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    /* ── Search ── */
    .cnp-search-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 10px;
        display: block;
    }
    .cnp-search-box {
        display: flex;
        align-items: center;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        padding: 0 14px;
        background: #fff;
        width: 340px;
        max-width: 100%;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .cnp-search-box:focus-within {
        border-color: #0da2e7;
        box-shadow: 0 0 0 3px rgba(13,162,231,0.12);
    }
    .cnp-search-box input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 14px;
        color: #374151;
        padding: 10px 0;
        background: transparent;
        font-family: 'Inter', sans-serif;
    }
    .cnp-search-box i {
        color: #94a3b8;
        font-size: 14px;
    }

    /* ── Family Members Section ── */
    .cnp-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .cnp-section-title-left {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }
    .cnp-section-title-left i {
        color: #0da2e7;
        font-size: 16px;
    }
    .cnp-members-count {
        font-size: 12.5px;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ── Member Cards Grid ── */
    .cnp-members-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }

    .cnp-member-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s;
        position: relative;
    }
    .cnp-member-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 2px 10px rgba(13,162,231,0.1);
    }
    .cnp-member-card.selected {
        border-color: #0da2e7;
        box-shadow: 0 0 0 3px rgba(13,162,231,0.12);
    }

    /* Checkmark badge */
    .cnp-member-check {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        background: #0da2e7;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .cnp-member-card.selected .cnp-member-check { display: flex; }
    .cnp-member-check i { color: #fff; font-size: 10px; }

    .cnp-member-top {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .cnp-member-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
        flex-shrink: 0;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #64748b;
        font-size: 16px;
    }
    .cnp-member-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }

    .cnp-member-name {
        font-size: 14.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px;
    }
    .cnp-member-role {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }

    .cnp-member-meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 12px;
    }
    .cnp-member-meta span { display: flex; align-items: center; gap: 6px; }
    .cnp-member-meta i { color: #94a3b8; font-size: 11px; width: 12px; }

    .cnp-member-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }

    .cnp-plan-badge {
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 3px 9px;
        border-radius: 20px;
    }
    .cnp-plan-active    { background: #d1fae5; color: #065f46; }
    .cnp-plan-dependent { background: #e0f2fe; color: #0369a1; }

    .cnp-view-history {
        font-size: 12px;
        font-weight: 600;
        color: #0da2e7;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0;
        text-decoration: none;
    }
    .cnp-view-history:hover { text-decoration: underline; }

    /* ── Payment Type Section ── */
    .cnp-pay-type-desc {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 16px;
        line-height: 1.6;
    }
    .cnp-checkboxes {
        display: flex;
        gap: 28px;
        flex-wrap: wrap;
    }
    .cnp-checkbox-item {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        font-weight: 500;
        color: #1e293b;
        cursor: pointer;
        user-select: none;
    }
    .cnp-checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #0da2e7;
        cursor: pointer;
        border-radius: 4px;
    }

    /* ═══════════════════════════════
       FOOTER
    ═══════════════════════════════ */
    .cnp-footer {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        padding: 16px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        position: sticky;
        bottom: 0;
        z-index: 10;
    }

    .cnp-wrap.cnp-modal-open .cnp-footer {
        display: none;
    }

    .cnp-member-modal {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .cnp-cancel-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: none;
        border: none;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        padding: 8px 4px;
        font-family: 'Inter', sans-serif;
        transition: color 0.15s;
    }
    .cnp-cancel-btn:hover { color: #ef4444; }

    .cnp-footer-right { display: flex; align-items: center; gap: 10px; }

    .cnp-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        color: #374151;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        padding: 10px 18px;
        font-family: 'Inter', sans-serif;
        transition: background 0.15s, border-color 0.15s;
    }
    .cnp-back-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
    .cnp-back-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .cnp-next-btn {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        background: #0da2e7;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        padding: 11px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        transition: background 0.17s, box-shadow 0.17s, transform 0.12s;
        box-shadow: 0 4px 14px rgba(13,162,231,0.4);
        text-decoration: none;
    }
    .cnp-next-btn:hover {
        background: #0891cc;
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(13,162,231,0.5);
    }

    .cnp-save-btn {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        background: #10b981;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        padding: 11px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        transition: background 0.17s, box-shadow 0.17s;
        box-shadow: 0 4px 14px rgba(16,185,129,0.35);
    }
    .cnp-save-btn:hover { background: #059669; box-shadow: 0 6px 20px rgba(16,185,129,0.45); }

    @media (max-width: 640px) {
        .cnp-header { padding: 16px 16px 0; }
        .cnp-body   { padding: 16px; gap: 16px; }
        .cnp-footer { padding: 14px 16px; }
        .cnp-members-grid { grid-template-columns: 1fr; }
        .cnp-search-box { width: 100%; }
    }
    </style>

<div class="cnp-wrap">
    @php
        $steps = [
            1 => 'Select Person',
            2 => 'Procedures',
            3 => 'Diagnostics',
            4 => 'Pharmacy',
            5 => 'Review',
            6 => 'Finish',
        ];
        $currentStep = $step ?? 1;
    @endphp

    {{-- ═══════════════════ HEADER ═══════════════════ --}}
    <div class="cnp-header">

        {{-- Title row --}}
        <div class="cnp-header-top">
            <div>
                <h1 class="cnp-title">Create New Payment</h1>
                <p class="cnp-subtitle">Step {{ $currentStep }} of 6: {{ $steps[$currentStep] ?? 'Member &amp; Payment Type' }}</p>
            </div>
            <button type="button" class="cnp-add-member-btn" wire:click="openAddMemberModal">
                <i class="fas fa-user-plus"></i>
                Add New Family Member
            </button>
        </div>

        {{-- Step Tab Bar: all 6 steps always visible; click to go back only --}}
        <nav class="cnp-steps">
            @foreach($steps as $num => $label)
                <button
                    class="cnp-step-tab {{ $currentStep == $num ? 'active' : ($currentStep > $num ? 'done' : '') }}"
                    wire:click="{{ $currentStep > $num ? 'goToStep('.$num.')' : '' }}"
                    {{ $currentStep < $num ? 'disabled' : '' }}
                    type="button"
                >
                    <span class="cnp-step-num">
                        @if($currentStep > $num)
                            <i class="fas fa-check" style="font-size:9px;"></i>
                        @else
                            {{ $num }}
                        @endif
                    </span>
                    {{ $label }}
                </button>
            @endforeach
        </nav>

    </div>{{-- /cnp-header --}}


    {{-- ═══════════════════ BODY ═══════════════════ --}}
    <div class="cnp-body">

        @if(($step ?? 1) === 1)

            {{-- ── Search by Phone ── --}}
            <div class="cnp-card">
                <label class="cnp-search-label">Search by Phone Number</label>
                <div class="cnp-search-box">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="phoneSearch"
                        placeholder="9876543210"
                        value="{{ $phoneSearch ?? '' }}"
                    >
                    <i class="fas fa-search"></i>
                </div>
            </div>

            {{-- ── Linked Family Members ── --}}
            <div>
                <div class="cnp-section-title">
                    <div class="cnp-section-title-left">
                        <i class="fas fa-users"></i>
                        Linked Family Members
                    </div>
                    <span class="cnp-members-count">
                        {{ count($familyMembers ?? []) ?: 0 }} members found
                    </span>
                </div>

                <div class="cnp-members-grid">

                    @if($member)
                        <div
                            class="cnp-member-card {{ ($selectedMemberId ?? null) == $member->id ? 'selected' : '' }}"
                            wire:click="selectMember('{{ $member->id }}')">
                            <div class="cnp-member-check"><i class="fas fa-check"></i></div>

                            <div class="cnp-member-top">
                                <div class="cnp-member-avatar">
                                    @if(!empty($member->image))
                                        <img src="{{ asset('storage/users/' . $member->image) }}" alt="{{ $member->first_name }} {{ $member->last_name }}">
                                    @else
                                        {{ strtoupper(substr($member->first_name,0,1)) }} {{ strtoupper(substr($member->last_name,0,1)) }}
                                    @endif
                                </div>
                                <div>
                                    <p class="cnp-member-name">{{ ucfirst($member->first_name) }} {{ ucfirst($member->last_name) }}</p>
                                    <p class="cnp-member-role">{{ $member->is_primary ? 'Primary' : 'Dependent' }}</p>
                                </div>
                            </div>

                            <div class="cnp-member-meta">
                                <span><i class="fas fa-birthday-cake"></i> {{ date('d M Y', strtotime($member->dob)) }}</span>
                                <span><i class="fas fa-id-badge"></i> ID: {{ $this->personDisplayId($member) }}</span>
                            </div>

                            <div class="cnp-member-footer">
                                <span class="cnp-plan-badge {{ $member->is_primary ? 'cnp-plan-active' : 'cnp-plan-dependent' }}">
                                    {{ $member->is_primary ? 'Primary' : 'Dependent' }}
                                </span>
                                <button class="cnp-view-history" wire:click.stop="viewHistory('{{ $member->id }}')">
                                    View History
                                </button>
                            </div>
                        </div>
                    @endif

                    @forelse($familyMembers ?? [] as $familyMember)
                    <div
                        class="cnp-member-card {{ ($selectedMemberId ?? null) == $familyMember->id ? 'selected' : '' }}"
                        wire:click="selectMember('{{ $familyMember->id }}')">
                        
                        <div class="cnp-member-check"><i class="fas fa-check"></i></div>

                        <div class="cnp-member-top">
                            <div class="cnp-member-avatar">
                                @if(!empty($familyMember->image))
                                    <img src="{{ asset('storage/users/' . $familyMember->image) }}" alt="{{ $familyMember->first_name }} {{ $familyMember->last_name }}">
                                @else
                                    {{ strtoupper(substr($familyMember->first_name,0,1)) }} {{ strtoupper(substr($familyMember->last_name,0,1)) }}
                                @endif
                            </div>
                            <div>
                                <p class="cnp-member-name">{{ ucfirst($familyMember->first_name) }} {{ ucfirst($familyMember->last_name) }}</p>
                                <p class="cnp-member-role">{{ $familyMember->is_primary ? 'Primary' : 'Dependent' }}</p>
                            </div>
                        </div>

                        <div class="cnp-member-meta">
                            <span><i class="fas fa-birthday-cake"></i> {{ date('d M Y', strtotime($familyMember->dob)) }}</span>
                            <span><i class="fas fa-id-badge"></i> ID: {{ $this->personDisplayId($familyMember) }}</span>
                        </div>

                        <div class="cnp-member-footer">
                            <span class="cnp-plan-badge {{ $familyMember->is_primary ? 'cnp-plan-active' : 'cnp-plan-dependent' }}">
                                {{ $familyMember->is_primary ? 'Primary' : 'Dependent' }}
                            </span>
                            <button class="cnp-view-history" wire:click.stop="viewHistory('{{ $familyMember->id }}')">
                                View History
                            </button>
                        </div>
                    </div>

                    @empty
                        <div class="cnp-member-card text-center text-sm text-gray-500 py-4 px-6 rounded-lg border border-gray-200
                        justify-center items-center">No family members found.</div>
                    @endforelse
                </div>
            </div>

            {{-- ── Payment Type ── --}}
            <div class="cnp-card">
                <div class="cnp-section-title" style="margin-bottom:10px;">
                    <div class="cnp-section-title-left">
                        <i class="fas fa-credit-card"></i>
                        Payment Type
                    </div>
                </div>
                <p class="cnp-pay-type-desc">
                    Select the categories to include in this payment session. All categories are selected by default.
                </p>
                <div class="cnp-checkboxes">
                    <label class="cnp-checkbox-item {{ !$canSelectProcedures ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <input type="checkbox"
                            @if($canSelectProcedures) wire:model.live="includesProcedures" @else disabled @endif
                            @checked($includesProcedures)> Procedures
                    </label>
                    <label class="cnp-checkbox-item {{ !$canSelectDiagnostics ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <input type="checkbox"
                            @if($canSelectDiagnostics) wire:model.live="includesDiagnostics" @else disabled @endif
                            @checked($includesDiagnostics)> Diagnostics
                    </label>
                    <label class="cnp-checkbox-item {{ !$canSelectPharmacy ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <input type="checkbox"
                            @if($canSelectPharmacy) wire:model.live="includesPharmacy" @else disabled @endif
                            @checked($includesPharmacy)> Pharmacy
                    </label>
                </div>
            </div>

        @elseif(($step ?? 1) === 2)
            @include('livewire.pharmacist-admin.payments.steps.procedures')

        @elseif(($step ?? 1) === 3)
            @include('livewire.pharmacist-admin.payments.steps.lab')

        @elseif(($step ?? 1) === 4)
            @include('livewire.pharmacist-admin.payments.steps.pharmacy')

        @elseif(($step ?? 1) === 5)
            @include('livewire.pharmacist-admin.payments.steps.review')

        @elseif(($step ?? 1) === 6)
            @include('livewire.pharmacist-admin.payments.steps.finish')
        @endif

    </div>{{-- /cnp-body --}}


    {{-- ═══════════════════ FOOTER ═══════════════════ --}}
    <div class="cnp-footer">

        {{-- Cancel --}}
        <a href="{{ route('pharmacist.payments.index') }}" class="cnp-cancel-btn">
            <i class="fas fa-times"></i>
            Cancel
        </a>

        {{-- Right: Back + Next/Save --}}
        <div class="cnp-footer-right">

            {{-- Back --}}
            <button
                class="cnp-back-btn"
                wire:click="previousStep"
                {{ ($currentStep ?? 1) <= 1 ? 'disabled' : '' }}
            >
                <i class="fas fa-arrow-left"></i>
                Back
            </button>

            {{-- Next / Save --}}
            @if(($currentStep ?? 1) < 5)
                <button class="cnp-next-btn" wire:click="nextStep">
                    Next Step: {{ $steps[$nextStepNumber ?? 2] ?? 'Continue' }}
                    <i class="fas fa-arrow-right"></i>
                </button>
            @elseif(($currentStep ?? 1) === 5)
                <button class="cnp-save-btn" wire:click="savePayment">
                    <i class="fas fa-check"></i>
                    Confirm &amp; Save Payment
                </button>
            @else
                <a href="{{ route('pharmacist.payments.create') }}" class="cnp-next-btn">
                    Create New Payment
                    <i class="fas fa-plus"></i>
                </a>
            @endif

        </div>

    </div>{{-- /cnp-footer --}}

    
    @if($showAddMemberModal)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center p-4" wire:key="add-member-modal">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto z-10">
                <div class="px-6 pt-6 pb-4 border-b border-slate-100">
                    <h2 class="text-lg font-bold text-slate-900">Add New Family Member</h2>
                    <p class="text-xs text-slate-400 mt-1">Register a new profile to link with this transaction.</p>
                    <button type="button"
                        wire:click="closeModal"
                        class="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form wire:submit="addFamilyMember">
                    <div class="px-6 py-5 flex flex-col gap-5">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-700">First Name</label>
                            <div class="flex items-center gap-2.5 border border-slate-200 rounded-lg px-3 focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100 transition bg-white">
                                <i class="fas fa-user text-slate-300 text-xs flex-shrink-0"></i>
                                <input type="text" placeholder="Enter first name"
                                    class="flex-1 border-none outline-none text-sm text-slate-800 py-2.5 bg-transparent placeholder:text-slate-300 min-w-0"
                                    wire:model="first_name">
                            </div>
                            @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-700">Last Name</label>
                            <div class="flex items-center gap-2.5 border border-slate-200 rounded-lg px-3 focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100 transition bg-white">
                                <i class="fas fa-user text-slate-300 text-xs flex-shrink-0"></i>
                                <input type="text" placeholder="Enter last name"
                                    class="flex-1 border-none outline-none text-sm text-slate-800 py-2.5 bg-transparent placeholder:text-slate-300 min-w-0"
                                    wire:model="last_name">
                            </div>
                            @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-slate-700">Date of Birth</label>
                                <div class="flex items-center gap-2.5 border border-slate-200 rounded-lg px-3 focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100 transition bg-white">
                                    <i class="fas fa-calendar text-slate-300 text-xs flex-shrink-0"></i>
                                    <input type="date"
                                        class="flex-1 border-none outline-none text-sm text-slate-800 py-2.5 bg-transparent min-w-0 cursor-pointer"
                                        wire:model="dob">
                                </div>
                                @error('dob') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-slate-700">
                                    Phone Number
                                    <span class="font-normal text-slate-400 ml-1">Optional</span>
                                </label>
                                <div class="flex items-center gap-2.5 border border-slate-200 rounded-lg px-3 focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100 transition bg-white">
                                    <i class="fas fa-phone text-slate-300 text-xs flex-shrink-0"></i>
                                    <input type="tel" placeholder="9876543210" maxlength="10"
                                        class="flex-1 border-none outline-none text-sm text-slate-800 py-2.5 bg-transparent placeholder:text-slate-300 min-w-0"
                                        wire:model="mobile">
                                </div>
                                @error('mobile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-semibold text-slate-700">Gender</label>
                            <div class="flex items-center gap-6">
                                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                                    <input type="radio" name="gender" value="male" class="accent-sky-500 w-4 h-4 cursor-pointer" wire:model="gender">
                                    Male
                                </label>
                                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                                    <input type="radio" name="gender" value="female" class="accent-sky-500 w-4 h-4 cursor-pointer" wire:model="gender">
                                    Female
                                </label>
                                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                                    <input type="radio" name="gender" value="other" class="accent-sky-500 w-4 h-4 cursor-pointer" wire:model="gender">
                                    Other
                                </label>
                            </div>
                            @error('gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="px-6 pb-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="closeModal"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg shadow-md shadow-sky-200 hover:shadow-sky-300 transition-all hover:-translate-y-px">
                            <i class="fas fa-user-plus text-xs"></i>
                            Create Profile &amp; Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
</div>
