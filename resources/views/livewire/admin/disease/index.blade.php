<div class="dept-wrap">
    <style>
        .dept-wrap,
        .dept-wrap * { box-sizing: border-box; }
        .dept-header *,
        .dept-stats *,
        .dept-table-card *,
        [data-flux-modal="department-form"] dialog *,
        [data-flux-modal="delete-department"] dialog * {
            margin:0;
            padding:0;
        }
        .dept-wrap {
            --brand: #0da2e7;
            --brand-dark: #0886c4;
            --brand-light: #e8f6fd;
            --brand-mid: #b3e3f8;
            font-family: 'DM Sans', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            padding: 2rem;
            color: #111827;
        }
        .dept-header {
            background: #fff;
            border-radius: 16px;
            padding: 1.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e8ecf0;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .dept-header-left h1 { font-size:1.4rem; font-weight:700; letter-spacing:-.02em; color:#0f172a; }
        .dept-header-left p  { font-size:.85rem; color:#64748b; margin-top:.25rem; }
        .btn-add {
            display:inline-flex; align-items:center; gap:.5rem;
            background:var(--brand); color:#fff; border:none; border-radius:10px;
            padding:.65rem 1.25rem; font-size:.875rem; font-weight:600;
            cursor:pointer; transition:background .15s,transform .1s; font-family:inherit; white-space:nowrap;
        }
        .btn-add:hover { background:var(--brand-dark); }
        .btn-add:active { transform:scale(.97); }
        .dept-stats { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; margin-bottom:1.25rem; }
        .stat-card {
            background:#fff; border-radius:14px; padding:1.25rem 1.5rem;
            border:1px solid #e8ecf0; box-shadow:0 1px 4px rgba(0,0,0,.04);
            display:flex; align-items:center; gap:1rem;
        }
        .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
        .stat-icon.blue { background:var(--brand-light); color:var(--brand); }
        .stat-icon.green { background:#e6f4ea; color:#1e7e34; }
        .stat-label { font-size:.75rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:#94a3b8; }
        .stat-value { font-size:1.75rem; font-weight:800; color:#0f172a; line-height:1.1; margin-top:.15rem; letter-spacing:-.03em; }
        .dept-table-card { background:#fff; border-radius:16px; border:1px solid #e8ecf0; box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
        .table-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; }
        .search-wrap { position:relative; flex:1; max-width:340px; }
        .search-wrap i { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.9rem; pointer-events:none; }
        .search-input {
            width:100%; border:1px solid #e2e8f0; border-radius:9px;
            padding:.55rem .85rem .55rem 2.35rem; font-size:.875rem; font-family:inherit;
            color:#0f172a; background:#f8fafc; outline:none; transition:border-color .15s,box-shadow .15s;
        }
        .search-input:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(13,162,231,.1); background:#fff; }
        .page-select { border:1px solid #e2e8f0; border-radius:9px; padding:.55rem .8rem; font-size:.875rem; font-family:inherit; color:#374151; background:#f8fafc; outline:none; cursor:pointer; }
        .dept-table { width:100%; border-collapse:collapse; }
        .dept-table thead { background:#f8fafc; }
        .dept-table th { padding:.75rem 1.5rem; text-align:left; font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8; white-space:nowrap; }
        .dept-table th:last-child { text-align:right; }
        .dept-table tbody tr { border-top:1px solid #f1f5f9; transition:background .1s; }
        .dept-table tbody tr:hover { background:#f8fafc; }
        .dept-table td { padding:1rem 1.5rem; vertical-align:middle; }
        .dept-table td:last-child { text-align:right; }
        .dept-name-cell { display:flex; align-items:center; gap:.85rem; }
        .dept-avatar { width:46px; height:46px; border-radius:10px; object-fit:cover; border:1px solid #e8ecf0; flex-shrink:0; }
        .dept-avatar-placeholder { width:46px; height:46px; border-radius:10px; background:var(--brand-light); color:var(--brand); display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; border:1px solid var(--brand-mid); }
        .dept-name { font-weight:600; font-size:.925rem; color:#0f172a; }
        .disease-pills { display:flex; flex-wrap:wrap; gap:.4rem; max-width:480px; }
        .disease-pill { background:var(--brand-light); color:#0369a1; border:1px solid #bae6fd; border-radius:99px; padding:.25rem .7rem; font-size:.75rem; font-weight:500; white-space:nowrap; }
        .no-diseases { font-size:.85rem; color:#cbd5e1; font-style:italic; }
        .action-btn { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; border:1px solid transparent; background:transparent; cursor:pointer; font-size:.95rem; transition:background .13s,border-color .13s,color .13s; }
        .action-btn.edit { color:var(--brand); }
        .action-btn.edit:hover { background:var(--brand-light); border-color:var(--brand-mid); }
        .action-btn.delete { color:#ef4444; margin-left:.25rem; }
        .action-btn.delete:hover { background:#fef2f2; border-color:#fecaca; }
        .empty-state { padding:4rem 1.5rem; text-align:center; }
        .empty-icon { font-size:2.5rem; color:#cbd5e1; margin-bottom:1rem; }
        .empty-title { font-size:1rem; font-weight:600; color:#374151; }
        .empty-sub { font-size:.875rem; color:#94a3b8; margin-top:.4rem; }
        .pagination-wrap { padding:1rem 1.5rem; border-top:1px solid #f1f5f9; }
        /* Modal form styles */
        .m-label { display:block; font-size:.8rem; font-weight:600; color:#374151; margin-bottom:.4rem; }
        .m-input { width:100%; border:1px solid #e2e8f0; border-radius:9px; padding:.6rem .9rem; font-size:.875rem; font-family:inherit; color:#0f172a; outline:none; transition:border-color .15s,box-shadow .15s; background:#fff; }
        .m-input:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(13,162,231,.1); }
        .m-err { font-size:.78rem; color:#ef4444; margin-top:.3rem; }
        .file-wrap { border:1.5px dashed #e2e8f0; border-radius:10px; padding:.85rem; display:flex; align-items:center; gap:.75rem; background:#fafafa; cursor:pointer; transition:border-color .15s; }
        .file-wrap:hover { border-color:var(--brand); }
        .file-hint { font-size:.78rem; color:#94a3b8; margin-top:.2rem; }
        .img-preview { width:72px; height:72px; border-radius:10px; object-fit:cover; border:1px solid #e8ecf0; margin-top:.75rem; }
        .disease-box { border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
        .disease-search-wrap { padding:.65rem; border-bottom:1px solid #f1f5f9; position:relative; }
        .disease-search-wrap i { position:absolute; left:1.2rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.85rem; pointer-events:none; }
        .disease-search-wrap input { width:100%; border:1px solid #e2e8f0; border-radius:8px; padding:.5rem .75rem .5rem 2.1rem; font-size:.85rem; font-family:inherit; outline:none; background:#f8fafc; }
        .disease-search-wrap input:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(13,162,231,.1); background:#fff; }
        .disease-grid { display:grid; grid-template-columns:1fr 1fr; max-height:200px; overflow-y:auto; padding:.4rem; }
        .disease-opt { display:flex; align-items:center; gap:.5rem; padding:.5rem .6rem; border-radius:7px; cursor:pointer; font-size:.85rem; color:#374151; transition:background .1s; }
        .disease-opt:hover { background:#f1f5f9; }
        .disease-opt input[type=checkbox] { accent-color:var(--brand); width:15px; height:15px; cursor:pointer; flex-shrink:0; }
        .d-badge { font-size:.75rem; background:var(--brand-light); color:var(--brand-dark); border-radius:99px; padding:.15rem .6rem; font-weight:600; }
        .btn-cancel { border:1px solid #e2e8f0; background:#fff; border-radius:9px; padding:.55rem 1.1rem; font-size:.85rem; font-weight:500; color:#374151; cursor:pointer; font-family:inherit; transition:background .13s; }
        .btn-cancel:hover { background:#f8fafc; }
        .btn-save { background:var(--brand); color:#fff; border:none; border-radius:9px; padding:.55rem 1.25rem; font-size:.85rem; font-weight:600; cursor:pointer; font-family:inherit; transition:background .15s; }
        .btn-save:hover { background:var(--brand-dark); }
        .btn-del { background:#ef4444; color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-size:.825rem; font-weight:600; cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:.35rem; transition:background .13s; }
        .btn-del:hover { background:#dc2626; }
        ui-modal[data-flux-modal="department-form"] dialog {
            width:calc(100% - 2rem) !important;
            max-width:540px !important;
        }
        ui-modal[data-flux-modal="delete-department"] dialog {
            width:calc(100% - 2rem) !important;
            max-width:380px !important;
        }
        [data-flux-modal="department-form"] dialog,
        [data-flux-modal="delete-department"] dialog {
            margin:auto !important;
            inset:0 !important;
            max-height:calc(100vh - 2rem) !important;
            color-scheme:light !important;
            background:#fff !important;
            color:#111827 !important;
        }
        [x-cloak] { display:none !important; }
        @media(max-width:640px){
            .dept-wrap{padding:1rem;}
            .dept-header{flex-direction:column;align-items:flex-start;gap:1rem;}
            .dept-stats{grid-template-columns:1fr;}
            .table-toolbar{flex-direction:column;align-items:stretch;}
            .search-wrap{max-width:100%;}
            .disease-grid{grid-template-columns:1fr;}
        }
    </style>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- ── Header ── --}}
    <div class="dept-header">
        <div class="dept-header-left">
            <h1>Disease Departments</h1>
            <p>Group diseases into departments for easier management.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-add">
            <i class="fa-solid fa-plus"></i> Add Department
        </button>
    </div>

    {{-- ── Stats ── --}}
    <div class="dept-stats">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-building-columns"></i></div>
            <div>
                <div class="stat-label">Total Departments</div>
                <div class="stat-value">{{ $departments->total() }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-virus"></i></div>
            <div>
                <div class="stat-label">Available Diseases</div>
                <div class="stat-value">{{ \App\Models\Disease::where('is_active', true)->count() }}</div>
            </div>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="dept-table-card">
        <div class="table-toolbar">
            <div class="search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search departments..." class="search-input">
            </div>
            <select wire:model.live="perPage" class="page-select">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </select>
        </div>

        <div style="overflow-x:auto;">
            <table class="dept-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Diseases</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr wire:key="department-{{ $department->id }}">
                            <td>
                                <div class="dept-name-cell">
                                    @if ($department->department_image)
                                        <img src="{{ Storage::url($department->department_image) }}" alt="{{ $department->department_name }}" class="dept-avatar">
                                    @else
                                        <div class="dept-avatar-placeholder"><i class="fa-solid fa-building"></i></div>
                                    @endif
                                    <span class="dept-name">{{ $department->department_name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="disease-pills">
                                    @forelse (($department->diseases ?? []) as $diseaseId)
                                        @if ($diseaseNames->has($diseaseId))
                                            <span class="disease-pill">{{ $diseaseNames[$diseaseId] }}</span>
                                        @endif
                                    @empty
                                        <span class="no-diseases">No diseases selected</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <button type="button" wire:click="openEdit({{ $department->id }})" class="action-btn edit" title="Edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" wire:click="confirmDelete({{ $department->id }})" class="action-btn delete" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-building-circle-xmark"></i></div>
                                    <p class="empty-title">No disease departments found</p>
                                    <p class="empty-sub">Add a department to get started.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $departments->links() }}</div>
    </div>

    {{-- ══════════════════════════════════════════════════
         HIDDEN Flux modals — keep PHP working unchanged.
         Flux::modal()->show() / close() still fire.
         We intercept the modal-show browser event Flux
         dispatches and mirror state into our own overlay.
    ══════════════════════════════════════════════════ --}}
    {{-- ══════════════════════════════════════════════════
         FORM MODAL — Alpine-controlled, always centered
    ══════════════════════════════════════════════════ --}}
    <flux:modal name="department-form" class="p-0" wire:close="closeForm">
        <div x-data @click.outside="$wire.closeForm()" style="padding:1.75rem;">
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeForm" />

            <form wire:submit="save" style="display:flex;flex-direction:column;gap:1.1rem;">

                <div>
                    <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;letter-spacing:-.02em;">
                        {{ $departmentId ? 'Edit Disease Department' : 'Add Disease Department' }}
                    </h2>
                    <p style="font-size:.8rem;color:#64748b;margin-top:.2rem;">Enter the department details and select its diseases.</p>
                </div>

                <div>
                    <label class="m-label" for="dept-name">Department Name <span style="color:#ef4444">*</span></label>
                    <input id="dept-name" type="text" wire:model="departmentName" class="m-input" placeholder="e.g. Cardiology, Neurology...">
                    @error('departmentName') <p class="m-err">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="m-label" for="dept-img">
                        Department Image @if(!$departmentId)<span style="color:#ef4444">*</span>@endif
                    </label>
                    <label class="file-wrap" for="dept-img">
                        <span style="font-size:1.3rem;color:#94a3b8;"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                        <div>
                            <div style="font-size:.825rem;font-weight:500;color:#374151;">Click to upload image</div>
                            <div class="file-hint">JPG, PNG or WEBP — max 2 MB</div>
                        </div>
                    </label>
                    <input id="dept-img" type="file" wire:model="departmentImage" accept="image/*" style="display:none;">
                    @error('departmentImage') <p class="m-err">{{ $message }}</p> @enderror
                    @if ($departmentImage)
                        <img src="{{ $departmentImage->temporaryUrl() }}" class="img-preview" alt="Preview">
                    @elseif ($existingImage)
                        <img src="{{ Storage::url($existingImage) }}" class="img-preview" alt="Current image">
                    @endif
                </div>

                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.4rem;">
                        <label class="m-label" style="margin-bottom:0;">Diseases <span style="color:#ef4444">*</span></label>
                        <span class="d-badge">{{ count($selectedDiseases) }} selected</span>
                    </div>
                    <div class="disease-box">
                        <div class="disease-search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" wire:model.live.debounce.250ms="diseaseSearch" placeholder="Search diseases...">
                        </div>
                        <div class="disease-grid">
                            @forelse ($diseases as $disease)
                                <label wire:key="d-{{ $disease->id }}" class="disease-opt">
                                    <input type="checkbox" wire:model="selectedDiseases" value="{{ $disease->id }}">
                                    <span>{{ $disease->name }}</span>
                                </label>
                            @empty
                                <p style="grid-column:span 2;text-align:center;padding:1.5rem 0;font-size:.85rem;color:#94a3b8;">No diseases found.</p>
                            @endforelse
                        </div>
                    </div>
                    @error('selectedDiseases') <p class="m-err">{{ $message }}</p> @enderror
                    @error('selectedDiseases.*') <p class="m-err">{{ $message }}</p> @enderror
                </div>

                <div style="display:flex;justify-content:flex-end;gap:.65rem;padding-top:.75rem;border-top:1px solid #f1f5f9;">
                    <button type="button" wire:click="closeForm" class="btn-cancel">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save,departmentImage" class="btn-save">
                        <span wire:loading.remove wire:target="save">{{ $departmentId ? 'Update Department' : 'Add Department' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>

            </form>
        </div>
    </flux:modal>

    {{-- ══════════════════════════════════════════════════
         DELETE MODAL — Alpine-controlled, always centered
    ══════════════════════════════════════════════════ --}}
    <flux:modal name="delete-department" class="p-0" wire:close="closeDelete">
        <div x-data @click.outside="$wire.closeDelete()" style="padding:1.5rem;display:flex;flex-direction:column;gap:.8rem;">
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeDelete" />

            <div style="width:40px;height:40px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#ef4444;font-size:1rem;">
                <i class="fa-regular fa-trash-can"></i>
            </div>
            <div>
                <h2 style="font-size:.975rem;font-weight:700;color:#0f172a;">Delete this department?</h2>
                <p style="font-size:.825rem;color:#64748b;line-height:1.55;margin-top:.3rem;">
                    This department and its image will be permanently deleted. Disease records will remain unaffected.
                </p>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.6rem;padding-top:.25rem;">
                <button type="button" wire:click="closeDelete" class="btn-cancel" style="padding:.48rem .95rem;font-size:.825rem;">
                    <i class="fa-solid fa-xmark" style="margin-right:.3rem;"></i>Cancel
                </button>
                <button type="button" wire:click="destroy" class="btn-del">
                    <i class="fa-regular fa-trash-can"></i> Delete
                </button>
            </div>

        </div>
    </flux:modal>

    {{-- ══════════════════════════════════════════════════
         Bridge script:
         Intercepts Flux's internal modal-show / modal-close
         browser events and mirrors them to our Alpine overlays.
         PHP stays 100% unchanged.
    ══════════════════════════════════════════════════ --}}
</div>
