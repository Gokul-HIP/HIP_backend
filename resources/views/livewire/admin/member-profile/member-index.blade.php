<div class="sp-page">
<style>
.sp-page{display:flex;flex-direction:column;gap:16px;color:var(--text-color);font-family:inherit}
.sp-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.sp-title{margin:0;font-size:24px;font-weight:700;color:var(--text-color)}
.sp-subtitle{margin:4px 0 0;font-size:14px;color:var(--muted-color)}
.sp-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 16px;border-radius:10px;border:1px solid transparent;font:inherit;font-size:14px;font-weight:700;cursor:pointer}
.sp-btn-primary{background:var(--button-color);color:var(--card-bg);box-shadow:var(--shadow-card)}
.sp-btn-primary:hover{background:var(--button-hover)}
.sp-btn-primary:disabled{opacity:.5;cursor:not-allowed}
.sp-btn-ghost{background:var(--card-bg);border-color:var(--border-color);color:var(--text-color)}
.sp-btn-ghost:hover{background:var(--page-bg)}
.sp-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.sp-kpi{background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card);box-shadow:var(--shadow-card);padding:16px}
.sp-kpi-label{margin:0;font-size:12px;color:var(--muted-color)}
.sp-kpi-value{margin:6px 0 0;font-size:32px;font-weight:700;color:var(--text-color)}
.sp-filters{display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:12px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card)}
.sp-search{position:relative;flex:1 1 260px;min-width:0}
.sp-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-color);pointer-events:none}
.sp-control{box-sizing:border-box;width:100%;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;outline:none}
.sp-search .sp-control{padding-left:36px}
.sp-control:focus{border-color:var(--primary-color);box-shadow:0 0 0 3px var(--primary-hover)}
.sp-status-filter{flex:0 1 180px;min-width:140px}
.sp-table-card{overflow:visible;background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card);box-shadow:var(--shadow-card)}
.sp-table-wrap{overflow-x:auto}
.sp-table{width:100%;min-width:960px;border-collapse:separate;border-spacing:0;font-size:14px}
.sp-table thead{background:var(--primary-hover)}
.sp-table th{height:44px;padding:0 14px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted-color);white-space:nowrap}
.sp-table td{height:64px;padding:10px 14px;border-top:1px solid var(--border-color);vertical-align:middle}
.sp-row{background:var(--card-bg)}
.sp-row:hover{background:var(--page-bg)}
.sp-name{font-weight:600}
.sp-muted{color:var(--muted-color)}
.sp-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:var(--radius-pill);font-size:12px;font-weight:600;line-height:1.2;white-space:nowrap}
.sp-pill.is-active{background:var(--success-soft);color:var(--success-color)}
.sp-pill.is-inactive{background:var(--danger-soft);color:var(--danger-color)}
.sp-dot{width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.sp-actions{text-align:right;white-space:nowrap}
.sp-icon-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border-color);background:transparent;color:var(--text-color);cursor:pointer;font:inherit}
.sp-icon-btn:hover{background:var(--primary-hover)}
.sp-empty{padding:36px 16px;text-align:center}
.sp-empty-title{font-size:15px;font-weight:700}
.sp-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid var(--border-color);color:var(--muted-color);font-size:13px}
.sp-pagination{display:flex}
.sp-pagination-list{display:flex;align-items:center;gap:6px;margin:0;padding:0;list-style:none}
.sp-page-button{box-sizing:border-box;display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:13px;font-weight:600;cursor:pointer}
.sp-page-button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.sp-page-button:hover{background:var(--primary-hover)}
.sp-page-button.is-active{border-color:var(--button-color);background:var(--button-color);color:var(--card-bg)}
.sp-page-button.is-disabled{color:var(--muted-color);cursor:not-allowed;opacity:.55}
.sp-page-gap{display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;color:var(--muted-color)}
.sp-menu{position:fixed;z-index:10050;min-width:220px;padding:6px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:var(--shadow-card);font-family:inherit}
.sp-menu button{all:unset;box-sizing:border-box;display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border-radius:8px;font-family:inherit;font-size:13px;font-weight:500;line-height:1.2;color:var(--text-color);cursor:pointer}
.sp-menu button:hover,.sp-menu button:focus-visible{background:var(--primary-hover);color:var(--primary-color)}
.sp-menu button svg{width:16px;height:16px;flex:none;stroke:currentColor;fill:none;stroke-width:1.8}
.sp-menu button.is-disabled{color:var(--muted-color);cursor:not-allowed;opacity:.7}
.sp-menu button.is-disabled:hover{background:transparent;color:var(--muted-color)}
.sp-menu-note{margin-left:auto;font-size:11px;color:var(--muted-color)}
.sp-backdrop{position:fixed;inset:0;z-index:9999;background:color-mix(in srgb, var(--text-color) 45%, transparent)}
.sp-layer{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:24px}
.sp-modal{width:100%;max-width:720px;max-height:calc(100vh - 48px);display:flex;flex-direction:column;background:var(--card-bg);border-radius:var(--radius-card);box-shadow:var(--shadow-card);overflow:hidden}
.sp-modal-head,.sp-modal-foot{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border-color)}
.sp-modal-foot{justify-content:flex-end;border-bottom:0;border-top:1px solid var(--border-color)}
.sp-modal-title{margin:0;font-size:18px;font-weight:700}
.sp-modal-sub{margin:4px 0 0;font-size:13px;color:var(--muted-color)}
.sp-x{width:34px;height:34px;border:0;border-radius:10px;background:transparent;color:var(--muted-color);cursor:pointer;font-size:20px}
.sp-x:hover{background:var(--primary-hover);color:var(--primary-color)}
.sp-modal-body{padding:16px 20px;overflow-y:auto;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.sp-span2{grid-column:1 / -1}
.sp-field{display:flex;flex-direction:column;gap:6px;min-width:0;position:relative}
.sp-label{font-size:12.5px;font-weight:600}
.sp-opt{font-weight:500;color:var(--muted-color)}
.sp-textarea{height:auto;min-height:76px;padding:10px 12px;resize:vertical}
.sp-error{font-size:12px;color:var(--danger-color)}
.sp-seg{display:flex;gap:6px}
.sp-seg label{flex:1}
.sp-seg input{position:absolute;opacity:0}
.sp-seg span{display:flex;align-items:center;justify-content:center;height:42px;border:1px solid var(--border-color);border-radius:10px;font-size:13.5px;font-weight:600;color:var(--muted-color);background:var(--card-bg)}
.sp-seg input:checked + span{background:var(--primary-hover);border-color:var(--primary-color);color:var(--primary-color)}
.sp-mrn-row{display:flex;gap:8px}
.sp-mrn-row .sp-control{flex:1;font-weight:700;letter-spacing:.04em}
.sp-status-ok{color:var(--success-color);font-size:12.5px;font-weight:600}
.sp-status-bad{color:var(--danger-color);font-size:12.5px;font-weight:600}
.sp-combo{position:absolute;top:100%;left:0;right:0;z-index:10002;margin-top:4px;max-height:220px;overflow-y:auto;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:var(--shadow-card)}
.sp-combo button{display:flex;justify-content:space-between;gap:12px;width:100%;padding:10px 12px;border:0;background:transparent;text-align:left;cursor:pointer;font:inherit}
.sp-combo button:hover{background:var(--primary-hover)}
.sp-chip{display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:42px;padding:0 8px 0 12px;border:1px solid var(--primary-color);border-radius:10px;background:var(--primary-hover);color:var(--primary-color);font-weight:600}
.sp-chip button{border:0;background:transparent;color:inherit;cursor:pointer;font:inherit}
.sp-note{font-size:13px;color:var(--muted-color)}
@media (max-width:768px){
    .sp-kpis{grid-template-columns:1fr}
    .sp-header .sp-btn{width:100%}
    .sp-status-filter,.sp-search{flex:1 1 100%}
    .sp-table{min-width:0}
    .sp-table thead{display:none}
    .sp-table,.sp-table tbody,.sp-table tr,.sp-table td{display:block;width:100%}
    .sp-table tr{margin:12px;border:1px solid var(--border-color);border-radius:var(--radius-card);overflow:hidden}
    .sp-table td{display:flex;justify-content:space-between;gap:12px;height:auto;border-top:1px solid var(--border-color)}
    .sp-table td:first-child{border-top:0}
    .sp-table td::before{content:attr(data-label);color:var(--muted-color);font-size:12px;font-weight:700}
    .sp-actions{text-align:left}
    .sp-footer{flex-direction:column}
    .sp-modal-body{grid-template-columns:1fr}
    .sp-layer{align-items:flex-end;padding:0}
    .sp-modal{max-height:92vh;border-radius:var(--radius-card) var(--radius-card) 0 0}
}
</style>

    <header class="sp-header">
        <div>
            <h1 class="sp-title">Manage Patients</h1>
            <p class="sp-subtitle">App patients. Accounts with a password are not listed here.</p>
        </div>
        <button type="button" class="sp-btn sp-btn-primary" wire:click="openAddModal">Add new patient</button>
    </header>

    <section class="sp-kpis" aria-label="Patient totals">
        <article class="sp-kpi">
            <p class="sp-kpi-label">Total Patients</p>
            <p class="sp-kpi-value">{{ $kpiTotal }}</p>
        </article>
        <article class="sp-kpi">
            <p class="sp-kpi-label">Active</p>
            <p class="sp-kpi-value">{{ $kpiActive }}</p>
        </article>
        <article class="sp-kpi">
            <p class="sp-kpi-label">Inactive</p>
            <p class="sp-kpi-value">{{ $kpiInactive }}</p>
        </article>
    </section>

    <div class="sp-filters">
        <label class="sp-search">
            <i class="sp-search-icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" class="sp-control" wire:model.live.debounce.300ms="search" placeholder="Search name, mobile, email, HIP ID..." aria-label="Search patients">
        </label>
        <select class="sp-control sp-status-filter" wire:model.live="statusFilter" aria-label="Filter by status">
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button type="button" class="sp-btn sp-btn-ghost" wire:click="clearFilters">Reset</button>
    </div>

    <div class="sp-table-card">
        <div class="sp-table-wrap">
            <table class="sp-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>HIP ID</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>MRN</th>
                        <th>Hospital</th>
                        <th>Status</th>
                        <th class="sp-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        @php
                            $person = $personMap->get($patient->id);
                            $patientName = trim(($patient->first_name ?? '').' '.($patient->last_name ?? ''));
                            $patientName = $patientName !== '' ? $patientName : 'Unnamed patient';
                            $isActive = (bool) ($patient->is_active ?? true);
                        @endphp
                        <tr class="sp-row" wire:key="sp-patient-{{ $patient->id }}">
                            <td class="sp-name" data-label="Patient">{{ $patientName }}</td>
                            <td data-label="HIP ID">{{ $patient->hip_id ?: '—' }}</td>
                            <td data-label="Mobile">{{ $patient->mobile_num ?: '—' }}</td>
                            <td data-label="Email">{{ $patient->email ?: '—' }}</td>
                            <td data-label="MRN">{{ $person->mrn ?? '—' }}</td>
                            <td data-label="Hospital">{{ $person ? ($hospitalNames[$person->hospital_id] ?? '—') : '—' }}</td>
                            <td data-label="Status">
                                @if($isActive)
                                    <span class="sp-pill is-active"><span class="sp-dot" aria-hidden="true"></span>Active</span>
                                @else
                                    <span class="sp-pill is-inactive"><span class="sp-dot" aria-hidden="true"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="sp-actions" data-label="Actions">
                                <button type="button" class="sp-icon-btn" aria-haspopup="menu" aria-label="Actions for {{ $patientName }}" onclick="window.__spOpenMenu(this, {{ \Illuminate\Support\Js::from($patient->id) }}, {{ $isActive ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from(route('admin.member-profile.view', ['user' => $patient->id])) }})">⋯</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="sp-empty">
                                <div class="sp-empty-title">No patients found</div>
                                <p class="sp-muted">Try changing the filters, or add a new patient.</p>
                                <button type="button" class="sp-btn sp-btn-ghost" wire:click="clearFilters" style="margin-top:12px;">Reset</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="sp-footer">
            <span>Showing {{ $patients->firstItem() ?? 0 }}–{{ $patients->lastItem() ?? 0 }} of {{ $patients->total() }} patients</span>
            @if ($patients->hasPages())
                <nav class="sp-pagination" aria-label="Pagination Navigation">
                    <ul class="sp-pagination-list">
                        <li>
                            <button type="button" class="sp-page-button {{ $patients->onFirstPage() ? 'is-disabled' : '' }}" wire:click="previousPage" @disabled($patients->onFirstPage()) aria-label="Previous page">
                                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12.5 4.5-5 5.5 5 5.5" /></svg>
                            </button>
                        </li>
                        @foreach (range(1, $patients->lastPage()) as $page)
                            <li>
                                <button type="button" class="sp-page-button {{ $page == $patients->currentPage() ? 'is-active' : '' }}" wire:click="gotoPage({{ $page }})" @if($page == $patients->currentPage()) disabled aria-current="page" @endif>{{ $page }}</button>
                            </li>
                        @endforeach
                        <li>
                            <button type="button" class="sp-page-button {{ $patients->hasMorePages() ? '' : 'is-disabled' }}" wire:click="nextPage" @disabled(! $patients->hasMorePages()) aria-label="Next page">
                                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 4.5 5 5.5-5 5.5" /></svg>
                            </button>
                        </li>
                    </ul>
                </nav>
            @endif
        </footer>
    </div>

    @if($showFormModal)
        <template x-teleport="body">
            <div>
                <div class="sp-backdrop" wire:click="$set('showFormModal', false)"></div>
                <div class="sp-layer">
                    <div class="sp-modal" role="dialog" aria-modal="true" aria-labelledby="sp-form-title">
                        <div class="sp-modal-head">
                            <div>
                                <h2 id="sp-form-title" class="sp-modal-title">{{ $editingUserId ? 'Edit patient' : 'Add new patient' }}</h2>
                                <p class="sp-modal-sub">{{ $editingUserId ? 'Update this patient' : 'Create an app patient or a dependent' }}</p>
                            </div>
                            <button type="button" class="sp-x" wire:click="$set('showFormModal', false)" aria-label="Close">&times;</button>
                        </div>
                        <div class="sp-modal-body">
                            <div class="sp-field">
                                <label class="sp-label">First name <span class="sp-opt">*</span></label>
                                <input class="sp-control" wire:model="first_name" autocomplete="off">
                                @error('first_name') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Last name <span class="sp-opt">*</span></label>
                                <input class="sp-control" wire:model="last_name" autocomplete="off">
                                @error('last_name') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Mobile <span class="sp-opt">*</span></label>
                                <input class="sp-control" wire:model="mobile" inputmode="numeric" maxlength="10" autocomplete="off">
                                @error('mobile') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Date of birth <span class="sp-opt">*</span></label>
                                <input class="sp-control" type="date" wire:model="dob" autocomplete="off">
                                @error('dob') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Gender <span class="sp-opt">*</span></label>
                                <div class="sp-seg">
                                    <label><input type="radio" name="sp_gender" wire:model="gender" value="Male"><span>Male</span></label>
                                    <label><input type="radio" name="sp_gender" wire:model="gender" value="Female"><span>Female</span></label>
                                    <label><input type="radio" name="sp_gender" wire:model="gender" value="Other"><span>Other</span></label>
                                </div>
                                @error('gender') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Blood group <span class="sp-opt">*</span></label>
                                <select class="sp-control" wire:model="blood_group">
                                    <option value="">Select</option>
                                    @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $group)
                                        <option value="{{ $group }}">{{ $group }}</option>
                                    @endforeach
                                </select>
                                @error('blood_group') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Hospital <span class="sp-opt">*</span></label>
                                <select class="sp-control" wire:model="hospital_id">
                                    <option value="">Select</option>
                                    @foreach($hospitals as $hospital)
                                        <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
                                    @endforeach
                                </select>
                                @error('hospital_id') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Email <span class="sp-opt">(optional)</span></label>
                                <input class="sp-control" type="email" wire:model="email" autocomplete="off">
                                @error('email') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Emergency contact name <span class="sp-opt">(optional)</span></label>
                                <input class="sp-control" wire:model="emergency_contact_name" autocomplete="off">
                                @error('emergency_contact_name') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field">
                                <label class="sp-label">Emergency contact number <span class="sp-opt">(optional)</span></label>
                                <input class="sp-control" wire:model="emergency_contact_number" autocomplete="off">
                                @error('emergency_contact_number') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field sp-span2">
                                <label class="sp-label">Address <span class="sp-opt">(optional)</span></label>
                                <textarea class="sp-control sp-textarea" wire:model="address" autocomplete="off"></textarea>
                                @error('address') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="sp-field sp-span2">
                                <label class="sp-label">Relationship <span class="sp-opt">*</span></label>
                                <select class="sp-control" wire:model.live="relationship">
                                    <option>Self</option>
                                    <option>Father</option>
                                    <option>Mother</option>
                                    <option>Spouse</option>
                                    <option>Child</option>
                                    <option>Other</option>
                                </select>
                                @error('relationship') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                            @if($relationship === 'Other')
                                <div class="sp-field sp-span2">
                                    <label class="sp-label">Specify <span class="sp-opt">*</span></label>
                                    <input class="sp-control" wire:model.live="relationship_other" autocomplete="off">
                                    @error('relationship_other') <div class="sp-error">{{ $message }}</div> @enderror
                                </div>
                            @endif
                            @if($relationship !== 'Self')
                                <div class="sp-field sp-span2">
                                    <label class="sp-label">Primary account <span class="sp-opt">*</span></label>
                                    @if($primaryLabel)
                                        <div class="sp-chip">{{ $primaryLabel }} <button type="button" wire:click="clearPrimary" aria-label="Clear primary account">&times;</button></div>
                                    @else
                                        <input class="sp-control" wire:model.live.debounce.300ms="primarySearch" placeholder="Search by name or mobile" autocomplete="off">
                                        @if(strlen(trim($primarySearch)) >= 2)
                                            <div class="sp-combo">
                                                @forelse($primaryResults as $result)
                                                    <button type="button" wire:click="selectPrimary('{{ $result['id'] }}')" wire:key="sp-primary-{{ $result['id'] }}">
                                                        <span>{{ $result['name'] }} — {{ $result['mobile'] ?: '—' }}</span>
                                                    </button>
                                                @empty
                                                    <div class="sp-note" style="padding:12px;">No matching primary accounts</div>
                                                @endforelse
                                            </div>
                                        @endif
                                    @endif
                                    @error('primary_id') <div class="sp-error">{{ $message }}</div> @enderror
                                </div>
                            @endif
                            <div class="sp-field sp-span2">
                                <label class="sp-label">MRN <span class="sp-opt">*</span></label>
                                <div class="sp-mrn-row">
                                    <input class="sp-control" wire:model.live.debounce.400ms="mrn" autocomplete="off">
                                    <button type="button" class="sp-btn sp-btn-ghost" wire:click="regenerateMrn">Regenerate</button>
                                </div>
                                <div wire:loading wire:target="mrn" class="sp-note">Checking…</div>
                                @if($mrnStatus === 'available') <div class="sp-status-ok">MRN ID is available</div> @endif
                                @if($mrnStatus === 'taken') <div class="sp-status-bad">This MRN ID is already taken</div> @endif
                                @if($mrnStatus === 'invalid') <div class="sp-status-bad">Invalid MRN format</div> @endif
                                @error('mrn') <div class="sp-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="sp-modal-foot">
                            <button type="button" class="sp-btn sp-btn-ghost" wire:click="$set('showFormModal', false)">Cancel</button>
                            <button type="button" class="sp-btn sp-btn-primary" wire:click="savePatient" wire:loading.attr="disabled" @if($mrnStatus==='taken' || $mrnStatus==='invalid') disabled @endif>{{ $editingUserId ? 'Save changes' : 'Create' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @if($showStatusDialog)
        <template x-teleport="body">
            <div>
                <div class="sp-backdrop" wire:click="$set('showStatusDialog', false)"></div>
                <div class="sp-layer">
                    <div class="sp-modal" role="alertdialog" aria-modal="true" aria-labelledby="sp-status-title" style="max-width:480px;">
                        <div class="sp-modal-head">
                            <h2 id="sp-status-title" class="sp-modal-title">{{ $statusActivating ? 'Activate' : 'Deactivate' }} {{ $statusUserName }}?</h2>
                            <button type="button" class="sp-x" wire:click="$set('showStatusDialog', false)" aria-label="Close">&times;</button>
                        </div>
                        <div class="sp-modal-body" style="grid-template-columns:1fr;">
                            <p>Status only — login blocking comes in a later step</p>
                        </div>
                        <div class="sp-modal-foot">
                            <button type="button" class="sp-btn sp-btn-ghost" wire:click="$set('showStatusDialog', false)">Cancel</button>
                            <button type="button" class="sp-btn sp-btn-primary" wire:click="confirmStatusChange">{{ $statusActivating ? 'Activate' : 'Deactivate' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

<script>
(function () {
    window.__spOpenMenu = function (button, id, isActive, profileUrl) {
        if (window.__spMenuEl) {
            window.__spMenuEl.remove();
            window.__spMenuEl = null;
        }
        const rect = button.getBoundingClientRect();
        const menu = document.createElement('div');
        const active = isActive === true || isActive === 'true' || isActive === 1 || isActive === '1';
        const toggleLabel = active ? 'Deactivate' : 'Activate';
        menu.className = 'sp-menu';
        menu.style.right = (window.innerWidth - rect.right) + 'px';
        menu.style.top = (rect.bottom + 6) + 'px';
        menu.innerHTML = ''
            + '<button type="button" data-action="overview"><span>View details</span></button>'
            + '<button type="button" data-action="dependents"><span>Manage dependents</span></button>'
            + '<button type="button" data-action="appointments"><span>View appointment bookings</span></button>'
            + '<button type="button" data-action="transactions"><span>View transactions</span></button>'
            + '<button type="button" data-action="edit"><svg viewBox="0 0 24 24"><path d="M3 21v-3l11-11 3 3L6 21H3z"></path></svg><span>Edit</span></button>'
            + '<button type="button" data-action="toggle"><svg viewBox="0 0 24 24"><path d="M12 2v10"></path><path d="M18.4 6.6a8 8 0 1 1-12.8 0"></path></svg><span>' + toggleLabel + '</span></button>';
        document.body.appendChild(menu);
        window.__spMenuEl = menu;

        function close() {
            if (window.__spMenuEl) {
                window.__spMenuEl.remove();
                window.__spMenuEl = null;
                document.removeEventListener('mousedown', onDoc);
            }
        }
        function onDoc(event) {
            if (!menu.contains(event.target) && event.target !== button) {
                close();
            }
        }
        document.addEventListener('mousedown', onDoc);
        window.addEventListener('scroll', close, { once: true });
        window.addEventListener('resize', close, { once: true });
        menu.addEventListener('click', function (event) {
            const action = event.target.closest('[data-action]')?.getAttribute('data-action');
            if (!action) {
                return;
            }
            close();
            if (action === 'overview' || action === 'dependents' || action === 'appointments' || action === 'transactions') {
                const url = new URL(profileUrl, window.location.origin);
                url.searchParams.set('tab', action);
                window.location.assign(url.toString());
                return;
            }
            if (action === 'edit') {
                Livewire.dispatch('openEditModal', { id: id });
            }
            if (action === 'toggle') {
                Livewire.dispatch('toggleActive', { id: id });
            }
        });
    };
})();
</script>
</div>
