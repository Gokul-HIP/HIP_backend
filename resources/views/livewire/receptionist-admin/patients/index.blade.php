<div id="rp-root">

<style>
/* RP modal & form tokens-only styles as requested */
.rp-modal-backdrop{position:fixed;inset:0;z-index:9999;background:color-mix(in srgb,var(--text-color) 45%,transparent);backdrop-filter:blur(4px)}
.rp-modal-layer{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:24px;pointer-events:none}
.rp-modal{width:100%;max-width:640px;max-height:calc(100vh - 48px);display:flex;flex-direction:column;background:var(--card-bg);border-radius:calc(var(--radius-card) + 4px);box-shadow:0 24px 60px color-mix(in srgb,var(--text-color) 25%,transparent);overflow:hidden;pointer-events:auto}
.rp-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:20px 24px 16px;border-bottom:1px solid var(--border-color)}
.rp-modal-title{margin:0;font-size:18px;font-weight:700;color:var(--text-color)}
.rp-modal-sub{margin:2px 0 0;font-size:13px;color:var(--muted-color)}
.rp-x{width:34px;height:34px;border:0;border-radius:10px;background:transparent;color:var(--muted-color);cursor:pointer;font-size:20px;line-height:1}
.rp-x:hover{background:var(--primary-hover);color:var(--primary-color)}
.rp-modal-body{padding:20px 24px;overflow-y:auto;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 14px}
.rp-span2{grid-column:1 / -1}
.rp-section{grid-column:1 / -1;margin:6px 0 -4px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted-color)}
.rp-field{display:flex;flex-direction:column;gap:6px;min-width:0;position:relative}
.rp-label{font-size:12.5px;font-weight:600;color:var(--text-color)}
.rp-label .opt{font-weight:500;color:var(--muted-color)}
.rp-input,.rp-select,.rp-textarea{width:100%;box-sizing:border-box;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;outline:none!important;transition:border-color .15s,box-shadow .15s}
.rp-textarea{height:auto;min-height:76px;padding:10px 12px;resize:vertical}
.rp-input::placeholder,.rp-textarea::placeholder{color:var(--muted-color);opacity:.8}
.rp-input:focus,.rp-select:focus,.rp-textarea:focus{border-color:var(--primary-color)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-color) 18%,transparent)!important;outline:none!important}
.rp-input.is-invalid,.rp-select.is-invalid{border-color:var(--danger-color)!important}
.rp-error{font-size:12px;color:var(--danger-color)}
.rp-prefix-wrap{display:flex}
.rp-prefix{display:flex;align-items:center;padding:0 12px;border:1px solid var(--border-color);border-right:0;border-radius:10px 0 0 10px;background:var(--page-bg);color:var(--muted-color);font-size:14px;font-weight:600}
.rp-prefix-wrap .rp-input{border-radius:0 10px 10px 0}
.rp-seg{display:flex;gap:6px}
.rp-seg label{flex:1;position:relative;cursor:pointer}
.rp-seg input{position:absolute;opacity:0;inset:0;cursor:pointer}
.rp-seg span{display:flex;align-items:center;justify-content:center;height:42px;border:1px solid var(--border-color);border-radius:10px;font-size:13.5px;font-weight:600;color:var(--muted-color);background:var(--card-bg)}
.rp-seg input:checked + span{background:var(--primary-hover);border-color:var(--primary-color);color:var(--primary-color)}
.rp-mrn-row{display:flex;gap:8px}
.rp-mrn-row .rp-input{flex:1;min-width:0;font-weight:700;letter-spacing:.04em;font-variant-numeric:tabular-nums}
.rp-mrn-row .rp-mrn-btn{flex:0 0 auto;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font-size:13px;font-weight:600;cursor:pointer}
.rp-mrn-row .rp-mrn-btn:hover{border-color:var(--primary-color);color:var(--primary-color);background:var(--primary-hover)}
.rp-icon-btn{outline:none!important;width:34px;height:34px;border-radius:10px;border:1px solid var(--border-color);display:inline-flex;align-items:center;justify-content:center;background:transparent;color:var(--text-color);cursor:pointer}
.rp-icon-btn:focus-visible{box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-color) 20%,transparent)!important}
.rp-icon-btn:hover{background:var(--primary-hover)}
.rp-status{display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600}
.rp-status.ok{color:var(--success-color)} .rp-status.bad{color:var(--danger-color)} .rp-status.wait{color:var(--muted-color)}
.rp-combo-list{position:absolute;top:100%;left:0;right:0;z-index:10002;margin-top:4px;max-height:240px;overflow-y:auto;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:var(--shadow-card)}
.rp-menu{position:fixed;z-index:10000;min-width:208px;padding:6px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:0 12px 32px color-mix(in srgb,var(--text-color) 18%,transparent);font-family:var(--font-family,inherit)}
.rp-menu button{all:unset;box-sizing:border-box;display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border-radius:8px;font-family:inherit;font-size:13px;font-weight:500;line-height:1.2;color:var(--text-color);cursor:pointer}
.rp-menu button:hover,.rp-menu button:focus-visible{background:var(--primary-hover);color:var(--primary-color)}
.rp-menu button svg{width:16px;height:16px;flex:none;stroke:currentColor;fill:none;stroke-width:1.8}
.rp-menu hr{border:0;height:1px;margin:6px 4px;background:var(--border-color)}
.rp-menu button.is-danger{color:var(--danger-color)}
.rp-menu button.is-danger:hover{background:var(--danger-soft);color:var(--danger-color)}
.rp-combo-item{display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;padding:10px 12px;border:0;background:transparent;text-align:left;cursor:pointer;font:inherit}
.rp-combo-item:hover,.rp-combo-item.is-active{background:var(--primary-hover)}
.rp-combo-name{font-size:14px;font-weight:600;color:var(--text-color)}
.rp-combo-phone{font-size:13px;color:var(--muted-color);font-variant-numeric:tabular-nums}
.rp-combo-empty{padding:12px;font-size:13px;color:var(--muted-color)}
.rp-chip{display:flex;align-items:center;justify-content:space-between;gap:10px;height:42px;padding:0 6px 0 12px;border:1px solid var(--primary-color);border-radius:10px;background:var(--primary-hover);color:var(--primary-color);font-size:14px;font-weight:600}
.rp-chip button{width:28px;height:28px;border:0;border-radius:8px;background:transparent;color:inherit;cursor:pointer}
.rp-modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid var(--border-color);background:var(--card-bg);position:sticky;bottom:0;z-index:5}
.rp-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 20px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:1px solid transparent}
.rp-btn-ghost{background:var(--card-bg);border-color:var(--border-color);color:var(--text-color)}
.rp-btn-ghost:hover{background:var(--page-bg)}
.rp-btn-primary{background:var(--button-color);color:var(--card-bg);box-shadow:var(--shadow-card)}
.rp-btn-primary:hover{background:var(--button-hover)}
.rp-btn-primary:disabled{opacity:.5;cursor:not-allowed}
.rp-drawer-backdrop{position:fixed;inset:0;z-index:9999;background:color-mix(in srgb,var(--text-color) 45%,transparent);backdrop-filter:blur(3px)}
.rp-drawer{position:fixed;top:0;right:0;bottom:0;z-index:10000;isolation:isolate;width:min(460px,100vw);display:flex;flex-direction:column;background:var(--page-bg);box-shadow:-24px 0 60px color-mix(in srgb,var(--text-color) 22%,transparent);animation:rp-slide-in .22s ease-out}
@keyframes rp-slide-in{from{transform:translateX(24px);opacity:0}to{transform:none;opacity:1}}
.rp-drawer-head{position:relative;padding:22px 24px 18px;background:var(--card-bg);border-bottom:1px solid var(--border-color)}
.rp-drawer-close{position:absolute;top:14px;right:14px;width:36px;height:36px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color)}
.rp-drawer-close:hover{background:var(--primary-hover);color:var(--text-color)}
.rp-drawer-avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--button-color);color:var(--card-bg);font-size:22px;font-weight:700}
.rp-drawer-name{margin:12px 0 2px;font-size:19px;font-weight:700;color:var(--text-color)}
.rp-drawer-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.rp-drawer-body{flex:1;overflow-y:auto;padding:16px 24px;display:flex;flex-direction:column;gap:14px}
.rp-card{background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card);padding:14px 16px}
.rp-card-title{margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted-color)}
.rp-kv{display:grid;grid-template-columns:130px 1fr;gap:8px 12px;font-size:13.5px}
.rp-kv dt{color:var(--muted-color)} .rp-kv dd{margin:0;color:var(--text-color);font-weight:500;word-break:break-word}
.rp-drawer-foot{display:flex;gap:10px;padding:14px 24px;background:var(--card-bg);border-top:1px solid var(--border-color)}
.rp-drawer-foot .rp-btn{flex:1}
.rp-pill{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:600;line-height:1.2}
.rp-pill.is-primary{background:var(--primary-hover);color:var(--primary-color)} .rp-pill.is-info{background:var(--info-soft);color:var(--info-color)} .rp-pill.is-success{background:var(--success-soft);color:var(--success-color)} .rp-pill.is-muted{background:var(--page-bg);color:var(--muted-color);border:1px solid var(--border-color)}
.rp-pill.is-doctor{background:var(--primary-hover);color:var(--primary-color)}.rp-pill.is-second{background:var(--info-soft);color:var(--info-color)}.rp-pill.is-diagnostic{background:var(--success-soft);color:var(--success-color)}
.rp-member-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-top:1px solid var(--border-color)} .rp-member-row:first-of-type{border-top:0}
.rp-member-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--button-color);color:var(--card-bg);font-size:11px;font-weight:700;flex:none}
.rp-member-main{min-width:0;flex:1}.rp-member-name{font-size:13.5px;font-weight:700;color:var(--text-color);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.rp-member-side{color:var(--muted-color);font-size:12.5px;white-space:nowrap}
.rp-muted-line{color:var(--muted-color);font-size:13.5px}
.rp-bookings-modal{max-width:760px}
.rp-booking-body{max-height:calc(100vh - 220px);overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:16px 24px}
.rp-booking-filters{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.rp-booking-chips{display:flex;gap:8px;flex-wrap:wrap}
.rp-filter-chip{border:1px solid var(--border-color);border-radius:999px;background:var(--card-bg);color:var(--muted-color);height:34px;padding:0 12px;font-size:12.5px;font-weight:700;cursor:pointer}
.rp-filter-chip.is-all{border-color:var(--button-color);background:var(--button-color);color:var(--card-bg)}
.rp-filter-chip.is-doctor{border-color:var(--primary-color);background:var(--primary-hover);color:var(--primary-color)}
.rp-filter-chip.is-second{border-color:var(--info-color);background:var(--info-soft);color:var(--info-color)}
.rp-filter-chip.is-diagnostic{border-color:var(--success-color);background:var(--success-soft);color:var(--success-color)}
.rp-booking-tools{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.rp-booking-select{height:36px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font-size:13px;padding:0 10px}
.rp-link-button{border:0;background:transparent;color:var(--primary-color);font-size:13px;font-weight:700;cursor:pointer}
.rp-booking-card{position:relative;display:flex;flex-direction:column;gap:8px;padding-left:18px}
.rp-booking-card:before{content:"";position:absolute;left:0;top:12px;bottom:12px;width:4px;border-radius:999px;background:var(--border-color)}
.rp-booking-card.is-doctor:before{background:var(--primary-color)}.rp-booking-card.is-second:before{background:var(--info-color)}.rp-booking-card.is-diagnostic:before{background:var(--success-color)}
.rp-booking-top,.rp-booking-meta{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.rp-booking-ref{color:var(--muted-color);font-size:12.5px;font-weight:700}
.rp-booking-title{font-size:14.5px;font-weight:600;color:var(--text-color)}
.rp-booking-meta{justify-content:flex-start;color:var(--muted-color);font-size:13px}
.rp-booking-meta svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:1.8}.rp-meta-item{display:inline-flex;align-items:center;gap:5px}
.rp-status-confirmed{background:var(--info-soft);color:var(--info-color)}.rp-status-completed{background:var(--success-soft);color:var(--success-color)}.rp-status-pending{background:var(--warning-soft);color:var(--warning-color)}.rp-status-missed,.rp-status-cancelled{background:var(--danger-soft);color:var(--danger-color)}.rp-status-muted{background:var(--page-bg);color:var(--muted-color);border:1px solid var(--border-color)}
.rp-page-stack{display:flex;flex-direction:column;gap:12px}
.rp-page-header{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.rp-page-title{margin:0;color:var(--text-color);font-size:24px;font-weight:700}
.rp-page-subtitle{margin:4px 0 0;color:var(--muted-color);font-size:14px}
.rp-add-btn{height:42px;padding:0 16px;border:0;border-radius:var(--radius-card);background:var(--button-color);box-shadow:var(--shadow-card);color:var(--card-bg);font-size:14px;font-weight:700;cursor:pointer}
.rp-add-btn:hover{background:var(--button-hover)}
.rp-filter-card{display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:12px;border:1px solid var(--border-color);border-radius:var(--radius-card);background:var(--card-bg)}
.rp-filter-search{position:relative;flex:1 1 280px;min-width:0}
.rp-search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted-color);pointer-events:none}
.rp-filter-control{box-sizing:border-box;width:100%;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;outline:none!important;transition:border-color .15s,box-shadow .15s}
.rp-filter-search .rp-filter-control{padding-left:38px}
.rp-filter-control:focus{border-color:var(--primary-color)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-color) 18%,transparent)!important;outline:none!important}
.rp-filter-hospital{flex:1 1 160px;width:160px;min-width:140px;max-width:200px}
.rp-filter-relationship{flex:1 1 150px;width:150px;min-width:130px;max-width:180px}
.rp-filter-status{flex:1 1 140px;width:140px;min-width:130px;max-width:160px}
.rp-filter-reset{flex:0 0 auto;min-width:76px}
.rp-status-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;line-height:1.2;white-space:nowrap}
.rp-status-pill.is-active{background:var(--success-soft);color:var(--success-color)}
.rp-status-pill.is-inactive{background:var(--page-bg);color:var(--muted-color);border:1px solid var(--border-color)}
.rp-status-dot{width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.rp-created-by{margin-top:2px;color:var(--muted-color);font-size:12px}
.rp-record-note{color:var(--muted-color);font-weight:500}
.rp-record-aside{margin-left:8px}
.rp-patient-row.is-inactive > td:not(.rp-sticky-actions){opacity:.7}
.rp-table-wrap{overflow-x:auto}
.rp-table-card{overflow:visible;border:1px solid var(--border-color);border-radius:var(--radius-card);background:var(--card-bg);box-shadow:var(--shadow-card)}
.rp-patients-table{width:100%;min-width:1280px;border-collapse:separate;border-spacing:0;color:var(--text-color);font-size:14px}
.rp-patients-table thead{background:var(--primary-hover)}
.rp-patients-table th{height:44px;padding:0 14px;color:var(--muted-color);font-size:11px;font-weight:700;letter-spacing:.06em;text-align:left;text-transform:uppercase;white-space:nowrap}
.rp-patients-table td{height:64px;padding:10px 14px;border-top:1px solid var(--border-color);vertical-align:middle}
.rp-patient-row{background:var(--card-bg);transition:background-color .15s}
.rp-patient-row:hover{background:var(--page-bg)}
.rp-patients-table .rp-patient-name{min-width:220px}
.rp-nowrap{white-space:nowrap;font-variant-numeric:tabular-nums}
.rp-mrn-value{font-weight:600}
.rp-added-date{color:var(--muted-color)}
.rp-empty-value{color:var(--muted-color)}
.rp-demographics{margin-top:2px;color:var(--muted-color);font-size:12px}
.rp-patient-unnamed{color:var(--muted-color);font-style:italic}
.rp-table-empty{padding:36px 16px!important;text-align:center}
.rp-empty-title{color:var(--text-color);font-size:15px;font-weight:700}
.rp-empty-copy{margin-top:4px;color:var(--muted-color);font-size:13px}
.rp-table-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-top:1px solid var(--border-color)}
.rp-footer-left{display:flex;align-items:center;gap:12px;color:var(--muted-color);font-size:13px;white-space:nowrap}
.rp-per-page{width:auto;min-width:78px;height:36px;padding:0 8px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:13px;outline:none!important}
.rp-per-page:focus{border-color:var(--primary-color)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-color) 18%,transparent)!important;outline:none!important}
.rp-sticky-actions{position:sticky;right:0;background:var(--card-bg);white-space:nowrap;text-align:right}
.rp-patients-table thead .rp-sticky-actions{background:var(--primary-hover)}
.rp-patient-row:hover .rp-sticky-actions{background:var(--page-bg)}
.rp-pagination{display:flex;min-width:0}
.rp-pagination-list{display:flex;align-items:center;gap:6px;margin:0;padding:0;list-style:none}
.rp-page-button{box-sizing:border-box;display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font-size:13px;font-weight:600;text-decoration:none;transition:background-color .15s,border-color .15s,color .15s}
.rp-page-button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.rp-page-button:hover{background:var(--primary-hover)}
.rp-page-button.is-active{border-color:var(--button-color);background:var(--button-color);color:var(--card-bg)}
.rp-page-button.is-disabled{color:var(--muted-color);cursor:not-allowed;opacity:.55}
.rp-page-button.is-disabled:hover{background:var(--card-bg)}
.rp-page-gap{display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;color:var(--muted-color);font-size:13px}
@media (max-width:640px){.rp-modal-layer{align-items:flex-end;padding:0}.rp-modal{max-width:none;max-height:92vh;border-radius:20px 20px 0 0}.rp-modal-body{grid-template-columns:1fr;padding:16px}.rp-modal-head,.rp-modal-foot{padding-left:16px;padding-right:16px}.rp-btn{flex:1}}
@media (max-width:640px){.rp-drawer{width:100vw}.rp-drawer-head,.rp-drawer-body,.rp-drawer-foot{padding-left:16px;padding-right:16px}.rp-kv{grid-template-columns:1fr}.rp-booking-body{padding:16px}.rp-bookings-modal{max-width:none}.rp-booking-tools{width:100%}.rp-booking-select{flex:1;min-width:0}}
@media (max-width:640px){.rp-page-header{align-items:flex-start}.rp-add-btn{width:100%}.rp-filter-card{align-items:stretch}.rp-filter-search,.rp-filter-hospital,.rp-filter-relationship,.rp-filter-status,.rp-filter-reset{flex:1 1 100%;width:100%;min-width:0;max-width:none}.rp-table-footer{flex-direction:column;justify-content:center}.rp-footer-left{justify-content:center;flex-wrap:wrap}.rp-pagination-list{justify-content:center}}
</style>
<div class="rp-page-stack" style="padding:4px 0;">
    <header class="rp-page-header">
        <div>
            <h1 class="rp-page-title">Patients</h1>
            <p class="rp-page-subtitle">Registered patients and their family members</p>
        </div>
        <button wire:click="openAddModal" class="rp-add-btn">Add new member</button>
    </header>

    <div class="rp-filter-card">
        <label class="rp-filter-search">
            <i class="rp-search-icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, phone, MRN ID, member ID..." aria-label="Search patients" class="rp-filter-control">
        </label>
        <select wire:model.live="hospitalFilter" aria-label="Filter by hospital" class="rp-filter-control rp-filter-hospital">
            <option value="">All hospitals</option>
            @foreach($hospitals as $hospital)
                <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="relationshipFilter" aria-label="Filter by relationship" class="rp-filter-control rp-filter-relationship">
            <option value="all">All relationships</option>
            <option value="Self">Self</option>
            <option value="Father">Father</option>
            <option value="Mother">Mother</option>
            <option value="Spouse">Spouse</option>
            <option value="Child">Child</option>
            <option value="Other">Other</option>
        </select>
        <select wire:model.live="statusFilter" aria-label="Filter by status" class="rp-filter-control rp-filter-status">
            <option value="all">All status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button type="button" wire:click="clearFilters" class="rp-btn rp-btn-ghost rp-filter-reset">Reset</button>
    </div>

    <div class="rp-table-wrap">
        <div class="rp-table-card">
            <table class="rp-patients-table">
                <thead>
                    <tr>
                        <th class="rp-patient-name">Patient</th>
                        <th>MRN ID</th>
                        <th>Member ID</th>
                        <th>Phone</th>
                        <th>Relationship</th>
                        <th>Primary account</th>
                        <th>Status</th>
                        <th>Hospital</th>
                        <th>Blood group</th>
                        <th>Created</th>
                        <th class="rp-sticky-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        @php
                            $patientName = $patient->display_name;
                            $hasResolvedName = $patientName !== 'Unnamed patient';
                            $genderLabel = filled($patient->gender) ? ucwords(strtolower(trim($patient->gender))) : null;
                            $age = $patient->dob ? \Illuminate\Support\Carbon::parse($patient->dob)->age : null;
                            $ageLabel = $age !== null ? ($genderLabel ? (string) $age : $age . ' yrs') : null;
                            $demographicParts = array_filter([$genderLabel, $ageLabel]);
                            $relationshipLabel = strtolower(trim((string) $patient->relationship)) === 'other'
                                ? trim((string) $patient->relationship_other)
                                : (filled($patient->relationship) ? ucwords(strtolower(trim($patient->relationship))) : '');
                            $isPrimary = (bool) $patient->is_primary || (string) $patient->parent_id === (string) $patient->id;
                            $parentName = $patient->parent ? $patient->parent->display_name : '';
                            $patientIsActive = (bool) ($patient->is_active ?? true);
                        @endphp
                        <tr wire:key="rec-person-{{ $patient->id }}" class="rp-patient-row {{ $patientIsActive ? '' : 'is-inactive' }}">
                            <td class="rp-patient-name">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:var(--button-color);">
                                        {{ $patient->initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium {{ $hasResolvedName ? '' : 'rp-patient-unnamed' }}">{{ $patientName }}</div>
                                        @if($demographicParts !== [])
                                            <div class="rp-demographics">{{ implode(' · ', $demographicParts) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="rp-nowrap rp-mrn-value">{{ $patient->mrn ?: '' }}@if(!$patient->mrn)<span class="rp-empty-value">—</span>@endif</td>
                            <td class="rp-nowrap">{{ $patient->hipUser?->hip_id ?: '' }}@if(!$patient->hipUser?->hip_id)<span class="rp-empty-value">—</span>@endif</td>
                            <td class="rp-nowrap">{{ $patient->mobile ? '+91 ' . $patient->mobile : '' }}@if(!$patient->mobile)<span class="rp-empty-value">—</span>@endif</td>
                            <td>
                                @if($relationshipLabel !== '')
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium" style="background:var(--primary-hover);color:var(--primary-color);">{{ $relationshipLabel }}</span>
                                @else
                                    <span class="rp-empty-value">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isPrimary)
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium" style="background:var(--primary-hover);color:var(--primary-color);">Primary</span>
                                @elseif($patient->parent && $parentName !== '')
                                    <div class="font-medium">{{ $parentName }}</div>
                                    <div class="rp-demographics rp-nowrap">{{ $patient->parent->mobile ? '+91 ' . $patient->parent->mobile : '—' }}</div>
                                @else
                                    <span class="rp-empty-value">—</span>
                                @endif
                            </td>
                            <td>
                                @if($patientIsActive)
                                    <span class="rp-status-pill is-active"><span class="rp-status-dot" aria-hidden="true"></span>Active</span>
                                @else
                                    <span class="rp-status-pill is-inactive"><span class="rp-status-dot" aria-hidden="true"></span>Inactive</span>
                                @endif
                            </td>
                            <td>{{ $hospitalsList[$patient->hospital_id] ?? '' }}@if(empty($hospitalsList[$patient->hospital_id] ?? null))<span class="rp-empty-value">—</span>@endif</td>
                            <td class="rp-nowrap">{{ $patient->blood_group ?: '' }}@if(!$patient->blood_group)<span class="rp-empty-value">—</span>@endif</td>
                            <td class="rp-nowrap">
                                <div>{{ $patient->created_label ?: '' }}@if(!$patient->created_label)<span class="rp-empty-value">—</span>@endif</div>
                                <div class="rp-created-by">{{ $patient->creator_name ? 'by '.$patient->creator_name : '—' }}</div>
                            </td>
                            <td class="rp-sticky-actions">
                                <button class="rp-icon-btn" aria-haspopup="menu" aria-expanded="false" aria-label="Actions for {{ $patientName }}" onclick="window.__rpOpenMenu(this, 'person:{{ $patient->id }}', {{ $patientIsActive ? 'true' : 'false' }})">⋯</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="rp-table-empty">
                                <div class="rp-empty-title">No patients found</div>
                                <div class="rp-empty-copy">Try changing the filters</div>
                                <button type="button" wire:click="clearFilters" class="rp-btn rp-btn-ghost mt-3">Reset</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <footer class="rp-table-footer">
                <div class="rp-footer-left">
                    <span>Showing {{ $patients->firstItem() ?? 0 }}–{{ $patients->lastItem() ?? 0 }} of {{ $patients->total() }} patients</span>
                    <label class="inline-flex items-center gap-2">
                        <span>Per page</span>
                        <select wire:model.live="perPage" class="rp-per-page" aria-label="Patients per page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
                {{ $patients->links('pagination.rp') }}
            </footer>
        </div>
    </div>

    <!-- Details drawer -->
    @if($showDetailsDrawer)
    <template x-teleport="body">
        <div x-data="{open: @entangle('showDetailsDrawer')}" x-effect="open ? document.body.classList.add('rp-modal-open') : document.body.classList.remove('rp-modal-open')" x-on:keydown.escape.window="@this.set('showDetailsDrawer', false)">
            <div class="rp-drawer-backdrop" @click="@this.set('showDetailsDrawer', false)"></div>
            <aside class="rp-drawer" role="dialog" aria-modal="true" aria-label="Patient details" @click.stop>
                <div class="rp-drawer-head">
                    <button class="rp-x rp-drawer-close" wire:click="$set('showDetailsDrawer', false)" aria-label="Close">&times;</button>
                    <div class="rp-drawer-avatar">{{ $detailsPerson['initials'] ?? '?' }}</div>
                    <h3 class="rp-drawer-name">{{ $detailsPerson['name'] ?? 'Unnamed patient' }}</h3>
                    <div class="rp-drawer-chips">
                        @if(!empty($detailsPerson['mrn']))
                            <span class="rp-pill is-primary">{{ $detailsPerson['mrn'] }}</span>
                        @else
                            <span class="rp-pill is-muted">No MRN</span>
                        @endif
                        @if(!empty($detailsPerson['member_id']))
                            <span class="rp-pill is-info">{{ $detailsPerson['member_id'] }}</span>
                        @endif
                        @if(!empty($detailsPerson['relationship']))
                            <span class="rp-pill is-muted">{{ $detailsPerson['relationship'] }}</span>
                        @endif
                        @if(!empty($detailsPerson['is_primary']))
                            <span class="rp-pill is-success">Primary account</span>
                        @endif
                        @if(!empty($detailsPerson['is_active']))
                            <span class="rp-status-pill is-active"><span class="rp-status-dot" aria-hidden="true"></span>Active</span>
                        @else
                            <span class="rp-status-pill is-inactive"><span class="rp-status-dot" aria-hidden="true"></span>Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="rp-drawer-body">
                    <section class="rp-card">
                        <h4 class="rp-card-title">Contact</h4>
                        <dl class="rp-kv">
                            <dt>Mobile</dt><dd>{!! $detailsPerson['mobile'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                            <dt>Email</dt><dd>{!! $detailsPerson['email'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                        </dl>
                    </section>

                    <section class="rp-card">
                        <h4 class="rp-card-title">Personal</h4>
                        <dl class="rp-kv">
                            <dt>Gender</dt><dd>{!! $detailsPerson['gender'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                            <dt>Date of birth</dt><dd>{!! !empty($detailsPerson['dob']) ? e($detailsPerson['dob'] . (!empty($detailsPerson['age']) ? ' - ' . $detailsPerson['age'] . ' yrs' : '')) : '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                            <dt>Blood group</dt><dd>{!! $detailsPerson['blood_group'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                            <dt>Hospital</dt><dd>{!! $detailsPerson['hospital'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                        </dl>
                    </section>

                    <section class="rp-card">
                        <h4 class="rp-card-title">Relationship</h4>
                        <dl class="rp-kv">
                            <dt>Relationship</dt><dd>{!! !empty($detailsPerson['relationship']) ? e($detailsPerson['relationship']) : '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                            <dt>Primary account</dt>
                            <dd>
                                @if(!empty($detailsPerson['is_primary']))
                                    <span class="rp-empty-value">This is the primary account holder</span>
                                @else
                                    {!! $detailsPerson['parent'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}
                                @endif
                            </dd>
                        </dl>
                    </section>

                    <section class="rp-card">
                        <h4 class="rp-card-title">Emergency contact</h4>
                        @if(empty($detailsPerson['emergency_contact_name']) && empty($detailsPerson['emergency_contact_number']))
                            <div class="rp-muted-line">No emergency contact added</div>
                        @else
                            <dl class="rp-kv">
                                <dt>Name</dt><dd>{!! $detailsPerson['emergency_contact_name'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                                <dt>Number</dt><dd>{!! $detailsPerson['emergency_contact_number'] ?? '<span class="rp-empty-value">&mdash;</span>' !!}</dd>
                            </dl>
                        @endif
                    </section>

                    <section class="rp-card">
                        <h4 class="rp-card-title">Address</h4>
                        @if(!empty($detailsPerson['address']))
                            <div>{{ $detailsPerson['address'] }}</div>
                        @else
                            <div class="rp-muted-line">No address added</div>
                        @endif
                    </section>

                    <section class="rp-card">
                        <h4 class="rp-card-title">Record</h4>
                        <dl class="rp-kv">
                            <dt>Status</dt>
                            <dd>
                                @if(!empty($detailsPerson['is_active']))
                                    <span class="rp-status-pill is-active"><span class="rp-status-dot" aria-hidden="true"></span>Active</span>
                                @else
                                    <span class="rp-status-pill is-inactive"><span class="rp-status-dot" aria-hidden="true"></span>Inactive</span>
                                    @if(!empty($detailsPerson['deactivated_date']))
                                        <span class="rp-record-note rp-record-aside">Inactive since {{ $detailsPerson['deactivated_date'] }}</span>
                                    @endif
                                @endif
                            </dd>
                            <dt>Created</dt>
                            <dd>
                                {{ $detailsPerson['created_at'] ?? '—' }}
                                @if(!empty($detailsPerson['created_relative']))
                                    <span class="rp-record-note rp-record-aside">{{ $detailsPerson['created_relative'] }}</span>
                                @endif
                            </dd>
                            <dt>Created by</dt>
                            <dd>
                                @if(!empty($detailsPerson['creator_name']))
                                    {{ $detailsPerson['creator_name'] }}
                                @else
                                    <span class="rp-record-note">Not recorded</span>
                                @endif
                            </dd>
                            <dt>Last updated</dt>
                            <dd>
                                {{ $detailsPerson['updated_at'] ?? '—' }}
                                @if(!empty($detailsPerson['updated_relative']))
                                    <span class="rp-record-note rp-record-aside">{{ $detailsPerson['updated_relative'] }}</span>
                                @endif
                            </dd>
                            <dt>Updated by</dt>
                            <dd>{{ $detailsPerson['updater_name'] ?? '—' }}</dd>
                        </dl>
                    </section>

                    <section class="rp-card">
                        <h4 class="rp-card-title">Linked family members</h4>
                        @forelse($detailsPerson['members'] ?? [] as $m)
                            <div class="rp-member-row">
                                <div class="rp-member-avatar">{{ $m['initials'] ?? '?' }}</div>
                                <div class="rp-member-main">
                                    <div class="rp-member-name">{{ $m['name'] ?? 'Unnamed patient' }}</div>
                                    @if(!empty($m['relationship']))<span class="rp-pill is-muted">{{ $m['relationship'] }}</span>@endif
                                </div>
                                <div class="rp-member-side">{{ $m['mobile'] ?? '' }}</div>
                            </div>
                        @empty
                            <div class="rp-muted-line">No linked family members</div>
                        @endforelse
                    </section>
                </div>

                <div class="rp-drawer-foot">
                    <button class="rp-btn rp-btn-primary" wire:click="editFromDetails">Edit patient</button>
                    <button class="rp-btn rp-btn-ghost" wire:click="bookingsFromDetails">Booking details</button>
                </div>
            </aside>
        </div>
    </template>
    @endif
<script>
(function(){
    // Simple action menu implementation teleported to body
    window.__rpOpenMenu = function(button, key, isActive){
        // close existing
        if(window.__rpMenuEl){ window.__rpMenuEl.remove(); window.__rpMenuEl=null; }
        const rect = button.getBoundingClientRect();
        const menu = document.createElement('div');
        const active = isActive === true || isActive === 'true' || isActive === 1 || isActive === '1';
        const toggleLabel = active ? 'Deactivate patient' : 'Activate patient';
        menu.className = 'rp-menu';
        menu.style.right = (window.innerWidth - rect.right) + 'px';
        menu.style.top = (rect.bottom + 6) + 'px';
        menu.innerHTML = `
            <button data-action="details"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="3"></circle><path d="M2 12a10 10 0 0 1 20 0"></path></svg><span>View patient details</span></button>
            <button data-action="bookings"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path></svg><span>Booking details</span></button>
            <button data-action="edit"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 21v-3l11-11 3 3L6 21H3z"></path></svg><span>Edit patient</span></button>
            <button data-action="toggle"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2v10"></path><path d="M18.4 6.6a8 8 0 1 1-12.8 0"></path></svg><span>${toggleLabel}</span></button>
            <hr />
            <button class="is-danger" data-action="delete"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 6h18"></path><path d="M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg><span>Delete patient</span></button>
        `;

        document.body.appendChild(menu);
        window.__rpMenuEl = menu;

        function close(){ if(window.__rpMenuEl){ window.__rpMenuEl.remove(); window.__rpMenuEl=null; document.removeEventListener('mousedown', onDoc); window.removeEventListener('scroll', close); window.removeEventListener('resize', close); } }
        function onDoc(e){ if(!menu.contains(e.target) && e.target!==button) close(); }
        document.addEventListener('mousedown', onDoc);
        window.addEventListener('scroll', close, {once:true});
        window.addEventListener('resize', close, {once:true});

        menu.addEventListener('click', function(ev){
            // find the button element clicked (may be the svg/span inside)
            let btn = ev.target;
            while (btn && !btn.getAttribute) btn = btn.parentElement;
            while (btn && !btn.getAttribute('data-action')) btn = btn.closest('[data-action]');
            const act = btn ? btn.getAttribute('data-action') : null;
            if(!act) return;
            close();
            const id = key.replace('person:','');
            if(act==='details') { Livewire.dispatch('openDetails', {id}) }
            if(act==='bookings') { Livewire.dispatch('openBookings', {id}) }
            if(act==='edit') { Livewire.dispatch('openEditModal', {id}) }
            if(act==='toggle') { Livewire.dispatch('toggleActive', {id}) }
            if(act==='delete') { Livewire.dispatch('confirmDelete', {id}) }
        });
    }
})();
</script>

<script>
// body scroll lock helper
window.addEventListener('clearModalBodyLock', ()=>{ document.body.classList.remove('rp-modal-open'); });
document.addEventListener('livewire:load', ()=>{
    Livewire.hook('message.processed', (message, component) => {
        // clear modal lock after navigation/morphs if necessary
        if (!document.querySelector('.rp-modal-backdrop') && !document.querySelector('.rp-drawer-backdrop')) {
            document.body.classList.remove('rp-modal-open');
        }
    });
});
</script>

    <!-- Bookings modal -->
    @if($showBookingsModal)
    <template x-teleport="body">
        <div x-data="{
                open: @entangle('showBookingsModal'),
                rows: @js($bookingsList),
                rpBkType: 'All',
                rpBkStatus: 'all',
                rpBkSort: 'desc',
                typeCount(type) { return type === 'All' ? this.rows.length : this.rows.filter(row => row.type === type).length; },
                statuses() { return [...new Set(this.rows.map(row => row.status).filter(Boolean))].sort(); },
                statusLabel(value) { const row = this.rows.find(item => item.status === value); return row ? row.status_label : value; },
                filteredRows() {
                    let selected = this.rows.filter(row => (this.rpBkType === 'All' || row.type === this.rpBkType) && (this.rpBkStatus === 'all' || row.status === this.rpBkStatus));
                    selected.sort((a, b) => {
                        const av = a.sort_ts || 0;
                        const bv = b.sort_ts || 0;
                        if (av === bv) return 0;
                        return this.rpBkSort === 'desc' ? bv - av : av - bv;
                    });
                    return selected;
                },
                clear() { this.rpBkType = 'All'; this.rpBkStatus = 'all'; this.rpBkSort = 'desc'; },
                typeClass(type) { return type === 'Doctor' ? 'is-doctor' : (type === 'Second Opinion' ? 'is-second' : (type === 'Diagnostic' ? 'is-diagnostic' : '')); },
                statusClass(status) { return ['confirmed','completed','pending','missed','cancelled'].includes(status) ? 'rp-status-' + status : 'rp-status-muted'; }
            }"
            x-effect="open ? document.body.classList.add('rp-modal-open') : document.body.classList.remove('rp-modal-open')"
            x-on:keydown.escape.window="@this.set('showBookingsModal', false)">
            <div class="rp-modal-backdrop"></div>
            <div class="rp-modal-layer">
            <div class="rp-modal rp-bookings-modal" role="dialog" aria-modal="true" @click.stop>
                <div class="rp-modal-head">
                    <div>
                        <h3 class="rp-modal-title">Bookings</h3>
                        <p class="rp-modal-sub">
                            {{ $bookingPerson['name'] ?? 'Unnamed patient' }}
                            @if(!empty($bookingPerson['mrn'])) - {{ $bookingPerson['mrn'] }}@endif
                            @if(!empty($bookingPerson['member_id'])) - {{ $bookingPerson['member_id'] }}@endif
                        </p>
                    </div>
                    <button class="rp-x" wire:click="$set('showBookingsModal', false)" aria-label="Close">&times;</button>
                </div>

                <div class="rp-booking-body">
                    <div class="rp-booking-chips" role="group" aria-label="Filter bookings by type">
                        <button type="button" class="rp-filter-chip" :class="rpBkType === 'All' ? 'is-all' : ''" @click="rpBkType = 'All'">All <span x-text="typeCount('All')"></span></button>
                        <button type="button" class="rp-filter-chip" :class="rpBkType === 'Doctor' ? 'is-doctor' : ''" @click="rpBkType = 'Doctor'">Doctor <span x-text="typeCount('Doctor')"></span></button>
                        <button type="button" class="rp-filter-chip" :class="rpBkType === 'Second Opinion' ? 'is-second' : ''" @click="rpBkType = 'Second Opinion'">Second Opinion <span x-text="typeCount('Second Opinion')"></span></button>
                        <button type="button" class="rp-filter-chip" :class="rpBkType === 'Diagnostic' ? 'is-diagnostic' : ''" @click="rpBkType = 'Diagnostic'">Diagnostic <span x-text="typeCount('Diagnostic')"></span></button>
                    </div>

                    <div class="rp-booking-filters">
                        <div class="rp-booking-tools">
                            <select class="rp-booking-select" x-model="rpBkStatus" aria-label="Filter bookings by status">
                                <option value="all">All status</option>
                                <template x-for="status in statuses()" :key="status">
                                    <option :value="status" x-text="statusLabel(status)"></option>
                                </template>
                            </select>
                            <button type="button" class="rp-btn rp-btn-ghost" @click="rpBkSort = rpBkSort === 'desc' ? 'asc' : 'desc'" x-text="rpBkSort === 'desc' ? 'Newest first' : 'Oldest first'"></button>
                            <button type="button" class="rp-link-button" @click="clear">Clear</button>
                        </div>
                        <div class="rp-muted-line">Showing <span x-text="filteredRows().length"></span> of <span x-text="rows.length"></span></div>
                    </div>

                    <template x-if="rows.length === 0">
                        <div class="rp-card rp-muted-line">No bookings yet</div>
                    </template>

                    <template x-if="rows.length > 0 && filteredRows().length === 0">
                        <div class="rp-card">
                            <div class="rp-empty-title">No bookings match these filters</div>
                            <button type="button" class="rp-btn rp-btn-ghost mt-3" @click="clear">Clear</button>
                        </div>
                    </template>

                    <template x-for="booking in filteredRows()" :key="booking.type + '-' + booking.ref">
                        <article class="rp-card rp-booking-card" :class="typeClass(booking.type)">
                            <div class="rp-booking-top">
                                <div class="rp-booking-tools">
                                    <span class="rp-pill" :class="typeClass(booking.type)" x-text="booking.type"></span>
                                    <span class="rp-booking-ref" x-text="booking.ref"></span>
                                </div>
                                <span class="rp-pill" :class="statusClass(booking.status)" x-text="booking.status_label || booking.status || 'Status unavailable'"></span>
                            </div>
                            <div class="rp-booking-title" x-text="booking.title || 'Booking'"></div>
                            <div class="rp-booking-meta">
                                <span class="rp-meta-item"><svg viewBox="0 0 24 24"><path d="M3 21h18"></path><path d="M5 21V7l8-4 6 3v15"></path></svg><span x-text="booking.hospital || 'Hospital unavailable'"></span></span>
                                <span class="rp-meta-item"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg><span x-text="booking.date || 'Date unavailable'"></span></span>
                                <span class="rp-meta-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg><span x-text="booking.time || 'Time unavailable'"></span></span>
                            </div>
                        </article>
                    </template>
                </div>

                <div class="rp-modal-foot"><button class="rp-btn rp-btn-ghost" wire:click="$set('showBookingsModal', false)">Close</button></div>
            </div>
            </div>
        </div>
    </template>
    @endif
    <!-- Add/Edit Modal -->
    @if($showFormModal)
    <template x-teleport="body">
    <div x-data="{open: @entangle('showFormModal')}" x-effect="$watch('open', v => { if(v) document.body.classList.add('rp-modal-open'); else document.body.classList.remove('rp-modal-open'); })">
        <div class="rp-modal-backdrop"></div>
        <div class="rp-modal-layer">
        <div class="rp-modal" role="dialog" aria-modal="true" x-data @keydown.escape.window="@this.set('showFormModal', false)">
            <div class="rp-modal-head">
                <div>
                    <h3 class="rp-modal-title">{{ $editingId ? 'Edit ' . ($formPersonName ?: 'patient') : 'Add new member' }}</h3>
                    <p class="rp-modal-sub">Add or update a family member</p>
                </div>
                <div>
                    <button class="rp-x" wire:click="$set('showFormModal', false)" aria-label="Close">✕</button>
                </div>
            </div>

            <div class="rp-modal-body">
                <div class="rp-section">Identity</div>

                <div class="rp-field rp-span2">
                    <label class="rp-label">MRN ID <span class="opt">(optional)</span></label>
                    <input autocomplete="off" wire:model.live.debounce.400ms="mrn" class="rp-input" maxlength="64" placeholder="Optional" />

                    <div class="mt-2 rp-status {{ $mrnStatus==='available' ? 'ok' : ($mrnStatus==='taken' ? 'bad' : 'wait') }}">
                        <div wire:loading wire:target="mrn" class="rp-status wait">Checking…</div>
                        @if($mrnStatus === 'available') <div class="rp-status ok">MRN ID is available</div> @endif
                        @if($mrnStatus === 'taken') <div class="rp-status bad">This MRN ID is already taken</div> @endif
                        @if($mrnStatus === 'invalid') <div class="rp-status bad">MRN must be 64 characters or fewer</div> @endif
                    </div>
                    @error('mrn') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field">
                    <label class="rp-label">First Name <span class="opt">*</span></label>
                    <input autocomplete="off" wire:model="first_name" class="rp-input" />
                    @error('first_name') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field">
                    <label class="rp-label">Last Name <span class="opt">*</span></label>
                    <input autocomplete="off" wire:model="last_name" class="rp-input" />
                    @error('last_name') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field">
                    <label class="rp-label">Mobile <span class="opt">*</span></label>
                    <div class="rp-prefix-wrap">
                        <div class="rp-prefix">+91</div>
                        <input autocomplete="off" maxlength="10" inputmode="numeric" wire:model="mobile" class="rp-input" />
                    </div>
                    @error('mobile') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field">
                    <label class="rp-label">Date of Birth <span class="opt">*</span></label>
                    <input autocomplete="off" wire:model="dob" type="date" class="rp-input" />
                    @error('dob') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field">
                    <label class="rp-label">Gender <span class="opt">*</span></label>
                    <div class="rp-seg">
                        <label><input autocomplete="off" type="radio" wire:model="gender" value="Male" /><span>Male</span></label>
                        <label><input autocomplete="off" type="radio" wire:model="gender" value="Female" /><span>Female</span></label>
                        <label><input autocomplete="off" type="radio" wire:model="gender" value="Other" /><span>Other</span></label>
                    </div>
                    @error('gender') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field">
                    <label class="rp-label">Blood Group <span class="opt">(optional)</span></label>
                    <select autocomplete="off" wire:model="blood_group" class="rp-select">
                        <option value="">Select</option>
                        <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                        <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                    </select>
                    @error('blood_group') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field">
                    <label class="rp-label">Hospital <span class="opt">*</span></label>
                    <select autocomplete="off" wire:model="hospital_id" class="rp-select">
                        <option value="">Select</option>
                        @foreach($hospitalsList as $id=>$name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('hospital_id') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-field rp-span2">
                    <label class="rp-label">Email <span class="opt">(optional)</span></label>
                    <input autocomplete="off" wire:model="email" type="email" class="rp-input" />
                    @error('email') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                <div class="rp-section">Relationship</div>

                <div>
                    <label class="rp-label">Relationship <span class="opt">*</span></label>
                    <select autocomplete="off" wire:model.live="relationship" class="rp-select">
                        <option>Self</option><option>Father</option><option>Mother</option><option>Spouse</option><option>Child</option><option>Other</option>
                    </select>
                    @error('relationship') <div class="rp-error">{{ $message }}</div> @enderror
                </div>

                @if($relationship === 'Other')
                <div>
                    <label class="rp-label">Specify relationship <span class="opt">*</span></label>
                    <input autocomplete="off" wire:model="relationship_other" class="rp-input" />
                    @error('relationship_other') <div class="rp-error">{{ $message }}</div> @enderror
                </div>
                @else
                <div></div>
                @endif

                @if($relationship !== 'Self')
                <div class="rp-span2 rp-field">
                    <label class="rp-label">Primary Account <span class="opt">*</span></label>
                    @if($primaryLabel)
                        <div class="rp-chip">{{ $primaryLabel }} <button wire:click="clearPrimary">✕</button></div>
                    @else
                                <div style="position:relative">
                                <input autocomplete="off" wire:model.live.debounce.300ms="primarySearch" placeholder="Search by name or mobile number" class="rp-input" />
                                @if(strlen(trim($primarySearch)) >= 2)
                                    <div class="rp-combo-list" x-data x-init="$nextTick(() => { const list = $el; const modalBody = list.closest('.rp-modal-body'); if(modalBody) modalBody.scrollTo({top: list.offsetTop - 24}); document.addEventListener('mousedown', (e)=> { if(!list.contains(e.target)) { $wire.set('primarySearch',''); } }); })">
                                    @if(count($primaryResults))
                                        @foreach($primaryResults as $r)
                                            <button type="button" wire:click="selectPrimary('{{ $r['id'] }}')" wire:key="primary-{{ $r['id'] }}" class="rp-combo-item">
                                                <div class="rp-combo-name">{{ $r['name'] }}</div>
                                                <div class="rp-combo-phone">{{ $r['mobile'] }}</div>
                                            </button>
                                        @endforeach
                                    @else
                                        <div class="rp-combo-empty">No matching primary account holders</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                    @error('primary_id') <div class="rp-error">{{ $message }}</div> @enderror
                    <div class="mt-1 text-xs text-gray-500">The person who holds the account at the hospital</div>
                </div>
                @endif

                <div class="rp-section">Emergency contact & address</div>

                <div>
                    <label class="rp-label">Emergency Contact Name <span class="opt">(optional)</span></label>
                    <input autocomplete="off" wire:model="emergency_contact_name" class="rp-input" />
                </div>

                <div>
                    <label class="rp-label">Emergency Contact Number <span class="opt">(optional)</span></label>
                    <input autocomplete="off" wire:model="emergency_contact_number" class="rp-input" />
                </div>

                <div class="rp-span2">
                    <label class="rp-label">Address <span class="opt">(optional)</span></label>
                    <textarea autocomplete="off" wire:model="address" class="rp-textarea"></textarea>
                </div>

                <div class="rp-field rp-span2">
                    <label class="rp-label">Status</label>
                    <div class="rp-seg">
                        <label><input type="radio" name="patient_status" wire:click="$set('is_active', true)" @checked($is_active) /><span>Active</span></label>
                        <label><input type="radio" name="patient_status" wire:click="$set('is_active', false)" @checked(! $is_active) /><span>Inactive</span></label>
                    </div>
                    @error('is_active') <div class="rp-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="rp-modal-foot">
                <button class="rp-btn rp-btn-ghost" wire:click="$set('showFormModal', false)">Cancel</button>
                <button class="rp-btn rp-btn-primary" wire:click="saveMember" wire:loading.attr="disabled" @if($mrnStatus==='taken' || $mrnStatus==='invalid') disabled @endif>{{ $editingId ? 'Save changes' : 'Create member' }}</button>
            </div>
        </div>
        </div>
    </div>
    </template>
    @endif

    <!-- Delete confirmation dialog -->
    @if($showDeleteDialog)
    <template x-teleport="body">
        <div x-data="{open: @entangle('showDeleteDialog')}" x-effect="$watch('open', v => { if(v) document.body.classList.add('rp-modal-open'); else document.body.classList.remove('rp-modal-open'); })">
            <div class="rp-modal-backdrop"></div>
            <div class="rp-modal-layer">
            <div class="rp-modal" role="dialog" aria-modal="true">
                <div class="rp-modal-head">
                    <h3 class="rp-modal-title">Delete patient</h3>
                    <button class="rp-x" wire:click="$set('showDeleteDialog', false)">✕</button>
                </div>
                <div class="rp-modal-body" style="grid-template-columns:1fr">
                    <div>Are you sure you want to delete {{ $deletePersonName ?: 'this patient' }}? This cannot be undone.</div>
                </div>
                <div class="rp-modal-foot">
                    <button class="rp-btn rp-btn-ghost" wire:click="$set('showDeleteDialog', false)">Cancel</button>
                    <button class="rp-btn rp-btn-primary" wire:click="deleteMember">Delete</button>
                </div>
            </div>
            </div>
        </div>
    </template>
    @endif

    @if($showDeactivateDialog)
    <template x-teleport="body">
        <div x-data="{open: @entangle('showDeactivateDialog')}" x-effect="$watch('open', v => { if(v) document.body.classList.add('rp-modal-open'); else document.body.classList.remove('rp-modal-open'); })">
            <div class="rp-modal-backdrop"></div>
            <div class="rp-modal-layer">
            <div class="rp-modal" role="alertdialog" aria-modal="true" aria-labelledby="rp-deactivate-title">
                <div class="rp-modal-head">
                    <h3 id="rp-deactivate-title" class="rp-modal-title">Deactivate {{ $deactivatePersonName ?: 'this patient' }}?</h3>
                    <button class="rp-x" wire:click="$set('showDeactivateDialog', false)" aria-label="Close">✕</button>
                </div>
                <div class="rp-modal-body" style="grid-template-columns:1fr">
                    <div>They stay in the list and can be activated again.</div>
                    @if($deactivateLinkedCount > 0)
                        <div>{{ $deactivateLinkedCount }} linked family members will stay active.</div>
                    @endif
                </div>
                <div class="rp-modal-foot">
                    <button class="rp-btn rp-btn-ghost" wire:click="$set('showDeactivateDialog', false)">Cancel</button>
                    <button class="rp-btn rp-btn-primary" wire:click="deactivateMember">Deactivate</button>
                </div>
            </div>
            </div>
        </div>
    </template>
    @endif

</div>
</div>
