<div class="hip-admin-page">
<style>
.hip-admin-page{display:flex;flex-direction:column;gap:16px;color:var(--text-color);font-family:inherit}
.hip-admin-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.hip-admin-title{margin:0;font-size:24px;font-weight:700}
.hip-admin-subtitle{margin:4px 0 0;font-size:14px;color:var(--muted-color)}
.hip-admin-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 16px;border-radius:10px;border:1px solid transparent;font:inherit;font-size:14px;font-weight:700;cursor:pointer}
.hip-admin-btn-primary{background:var(--button-color);color:var(--card-bg)}
.hip-admin-btn-primary:hover{background:var(--button-hover)}
.hip-admin-btn-ghost{background:var(--card-bg);border-color:var(--border-color);color:var(--text-color)}
.hip-admin-btn-ghost:hover{background:var(--page-bg)}
.hip-admin-btn-danger{background:var(--danger-color);color:var(--card-bg)}
.hip-admin-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.hip-admin-kpi,.hip-admin-table-card,.hip-admin-filters{background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card);box-shadow:var(--shadow-card)}
.hip-admin-kpi{padding:16px}
.hip-admin-kpi-label{margin:0;font-size:12px;color:var(--muted-color)}
.hip-admin-kpi-value{margin:6px 0 0;font-size:32px;font-weight:700}
.hip-admin-filters{display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:12px}
.hip-admin-search{position:relative;flex:1 1 260px;min-width:0}
.hip-admin-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-color);pointer-events:none}
.hip-admin-control{box-sizing:border-box;width:100%;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;outline:none}
.hip-admin-search .hip-admin-control{padding-left:36px}
.hip-admin-control:focus{border-color:var(--primary-color);box-shadow:0 0 0 3px var(--primary-hover)}
.hip-admin-filter{flex:0 1 200px;min-width:150px}
.hip-admin-table-wrap{overflow-x:auto}
.hip-admin-table{width:100%;min-width:980px;border-collapse:separate;border-spacing:0;font-size:14px}
.hip-admin-table thead{background:var(--primary-hover)}
.hip-admin-table th{height:44px;padding:0 14px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted-color);white-space:nowrap}
.hip-admin-table td{height:64px;padding:10px 14px;border-top:1px solid var(--border-color);vertical-align:middle}
.hip-admin-row{background:var(--card-bg)}
.hip-admin-row:hover{background:var(--page-bg)}
.hip-admin-person{display:flex;align-items:center;gap:10px;min-width:0}
.hip-admin-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--button-color);color:var(--card-bg);font-weight:700;font-size:13px;flex:none}
.hip-admin-name{font-weight:600}
.hip-admin-muted{color:var(--muted-color)}
.hip-admin-roles{display:flex;flex-wrap:wrap;gap:6px}
.hip-admin-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:var(--radius-pill);font-size:12px;font-weight:600;line-height:1.2;white-space:nowrap}
.hip-admin-pill.is-active{background:var(--success-soft);color:var(--success-color)}
.hip-admin-pill.is-inactive{background:var(--danger-soft);color:var(--danger-color)}
.hip-admin-pill.is-role{background:var(--info-soft);color:var(--info-color)}
.hip-admin-pill.is-muted{background:var(--page-bg);color:var(--muted-color);border:1px solid var(--border-color)}
.hip-admin-dot{width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.hip-admin-actions{text-align:right}
.hip-admin-icon-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border-color);background:transparent;color:var(--text-color);cursor:pointer;font:inherit}
.hip-admin-icon-btn:hover{background:var(--primary-hover)}
.hip-admin-empty{padding:36px 16px;text-align:center}
.hip-admin-empty-title{font-size:15px;font-weight:700}
.hip-admin-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid var(--border-color);color:var(--muted-color);font-size:13px}
.hip-admin-pagination-list{display:flex;align-items:center;gap:6px;margin:0;padding:0;list-style:none}
.hip-admin-page-button{box-sizing:border-box;display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:13px;font-weight:600;cursor:pointer}
.hip-admin-page-button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.hip-admin-page-button.is-active{border-color:var(--button-color);background:var(--button-color);color:var(--card-bg)}
.hip-admin-page-button.is-disabled{color:var(--muted-color);cursor:not-allowed;opacity:.55}
.hip-admin-menu{position:fixed;z-index:10050;min-width:200px;padding:6px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:var(--shadow-card)}
.hip-admin-menu button{all:unset;box-sizing:border-box;display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border-radius:8px;font-family:inherit;font-size:13px;font-weight:500;color:var(--text-color);cursor:pointer}
.hip-admin-menu button:hover,.hip-admin-menu button:focus-visible{background:var(--primary-hover);color:var(--primary-color)}
.hip-admin-backdrop{position:fixed;inset:0;z-index:9999;background:color-mix(in srgb, var(--text-color) 45%, transparent)}
.hip-admin-layer{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:24px}
.hip-admin-modal{width:100%;max-width:640px;max-height:calc(100vh - 48px);display:flex;flex-direction:column;background:var(--card-bg);border-radius:var(--radius-card);box-shadow:var(--shadow-card);overflow:hidden}
.hip-admin-modal-head,.hip-admin-modal-foot{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border-color)}
.hip-admin-modal-foot{justify-content:flex-end;border-bottom:0;border-top:1px solid var(--border-color)}
.hip-admin-modal-title{margin:0;font-size:18px;font-weight:700}
.hip-admin-modal-sub{margin:4px 0 0;font-size:13px;color:var(--muted-color)}
.hip-admin-x{width:34px;height:34px;border:0;border-radius:10px;background:transparent;color:var(--muted-color);cursor:pointer;font-size:20px}
.hip-admin-modal-body{padding:16px 20px;overflow-y:auto;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.hip-admin-span2{grid-column:1 / -1}
.hip-admin-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.hip-admin-label{font-size:12.5px;font-weight:600}
.hip-admin-error{font-size:12px;color:var(--danger-color)}
.hip-admin-password{display:flex;gap:8px}
.hip-admin-password .hip-admin-control{flex:1}
@media (max-width:768px){
    .hip-admin-kpis{grid-template-columns:1fr}
    .hip-admin-filter,.hip-admin-search{flex:1 1 100%}
    .hip-admin-table{min-width:0}
    .hip-admin-table thead{display:none}
    .hip-admin-table,.hip-admin-table tbody,.hip-admin-table tr,.hip-admin-table td{display:block;width:100%}
    .hip-admin-table tr{margin:12px;border:1px solid var(--border-color);border-radius:var(--radius-card);overflow:hidden}
    .hip-admin-table td{display:flex;justify-content:space-between;gap:12px;height:auto}
    .hip-admin-table td::before{content:attr(data-label);color:var(--muted-color);font-size:12px;font-weight:700}
    .hip-admin-actions{text-align:left}
    .hip-admin-footer{flex-direction:column}
    .hip-admin-modal-body{grid-template-columns:1fr}
    .hip-admin-layer{align-items:flex-end;padding:0}
    .hip-admin-modal{max-height:92vh;border-radius:var(--radius-card) var(--radius-card) 0 0}
}
</style>

<header class="hip-admin-header">
    <div>
        <h1 class="hip-admin-title">Admins</h1>
        <p class="hip-admin-subtitle">Accounts with a password. App patients are listed under Patients.</p>
    </div>
</header>

<section class="hip-admin-kpis" aria-label="Admin totals">
    <article class="hip-admin-kpi"><p class="hip-admin-kpi-label">Total</p><p class="hip-admin-kpi-value">{{ $kpiTotal }}</p></article>
    <article class="hip-admin-kpi"><p class="hip-admin-kpi-label">Active</p><p class="hip-admin-kpi-value">{{ $kpiActive }}</p></article>
    <article class="hip-admin-kpi"><p class="hip-admin-kpi-label">Inactive</p><p class="hip-admin-kpi-value">{{ $kpiInactive }}</p></article>
</section>

<div class="hip-admin-filters">
    <div class="hip-admin-search">
        <span class="hip-admin-search-icon" aria-hidden="true">⌕</span>
        <input class="hip-admin-control" type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email, or mobile" aria-label="Search admins">
    </div>
    <select class="hip-admin-control hip-admin-filter" wire:model.live="roleFilter" aria-label="Role">
        <option value="all">All roles</option>
        <option value="none">No role</option>
        @foreach($roles as $role)
            <option value="{{ $role->name }}">{{ $role->name }}</option>
        @endforeach
    </select>
    <select class="hip-admin-control hip-admin-filter" wire:model.live="statusFilter" aria-label="Status">
        <option value="all">All status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
    <button type="button" class="hip-admin-btn hip-admin-btn-ghost" wire:click="clearFilters">Clear</button>
</div>

<div class="hip-admin-table-card">
    <div class="hip-admin-table-wrap">
        <table class="hip-admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="hip-admin-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    @php
                        $adminName = trim(($admin->first_name ?? '').' '.($admin->last_name ?? ''));
                        $adminName = $adminName !== '' ? $adminName : ($admin->email ?: 'Unnamed admin');
                        $initials = collect(preg_split('/\s+/', $adminName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
                        $initials = $initials !== '' ? $initials : '?';
                        $isActive = (bool) ($admin->is_active ?? true);
                    @endphp
                    <tr class="hip-admin-row" wire:key="hip-admin-{{ $admin->id }}">
                        <td data-label="Name">
                            <div class="hip-admin-person">
                                <span class="hip-admin-avatar" aria-hidden="true">{{ $initials }}</span>
                                <span class="hip-admin-name">{{ $adminName }}</span>
                            </div>
                        </td>
                        <td data-label="Email">{{ $admin->email ?: '—' }}</td>
                        <td data-label="Mobile">{{ $admin->mobile_num ?: '—' }}</td>
                        <td data-label="Role">
                            <div class="hip-admin-roles">
                                @forelse($admin->roles as $role)
                                    <span class="hip-admin-pill is-role">{{ $role->name }}</span>
                                @empty
                                    <span class="hip-admin-pill is-muted">No role</span>
                                @endforelse
                            </div>
                        </td>
                        <td data-label="Status">
                            @if($isActive)
                                <span class="hip-admin-pill is-active"><span class="hip-admin-dot"></span>Active</span>
                            @else
                                <span class="hip-admin-pill is-inactive"><span class="hip-admin-dot"></span>Inactive</span>
                            @endif
                        </td>
                        <td data-label="Created">{{ $admin->created_at ? \Illuminate\Support\Carbon::parse($admin->created_at)->timezone(config('app.timezone'))->format('d M Y') : '—' }}</td>
                        <td class="hip-admin-actions" data-label="Actions">
                            <button type="button" class="hip-admin-icon-btn" aria-haspopup="menu" aria-label="Actions for {{ $adminName }}" onclick="window.__hipAdminOpenMenu(this, {{ \Illuminate\Support\Js::from($admin->id) }}, {{ $isActive ? 'true' : 'false' }})">⋯</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="hip-admin-empty">
                            <div class="hip-admin-empty-title">No admins found</div>
                            <p class="hip-admin-muted">Try changing the search or filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <footer class="hip-admin-footer">
        <span>Showing {{ $admins->firstItem() ?? 0 }}–{{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }}</span>
        @if($admins->hasPages())
            <nav aria-label="Pagination">
                <ul class="hip-admin-pagination-list">
                    <li><button type="button" class="hip-admin-page-button {{ $admins->onFirstPage() ? 'is-disabled' : '' }}" wire:click="previousPage('adminsPage')" @disabled($admins->onFirstPage()) aria-label="Previous page"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12.5 4.5-5 5.5 5 5.5" /></svg></button></li>
                    @foreach(range(1, $admins->lastPage()) as $page)
                        <li><button type="button" class="hip-admin-page-button {{ $page == $admins->currentPage() ? 'is-active' : '' }}" wire:click="gotoPage({{ $page }}, 'adminsPage')" @if($page == $admins->currentPage()) disabled aria-current="page" @endif>{{ $page }}</button></li>
                    @endforeach
                    <li><button type="button" class="hip-admin-page-button {{ $admins->hasMorePages() ? '' : 'is-disabled' }}" wire:click="nextPage('adminsPage')" @disabled(! $admins->hasMorePages()) aria-label="Next page"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 4.5 5 5.5-5 5.5" /></svg></button></li>
                </ul>
            </nav>
        @endif
    </footer>
</div>

@if($showEditModal)
    <template x-teleport="body">
        <div>
            <div class="hip-admin-backdrop" wire:click="$set('showEditModal', false)"></div>
            <div class="hip-admin-layer">
                <div class="hip-admin-modal" role="dialog" aria-modal="true" aria-labelledby="hip-admin-edit-title">
                    <div class="hip-admin-modal-head">
                        <div>
                            <h2 id="hip-admin-edit-title" class="hip-admin-modal-title">Edit admin</h2>
                            <p class="hip-admin-modal-sub">Update the name, contact, and role.</p>
                        </div>
                        <button type="button" class="hip-admin-x" wire:click="$set('showEditModal', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="hip-admin-modal-body">
                        <div class="hip-admin-field">
                            <label class="hip-admin-label" for="hip-admin-first-name">First name</label>
                            <input id="hip-admin-first-name" class="hip-admin-control" wire:model="first_name" autocomplete="off">
                            @error('first_name') <div class="hip-admin-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="hip-admin-field">
                            <label class="hip-admin-label" for="hip-admin-last-name">Last name</label>
                            <input id="hip-admin-last-name" class="hip-admin-control" wire:model="last_name" autocomplete="off">
                            @error('last_name') <div class="hip-admin-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="hip-admin-field">
                            <label class="hip-admin-label" for="hip-admin-email">Email</label>
                            <input id="hip-admin-email" class="hip-admin-control" type="email" wire:model="email" autocomplete="off">
                            @error('email') <div class="hip-admin-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="hip-admin-field">
                            <label class="hip-admin-label" for="hip-admin-mobile">Mobile</label>
                            <input id="hip-admin-mobile" class="hip-admin-control" wire:model="mobile_num" autocomplete="off">
                            @error('mobile_num') <div class="hip-admin-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="hip-admin-field hip-admin-span2">
                            <label class="hip-admin-label" for="hip-admin-role">Role</label>
                            <select id="hip-admin-role" class="hip-admin-control" wire:model="roleName">
                                <option value="">Select a role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('roleName') <div class="hip-admin-error">{{ $message }}</div> @enderror
                        </div>
                        @if($actionError !== '')
                            <p class="hip-admin-error hip-admin-span2">{{ $actionError }}</p>
                        @endif
                    </div>
                    <div class="hip-admin-modal-foot">
                        <button type="button" class="hip-admin-btn hip-admin-btn-ghost" wire:click="$set('showEditModal', false)">Cancel</button>
                        <button type="button" class="hip-admin-btn hip-admin-btn-primary" wire:click="saveEdit">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

@if($showPasswordModal)
    <template x-teleport="body">
        <div>
            <div class="hip-admin-backdrop" wire:click="$set('showPasswordModal', false)"></div>
            <div class="hip-admin-layer">
                <div class="hip-admin-modal" role="dialog" aria-modal="true" aria-labelledby="hip-admin-password-title" x-data="{ show: false }">
                    <div class="hip-admin-modal-head">
                        <div>
                            <h2 id="hip-admin-password-title" class="hip-admin-modal-title">Change password</h2>
                            <p class="hip-admin-modal-sub">{{ $statusUserName }}</p>
                        </div>
                        <button type="button" class="hip-admin-x" wire:click="$set('showPasswordModal', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="hip-admin-modal-body">
                        <div class="hip-admin-field hip-admin-span2">
                            <label class="hip-admin-label" for="hip-admin-password">New password</label>
                            <div class="hip-admin-password">
                                <input id="hip-admin-password" class="hip-admin-control" x-bind:type="show ? 'text' : 'password'" wire:model="password" autocomplete="new-password">
                                <button type="button" class="hip-admin-btn hip-admin-btn-ghost" x-on:click="show = !show" x-text="show ? 'Hide' : 'Show'"></button>
                            </div>
                            @error('password') <div class="hip-admin-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="hip-admin-field hip-admin-span2">
                            <label class="hip-admin-label" for="hip-admin-password-confirm">Confirm password</label>
                            <input id="hip-admin-password-confirm" class="hip-admin-control" x-bind:type="show ? 'text' : 'password'" wire:model="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="hip-admin-modal-foot">
                        <button type="button" class="hip-admin-btn hip-admin-btn-ghost" wire:click="$set('showPasswordModal', false)">Cancel</button>
                        <button type="button" class="hip-admin-btn hip-admin-btn-primary" wire:click="savePassword">Update password</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

@if($showStatusDialog)
    <template x-teleport="body">
        <div>
            <div class="hip-admin-backdrop" wire:click="$set('showStatusDialog', false)"></div>
            <div class="hip-admin-layer">
                <div class="hip-admin-modal" role="alertdialog" aria-modal="true" aria-labelledby="hip-admin-status-title" style="max-width:480px;">
                    <div class="hip-admin-modal-head">
                        <h2 id="hip-admin-status-title" class="hip-admin-modal-title">{{ $statusActivating ? 'Activate' : 'Deactivate' }} {{ $statusUserName }}?</h2>
                        <button type="button" class="hip-admin-x" wire:click="$set('showStatusDialog', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="hip-admin-modal-body" style="grid-template-columns:1fr;">
                        <p>The account will be signed out and blocked from logging in until reactivated.</p>
                        @if($actionError !== '')
                            <p class="hip-admin-error">{{ $actionError }}</p>
                        @endif
                    </div>
                    <div class="hip-admin-modal-foot">
                        <button type="button" class="hip-admin-btn hip-admin-btn-ghost" wire:click="$set('showStatusDialog', false)">Cancel</button>
                        <button type="button" class="hip-admin-btn hip-admin-btn-primary" wire:click="confirmStatusChange">{{ $statusActivating ? 'Activate' : 'Deactivate' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

@if($showDeleteDialog)
    <template x-teleport="body">
        <div>
            <div class="hip-admin-backdrop" wire:click="$set('showDeleteDialog', false)"></div>
            <div class="hip-admin-layer">
                <div class="hip-admin-modal" role="alertdialog" aria-modal="true" aria-labelledby="hip-admin-delete-title" style="max-width:480px;">
                    <div class="hip-admin-modal-head">
                        <h2 id="hip-admin-delete-title" class="hip-admin-modal-title">Delete {{ $deleteUserName }}?</h2>
                        <button type="button" class="hip-admin-x" wire:click="$set('showDeleteDialog', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="hip-admin-modal-body" style="grid-template-columns:1fr;">
                        <p>This removes {{ $deleteUserName }} from the admin list.</p>
                        @if($actionError !== '')
                            <p class="hip-admin-error">{{ $actionError }}</p>
                        @endif
                    </div>
                    <div class="hip-admin-modal-foot">
                        <button type="button" class="hip-admin-btn hip-admin-btn-ghost" wire:click="$set('showDeleteDialog', false)">Cancel</button>
                        <button type="button" class="hip-admin-btn hip-admin-btn-danger" wire:click="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

<script>
(function () {
    window.__hipAdminOpenMenu = function (button, id, isActive) {
        if (window.__hipAdminMenuEl) {
            window.__hipAdminMenuEl.remove();
            window.__hipAdminMenuEl = null;
        }
        const rect = button.getBoundingClientRect();
        const menu = document.createElement('div');
        const active = isActive === true || isActive === 'true' || isActive === 1 || isActive === '1';
        menu.className = 'hip-admin-menu';
        menu.style.right = (window.innerWidth - rect.right) + 'px';
        menu.style.top = (rect.bottom + 6) + 'px';
        menu.innerHTML = ''
            + '<button type="button" data-action="edit">Edit</button>'
            + '<button type="button" data-action="password">Change password</button>'
            + '<button type="button" data-action="toggle">' + (active ? 'Deactivate' : 'Activate') + '</button>'
            + '<button type="button" data-action="delete">Delete</button>';
        document.body.appendChild(menu);
        window.__hipAdminMenuEl = menu;
        function close() {
            if (window.__hipAdminMenuEl) {
                window.__hipAdminMenuEl.remove();
                window.__hipAdminMenuEl = null;
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
            if (action === 'edit') {
                Livewire.dispatch('openAdminEdit', { id: id });
            }
            if (action === 'password') {
                Livewire.dispatch('openAdminPassword', { id: id });
            }
            if (action === 'toggle') {
                Livewire.dispatch('toggleAdminActive', { id: id });
            }
            if (action === 'delete') {
                Livewire.dispatch('askAdminDelete', { id: id });
            }
        });
    };
})();
</script>
</div>
