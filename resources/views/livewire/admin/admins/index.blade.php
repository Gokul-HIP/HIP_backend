<div class="ad-page">
<style>
.ad-page{display:flex;flex-direction:column;gap:16px;color:var(--text-color);font-family:inherit}
.ad-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.ad-title{margin:0;font-size:24px;font-weight:700}
.ad-subtitle{margin:4px 0 0;font-size:14px;color:var(--muted-color)}
.ad-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 16px;border-radius:10px;border:1px solid transparent;font:inherit;font-size:14px;font-weight:700;cursor:pointer}
.ad-btn-primary{background:var(--button-color);color:var(--card-bg)}
.ad-btn-primary:hover{background:var(--button-hover)}
.ad-btn-ghost{background:var(--card-bg);border-color:var(--border-color);color:var(--text-color)}
.ad-btn-ghost:hover{background:var(--page-bg)}
.ad-btn-danger{background:var(--danger-color);color:var(--card-bg)}
.ad-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.ad-kpi,.ad-table-card,.ad-filters{background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card);box-shadow:var(--shadow-card)}
.ad-kpi{padding:16px}
.ad-kpi-label{margin:0;font-size:12px;color:var(--muted-color)}
.ad-kpi-value{margin:6px 0 0;font-size:32px;font-weight:700}
.ad-filters{display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:12px}
.ad-search{position:relative;flex:1 1 260px;min-width:0}
.ad-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-color);pointer-events:none}
.ad-control{box-sizing:border-box;width:100%;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;outline:none}
.ad-search .ad-control{padding-left:36px}
.ad-control:focus{border-color:var(--primary-color);box-shadow:0 0 0 3px var(--primary-hover)}
.ad-filter{flex:0 1 200px;min-width:150px}
.ad-table-wrap{overflow-x:auto}
.ad-table{width:100%;min-width:980px;border-collapse:separate;border-spacing:0;font-size:14px}
.ad-table thead{background:var(--primary-hover)}
.ad-table th{height:44px;padding:0 14px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted-color);white-space:nowrap}
.ad-table td{height:64px;padding:10px 14px;border-top:1px solid var(--border-color);vertical-align:middle}
.ad-row{background:var(--card-bg)}
.ad-row:hover{background:var(--page-bg)}
.ad-person{display:flex;align-items:center;gap:10px;min-width:0}
.ad-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--button-color);color:var(--card-bg);font-weight:700;font-size:13px;flex:none}
.ad-name{font-weight:600}
.ad-muted{color:var(--muted-color)}
.ad-roles{display:flex;flex-wrap:wrap;gap:6px}
.ad-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:var(--radius-pill);font-size:12px;font-weight:600;line-height:1.2;white-space:nowrap}
.ad-pill.is-active{background:var(--success-soft);color:var(--success-color)}
.ad-pill.is-inactive{background:var(--danger-soft);color:var(--danger-color)}
.ad-pill.is-role{background:var(--info-soft);color:var(--info-color)}
.ad-pill.is-muted{background:var(--page-bg);color:var(--muted-color);border:1px solid var(--border-color)}
.ad-dot{width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.ad-actions{text-align:right}
.ad-icon-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border-color);background:transparent;color:var(--text-color);cursor:pointer;font:inherit}
.ad-icon-btn:hover{background:var(--primary-hover)}
.ad-empty{padding:36px 16px;text-align:center}
.ad-empty-title{font-size:15px;font-weight:700}
.ad-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid var(--border-color);color:var(--muted-color);font-size:13px}
.ad-pagination-list{display:flex;align-items:center;gap:6px;margin:0;padding:0;list-style:none}
.ad-page-button{box-sizing:border-box;display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:13px;font-weight:600;cursor:pointer}
.ad-page-button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.ad-page-button.is-active{border-color:var(--button-color);background:var(--button-color);color:var(--card-bg)}
.ad-page-button.is-disabled{color:var(--muted-color);cursor:not-allowed;opacity:.55}
.ad-menu{position:fixed;z-index:10050;min-width:200px;padding:6px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:var(--shadow-card)}
.ad-menu button{all:unset;box-sizing:border-box;display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border-radius:8px;font-family:inherit;font-size:13px;font-weight:500;color:var(--text-color);cursor:pointer}
.ad-menu button:hover,.ad-menu button:focus-visible{background:var(--primary-hover);color:var(--primary-color)}
.ad-backdrop{position:fixed;inset:0;z-index:9999;background:color-mix(in srgb, var(--text-color) 45%, transparent)}
.ad-layer{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:24px}
.ad-modal{width:100%;max-width:640px;max-height:calc(100vh - 48px);display:flex;flex-direction:column;background:var(--card-bg);border-radius:var(--radius-card);box-shadow:var(--shadow-card);overflow:hidden}
.ad-modal-head,.ad-modal-foot{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border-color)}
.ad-modal-foot{justify-content:flex-end;border-bottom:0;border-top:1px solid var(--border-color)}
.ad-modal-title{margin:0;font-size:18px;font-weight:700}
.ad-modal-sub{margin:4px 0 0;font-size:13px;color:var(--muted-color)}
.ad-x{width:34px;height:34px;border:0;border-radius:10px;background:transparent;color:var(--muted-color);cursor:pointer;font-size:20px}
.ad-modal-body{padding:16px 20px;overflow-y:auto;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.ad-span2{grid-column:1 / -1}
.ad-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.ad-label{font-size:12.5px;font-weight:600}
.ad-error{font-size:12px;color:var(--danger-color)}
.ad-password{display:flex;gap:8px}
.ad-password .ad-control{flex:1}
@media (max-width:768px){
    .ad-kpis{grid-template-columns:1fr}
    .ad-filter,.ad-search{flex:1 1 100%}
    .ad-table{min-width:0}
    .ad-table thead{display:none}
    .ad-table,.ad-table tbody,.ad-table tr,.ad-table td{display:block;width:100%}
    .ad-table tr{margin:12px;border:1px solid var(--border-color);border-radius:var(--radius-card);overflow:hidden}
    .ad-table td{display:flex;justify-content:space-between;gap:12px;height:auto}
    .ad-table td::before{content:attr(data-label);color:var(--muted-color);font-size:12px;font-weight:700}
    .ad-actions{text-align:left}
    .ad-footer{flex-direction:column}
    .ad-modal-body{grid-template-columns:1fr}
    .ad-layer{align-items:flex-end;padding:0}
    .ad-modal{max-height:92vh;border-radius:var(--radius-card) var(--radius-card) 0 0}
}
</style>

<header class="ad-header">
    <div>
        <h1 class="ad-title">Admins</h1>
        <p class="ad-subtitle">Accounts with a password. App patients are listed under Patients.</p>
    </div>
</header>

<section class="ad-kpis" aria-label="Admin totals">
    <article class="ad-kpi"><p class="ad-kpi-label">Total</p><p class="ad-kpi-value">{{ $kpiTotal }}</p></article>
    <article class="ad-kpi"><p class="ad-kpi-label">Active</p><p class="ad-kpi-value">{{ $kpiActive }}</p></article>
    <article class="ad-kpi"><p class="ad-kpi-label">Inactive</p><p class="ad-kpi-value">{{ $kpiInactive }}</p></article>
</section>

<div class="ad-filters">
    <div class="ad-search">
        <span class="ad-search-icon" aria-hidden="true">⌕</span>
        <input class="ad-control" type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email, or mobile" aria-label="Search admins">
    </div>
    <select class="ad-control ad-filter" wire:model.live="roleFilter" aria-label="Role">
        <option value="all">All roles</option>
        <option value="none">No role</option>
        @foreach($roles as $role)
            <option value="{{ $role->name }}">{{ $role->name }}</option>
        @endforeach
    </select>
    <select class="ad-control ad-filter" wire:model.live="statusFilter" aria-label="Status">
        <option value="all">All status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
    <button type="button" class="ad-btn ad-btn-ghost" wire:click="clearFilters">Clear</button>
</div>

<div class="ad-table-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="ad-actions">Actions</th>
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
                    <tr class="ad-row" wire:key="ad-admin-{{ $admin->id }}">
                        <td data-label="Name">
                            <div class="ad-person">
                                <span class="ad-avatar" aria-hidden="true">{{ $initials }}</span>
                                <span class="ad-name">{{ $adminName }}</span>
                            </div>
                        </td>
                        <td data-label="Email">{{ $admin->email ?: '—' }}</td>
                        <td data-label="Mobile">{{ $admin->mobile_num ?: '—' }}</td>
                        <td data-label="Role">
                            <div class="ad-roles">
                                @forelse($admin->roles as $role)
                                    <span class="ad-pill is-role">{{ $role->name }}</span>
                                @empty
                                    <span class="ad-pill is-muted">No role</span>
                                @endforelse
                            </div>
                        </td>
                        <td data-label="Status">
                            @if($isActive)
                                <span class="ad-pill is-active"><span class="ad-dot"></span>Active</span>
                            @else
                                <span class="ad-pill is-inactive"><span class="ad-dot"></span>Inactive</span>
                            @endif
                        </td>
                        <td data-label="Created">{{ $admin->created_at ? \Illuminate\Support\Carbon::parse($admin->created_at)->timezone(config('app.timezone'))->format('d M Y') : '—' }}</td>
                        <td class="ad-actions" data-label="Actions">
                            <button type="button" class="ad-icon-btn" aria-haspopup="menu" aria-label="Actions for {{ $adminName }}" onclick="window.__adOpenMenu(this, {{ \Illuminate\Support\Js::from($admin->id) }}, {{ $isActive ? 'true' : 'false' }})">⋯</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="ad-empty">
                            <div class="ad-empty-title">No admins found</div>
                            <p class="ad-muted">Try changing the search or filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <footer class="ad-footer">
        <span>Showing {{ $admins->firstItem() ?? 0 }}–{{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }}</span>
        @if($admins->hasPages())
            <nav aria-label="Pagination">
                <ul class="ad-pagination-list">
                    <li><button type="button" class="ad-page-button {{ $admins->onFirstPage() ? 'is-disabled' : '' }}" wire:click="previousPage('adminsPage')" @disabled($admins->onFirstPage()) aria-label="Previous page"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12.5 4.5-5 5.5 5 5.5" /></svg></button></li>
                    @foreach(range(1, $admins->lastPage()) as $page)
                        <li><button type="button" class="ad-page-button {{ $page == $admins->currentPage() ? 'is-active' : '' }}" wire:click="gotoPage({{ $page }}, 'adminsPage')" @if($page == $admins->currentPage()) disabled aria-current="page" @endif>{{ $page }}</button></li>
                    @endforeach
                    <li><button type="button" class="ad-page-button {{ $admins->hasMorePages() ? '' : 'is-disabled' }}" wire:click="nextPage('adminsPage')" @disabled(! $admins->hasMorePages()) aria-label="Next page"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 4.5 5 5.5-5 5.5" /></svg></button></li>
                </ul>
            </nav>
        @endif
    </footer>
</div>

@if($showEditModal)
    <template x-teleport="body">
        <div>
            <div class="ad-backdrop" wire:click="$set('showEditModal', false)"></div>
            <div class="ad-layer">
                <div class="ad-modal" role="dialog" aria-modal="true" aria-labelledby="ad-edit-title">
                    <div class="ad-modal-head">
                        <div>
                            <h2 id="ad-edit-title" class="ad-modal-title">Edit admin</h2>
                            <p class="ad-modal-sub">Update the name, contact, and role.</p>
                        </div>
                        <button type="button" class="ad-x" wire:click="$set('showEditModal', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="ad-modal-body">
                        <div class="ad-field">
                            <label class="ad-label" for="ad-first-name">First name</label>
                            <input id="ad-first-name" class="ad-control" wire:model="first_name" autocomplete="off">
                            @error('first_name') <div class="ad-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="ad-field">
                            <label class="ad-label" for="ad-last-name">Last name</label>
                            <input id="ad-last-name" class="ad-control" wire:model="last_name" autocomplete="off">
                            @error('last_name') <div class="ad-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="ad-field">
                            <label class="ad-label" for="ad-email">Email</label>
                            <input id="ad-email" class="ad-control" type="email" wire:model="email" autocomplete="off">
                            @error('email') <div class="ad-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="ad-field">
                            <label class="ad-label" for="ad-mobile">Mobile</label>
                            <input id="ad-mobile" class="ad-control" wire:model="mobile_num" autocomplete="off">
                            @error('mobile_num') <div class="ad-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="ad-field ad-span2">
                            <label class="ad-label" for="ad-role">Role</label>
                            <select id="ad-role" class="ad-control" wire:model="roleName">
                                <option value="">Select a role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('roleName') <div class="ad-error">{{ $message }}</div> @enderror
                        </div>
                        @if($actionError !== '')
                            <p class="ad-error ad-span2">{{ $actionError }}</p>
                        @endif
                    </div>
                    <div class="ad-modal-foot">
                        <button type="button" class="ad-btn ad-btn-ghost" wire:click="$set('showEditModal', false)">Cancel</button>
                        <button type="button" class="ad-btn ad-btn-primary" wire:click="saveEdit">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

@if($showPasswordModal)
    <template x-teleport="body">
        <div>
            <div class="ad-backdrop" wire:click="$set('showPasswordModal', false)"></div>
            <div class="ad-layer">
                <div class="ad-modal" role="dialog" aria-modal="true" aria-labelledby="ad-password-title" x-data="{ show: false }">
                    <div class="ad-modal-head">
                        <div>
                            <h2 id="ad-password-title" class="ad-modal-title">Change password</h2>
                            <p class="ad-modal-sub">{{ $statusUserName }}</p>
                        </div>
                        <button type="button" class="ad-x" wire:click="$set('showPasswordModal', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="ad-modal-body">
                        <div class="ad-field ad-span2">
                            <label class="ad-label" for="ad-password">New password</label>
                            <div class="ad-password">
                                <input id="ad-password" class="ad-control" x-bind:type="show ? 'text' : 'password'" wire:model="password" autocomplete="new-password">
                                <button type="button" class="ad-btn ad-btn-ghost" x-on:click="show = !show" x-text="show ? 'Hide' : 'Show'"></button>
                            </div>
                            @error('password') <div class="ad-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="ad-field ad-span2">
                            <label class="ad-label" for="ad-password-confirm">Confirm password</label>
                            <input id="ad-password-confirm" class="ad-control" x-bind:type="show ? 'text' : 'password'" wire:model="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="ad-modal-foot">
                        <button type="button" class="ad-btn ad-btn-ghost" wire:click="$set('showPasswordModal', false)">Cancel</button>
                        <button type="button" class="ad-btn ad-btn-primary" wire:click="savePassword">Update password</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

@if($showStatusDialog)
    <template x-teleport="body">
        <div>
            <div class="ad-backdrop" wire:click="$set('showStatusDialog', false)"></div>
            <div class="ad-layer">
                <div class="ad-modal" role="alertdialog" aria-modal="true" aria-labelledby="ad-status-title" style="max-width:480px;">
                    <div class="ad-modal-head">
                        <h2 id="ad-status-title" class="ad-modal-title">{{ $statusActivating ? 'Activate' : 'Deactivate' }} {{ $statusUserName }}?</h2>
                        <button type="button" class="ad-x" wire:click="$set('showStatusDialog', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="ad-modal-body" style="grid-template-columns:1fr;">
                        <p>The account will be signed out and blocked from logging in until reactivated.</p>
                        @if($actionError !== '')
                            <p class="ad-error">{{ $actionError }}</p>
                        @endif
                    </div>
                    <div class="ad-modal-foot">
                        <button type="button" class="ad-btn ad-btn-ghost" wire:click="$set('showStatusDialog', false)">Cancel</button>
                        <button type="button" class="ad-btn ad-btn-primary" wire:click="confirmStatusChange">{{ $statusActivating ? 'Activate' : 'Deactivate' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

@if($showDeleteDialog)
    <template x-teleport="body">
        <div>
            <div class="ad-backdrop" wire:click="$set('showDeleteDialog', false)"></div>
            <div class="ad-layer">
                <div class="ad-modal" role="alertdialog" aria-modal="true" aria-labelledby="ad-delete-title" style="max-width:480px;">
                    <div class="ad-modal-head">
                        <h2 id="ad-delete-title" class="ad-modal-title">Delete {{ $deleteUserName }}?</h2>
                        <button type="button" class="ad-x" wire:click="$set('showDeleteDialog', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="ad-modal-body" style="grid-template-columns:1fr;">
                        <p>This removes {{ $deleteUserName }} from the admin list.</p>
                        @if($actionError !== '')
                            <p class="ad-error">{{ $actionError }}</p>
                        @endif
                    </div>
                    <div class="ad-modal-foot">
                        <button type="button" class="ad-btn ad-btn-ghost" wire:click="$set('showDeleteDialog', false)">Cancel</button>
                        <button type="button" class="ad-btn ad-btn-danger" wire:click="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

<script>
(function () {
    window.__adOpenMenu = function (button, id, isActive) {
        if (window.__adMenuEl) {
            window.__adMenuEl.remove();
            window.__adMenuEl = null;
        }
        const rect = button.getBoundingClientRect();
        const menu = document.createElement('div');
        const active = isActive === true || isActive === 'true' || isActive === 1 || isActive === '1';
        menu.className = 'ad-menu';
        menu.style.right = (window.innerWidth - rect.right) + 'px';
        menu.style.top = (rect.bottom + 6) + 'px';
        menu.innerHTML = ''
            + '<button type="button" data-action="edit">Edit</button>'
            + '<button type="button" data-action="password">Change password</button>'
            + '<button type="button" data-action="toggle">' + (active ? 'Deactivate' : 'Activate') + '</button>'
            + '<button type="button" data-action="delete">Delete</button>';
        document.body.appendChild(menu);
        window.__adMenuEl = menu;
        function close() {
            if (window.__adMenuEl) {
                window.__adMenuEl.remove();
                window.__adMenuEl = null;
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
