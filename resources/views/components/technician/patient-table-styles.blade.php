<style>
.sp-page-header { margin-bottom: 24px; }
.sp-page-header h1 { font-size: 26px; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
.sp-page-header p { font-size: 13.5px; color: #6b7280; margin-top: 4px; }

.sp-filter-panel,
.mp-filter-panel {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 20px;
}
.sp-filter-row,
.mp-filter-row {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    flex-wrap: wrap;
}
.sp-filter-group,
.mp-filter-group { display: flex; flex-direction: column; gap: 6px; min-width: 160px; }
.sp-filter-group.grow,
.mp-filter-group.grow { flex: 1; min-width: 220px; }
.sp-filter-label,
.mp-filter-label { font-size: 11.5px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; }
.sp-select,
.mp-select {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 13.5px;
    color: #374151;
    background: #fff;
    min-width: 160px;
}
.mp-search {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    background: #fff;
}
.mp-search i { color: #9ca3af; }
.mp-search input {
    border: none;
    outline: none;
    width: 100%;
    font-size: 13.5px;
    background: transparent;
}
.btn-reset-filters,
.btn-mp-reset {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.mp-page-header { margin-bottom: 24px; }
.mp-page-header h1 { font-size: 26px; font-weight: 700; color: #1a1a2e; }
.mp-page-header p { font-size: 13.5px; color: #6b7280; margin-top: 4px; }

.sp-table-wrap {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    overflow-x: auto;
}
.sp-table {
    width: 100%;
    min-width: 960px;
    border-collapse: collapse;
    border-spacing: 0;
    display: table;
}
.sp-table thead { display: table-header-group; }
.sp-table tbody { display: table-row-group; }
.sp-table tr { display: table-row; }
.sp-table th,
.sp-table td { display: table-cell; }
.sp-table thead tr { background: #f8fafc; }
.sp-table th {
    padding: 12px 16px;
    font-size: 11.5px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    text-align: left;
    border-bottom: 1px solid #f0f4f8;
    white-space: nowrap;
}
.sp-table td {
    padding: 14px 16px;
    font-size: 13.5px;
    color: #374151;
    border-bottom: 1px solid #f0f4f8;
    vertical-align: middle;
}
.sp-table tbody tr:last-child td { border-bottom: none; }
.sp-table tbody tr:hover td { background: #fafbfc; }

.sp-patient-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 140px;
}
.sp-patient-init {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.sp-patient-name { font-weight: 600; color: #1a1a2e; white-space: nowrap; }

.btn-sp-action {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid #e8ecf0;
    border-radius: 9px;
    background: #fff;
    color: #6b7280;
    text-decoration: none;
    transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.btn-sp-action:hover {
    border-color: #1A9FD4;
    color: #1A9FD4;
    background: #f0f9ff;
}

.mp-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.btn-mp-icon {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid #e8ecf0;
    border-radius: 9px;
    background: #fff;
    color: #6b7280;
    text-decoration: none;
}
.btn-mp-icon:hover { border-color: #1A9FD4; color: #1A9FD4; background: #f0f9ff; }
.btn-mp-update {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #374151;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}
.btn-mp-update:hover { background: #f8fafc; }
</style>
