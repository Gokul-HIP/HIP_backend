<div>
<style>
    /* ===== PAYMENTS PAGE ===== */
    .pay-wrap {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 0;
    }

    /* ── Top Action Bar ── */
    .pay-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px 16px;
        background: #f8fafc;
        gap: 12px;
        flex-wrap: wrap;
    }
    .pay-topbar-left { display: flex; gap: 10px; }

    .pay-btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 500;
        color: #374151;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        text-decoration: none;
    }
    .pay-btn-outline:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .pay-btn-outline i { color: #94a3b8; font-size: 13px; }

    .pay-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 20px;
        background: #0da2e7;
        border: none;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        transition: background 0.17s, box-shadow 0.17s, transform 0.12s;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(13,162,231,0.4);
    }
    .pay-btn-primary:hover {
        background: #0891cc;
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(13,162,231,0.5);
    }

    /* ── Table Container ── */
    .pay-table-wrap {
        padding: 0 24px 24px;
        flex: 1;
    }
    .pay-table-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: visible;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }

    .pay-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .pay-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .pay-table thead th {
        padding: 13px 16px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .pay-table thead th.text-right { text-align: right; }
    .pay-table thead th.text-center { text-align: center; }

    .pay-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.13s;
    }
    .pay-table tbody tr:last-child { border-bottom: none; }
    .pay-table tbody tr:hover { background: #f8fafc; }

    .pay-table td {
        padding: 14px 16px;
        vertical-align: top;
        color: #374151;
    }
    .pay-table td.text-right { text-align: right; }
    .pay-table td.text-center { text-align: center; }

    /* ── Member cell ── */
    .td-member-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 13.5px;
        line-height: 1.3;
    }
    .td-member-id {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
    }

    /* ── Person cell ── */
    .td-person {
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
    }
    .td-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
        border: 1.5px solid #e2e8f0;
        overflow: hidden;
    }
    .td-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .td-avatar.blue   { background: #dbeafe; color: #2563eb; }
    .td-avatar.purple { background: #ede9fe; color: #7c3aed; }
    .td-avatar.gray   { background: #f1f5f9; color: #475569; }
    .td-avatar.green  { background: #d1fae5; color: #059669; }
    .td-avatar.amber  { background: #fef3c7; color: #d97706; }
    .td-person-name { font-weight: 500; color: #1e293b; }

    /* ── Services / amounts ── */
    .td-service-line { color: #475569; line-height: 1.9; }
    .td-amount-line  { font-family: 'SF Mono', 'Fira Code', monospace; color: #64748b; line-height: 1.9; }
    .td-total { font-size: 16px; font-weight: 800; color: #0f172a; }

    /* ── Payment info ── */
    .td-pay-method { font-weight: 600; color: #334155; font-size: 13px; }

    /* ── Status badges ── */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-top: 5px;
    }
    .badge-completed { background: #d1fae5; color: #065f46; }
    .badge-pending   { background: #fef3c7; color: #92400e; }
    .badge-failed    { background: #fee2e2; color: #991b1b; }
    .badge-refunded  { background: #e2e8f0; color: #475569; }

    /* ── Coins ── */
    .td-coins {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 700;
        font-size: 13px;
        white-space: nowrap;
    }
    .td-coins.positive { color: #d97706; }
    .td-coins.zero     { color: #94a3b8; }
    .td-coins.negative { color: #94a3b8; font-style: italic; }
    .td-coins i { font-size: 12px; }

    /* ── Created info ── */
    .td-created-by   { font-weight: 600; color: #1e293b; font-size: 13px; }
    .td-created-time { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    /* ── Actions dropdown ── */
    .action-wrap {
        position: relative;
        display: inline-block;
    }
    .action-trigger {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.13s, color 0.13s;
        font-size: 15px;
    }
    .action-trigger:hover { background: #f1f5f9; color: #475569; }

    .action-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        width: 200px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        z-index: 100;
        overflow: hidden;
        display: none;
        animation: menuPop 0.15s ease;
    }
    .action-menu.open { display: block; }

    @keyframes menuPop {
        from { opacity: 0; transform: translateY(-6px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0)   scale(1); }
    }

    .action-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 14px;
        font-size: 13.5px;
        color: #374151;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
        transition: background 0.12s;
    }
    .action-menu-item:hover { background: #f8fafc; }
    .action-menu-item i { color: #94a3b8; font-size: 13px; width: 16px; }
    .action-menu-divider { border: none; border-top: 1px solid #f1f5f9; margin: 3px 0; }
    .action-menu-item.danger     { color: #dc2626; }
    .action-menu-item.danger:hover { background: #fef2f2; }
    .action-menu-item.danger i   { color: #dc2626; }

    /* ── Footer ── */
    .pay-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 24px;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .pay-footer-label {
        font-size: 13px;
        color: #64748b;
    }
    .pay-footer-label strong { color: #0f172a; font-weight: 600; }

    .pager { display: flex; align-items: center; gap: 6px; }
    .pager-btn {
        width: 36px;
        height: 36px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: background 0.13s, border-color 0.13s;
        text-decoration: none;
    }
    .pager-btn:hover { background: #f1f5f9; }
    .pager-btn.active {
        background: #0da2e7;
        border-color: #0da2e7;
        color: #fff;
    }
    .pager-nav {
        width: 36px;
        height: 36px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #64748b;
        cursor: pointer;
        transition: background 0.13s;
    }
    .pager-nav:hover { background: #f1f5f9; }
    .pager-nav.disabled { opacity: 0.4; cursor: not-allowed; }

    /* Responsive scroll */
    .pay-table-scroll { overflow-x: auto; }

    @media (max-width: 900px) {
        .pay-topbar { padding: 14px 14px 10px; }
        .pay-table-wrap { padding: 0 14px 14px; }
        .pay-table td, .pay-table th { padding: 10px 10px; }
    }
</style>

<div class="pay-wrap">

    {{-- ===== TOP ACTION BAR ===== --}}
    <div class="pay-topbar">
        <div class="pay-topbar-left">
            <button class="pay-btn-outline">
                <i class="fas fa-sliders-h"></i> Filter
            </button>
            <button class="pay-btn-outline">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <a href="{{ route('cashier.payments.create') }}" class="pay-btn-primary">
            <i class="fas fa-plus"></i> New Payment
        </a>
    </div>

    {{-- ===== TABLE ===== --}}
    <div class="pay-table-wrap">
        <div class="pay-table-card">
            <div class="pay-table-scroll">
                <table class="pay-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Person</th>
                            <th>Services</th>
                            <th class="text-right">Itemized Payable</th>
                            <th class="text-right">Total Amount</th>
                            <th>Payment Info</th>
                            <th>Coins</th>
                            <th>Created Info</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        {{-- ── ROW LOOP (Livewire / Blade) ── --}}
                        {{-- @forelse($payments ?? $dummyPayments ?? [] as $payment) --}}
                        <tr>
                            {{-- Member --}}
                            <td>
                                {{-- <div class="td-member-name">{{ $payment['member_name'] }}</div>
                                <div class="td-member-id">ID: {{ $payment['member_id'] }}</div> --}}
                            </td>

                            {{-- Person --}}
                            <td>
                                {{-- <div class="td-person">
                                    <div class="td-avatar {{ $payment['avatar_color'] ?? 'gray' }}">
                                        @if(!empty($payment['avatar_img']))
                                            <img src="{{ $payment['avatar_img'] }}" alt="{{ $payment['person_name'] }}">
                                        @else
                                            {{ strtoupper(substr($payment['person_name'], 0, 1)) }}{{ strtoupper(substr(strrchr($payment['person_name'], ' ') ?? '', 1, 1)) }}
                                        @endif
                                    </div>
                                    <span class="td-person-name">{{ $payment['person_name'] }}</span>
                                </div> --}}
                            </td>

                            {{-- Services --}}
                            <td>
                                {{-- @foreach($payment['services'] as $svc)
                                    <div class="td-service-line">{{ $svc }}</div>
                                @endforeach --}}
                            </td>

                            {{-- Itemized --}}
                            <td class="text-right">
                                {{-- @foreach($payment['itemized'] as $amt)
                                    <div class="td-amount-line">${{ number_format($amt, 2) }}</div>
                                @endforeach --}}
                            </td>

                            {{-- Total --}}
                            <td class="text-right">
                                {{-- <div class="td-total">${{ number_format($payment['total'], 2) }}</div> --}}
                            </td>

                            {{-- Payment Info --}}
                            <td>
                                {{-- <div class="td-pay-method">{{ $payment['payment_method'] }}</div> --}}
                                {{-- @php
                                    $badgeClass = match(strtolower($payment['status'])) {
                                        'completed' => 'badge-completed',
                                        'pending'   => 'badge-pending',
                                        'failed'    => 'badge-failed',
                                        'refunded'  => 'badge-refunded',
                                        default     => 'badge-refunded',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $payment['status'] }}</span> --}}
                            </td>

                            {{-- Coins --}}
                            <td>
                                {{-- @php
                                    $coins = $payment['coins'];
                                    $coinsClass = $coins > 0 ? 'positive' : ($coins < 0 ? 'negative' : 'zero');
                                    $coinsLabel = $coins > 0 ? '+' . $coins : ($coins == 0 ? '0' : $coins);
                                @endphp
                                <div class="td-coins {{ $coinsClass }}">
                                    <i class="fas fa-circle-notch"></i>
                                    {{ $coinsLabel }}
                                </div> --}}
                            </td>

                            {{-- Created Info --}}
                            <td>
                                {{-- <div class="td-created-by">{{ $payment['created_by'] }}</div>
                                <div class="td-created-time">{{ $payment['created_at'] }}</div> --}}
                            </td>

                            {{-- Actions --}}
                            <td class="text-center">
                                <div class="action-wrap">
                                    <button class="action-trigger" onclick="toggleActionMenu(this)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="action-menu">
                                        <button class="action-menu-item">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="action-menu-item">
                                            <i class="fas fa-paper-plane"></i> Resend Request
                                        </button>
                                        <hr class="action-menu-divider">
                                        <button class="action-menu-item danger">
                                            <i class="fas fa-undo"></i> Request Refund
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        {{-- @empty --}}
                        {{-- ── Fallback static rows (remove when real data flows in) ── --}}

                        {{-- Row 1 --}}
                        <tr>
                            <td>
                                <div class="td-member-name">Robert Chen</div>
                                <div class="td-member-id">ID: MB-99201</div>
                            </td>
                            <td>
                                <div class="td-person">
                                    <div class="td-avatar blue">RC</div>
                                    <span class="td-person-name">Robert Chen</span>
                                </div>
                            </td>
                            <td>
                                <div class="td-service-line">Procedure</div>
                                <div class="td-service-line">Diagnostic</div>
                                <div class="td-service-line">Pharmacy</div>
                            </td>
                            <td class="text-right">
                                <div class="td-amount-line">$120.00</div>
                                <div class="td-amount-line">$45.50</div>
                                <div class="td-amount-line">$12.00</div>
                            </td>
                            <td class="text-right"><div class="td-total">$177.50</div></td>
                            <td>
                                <div class="td-pay-method">HIP Card</div>
                                <span class="badge badge-completed">Completed</span>
                            </td>
                            <td><div class="td-coins positive"><i class="fas fa-circle-notch"></i> +450</div></td>
                            <td>
                                <div class="td-created-by">By Admin Sarah</div>
                                <div class="td-created-time">24 Oct, 10:30 AM</div>
                            </td>
                            <td class="text-center">
                                <div class="action-wrap">
                                    <button class="action-trigger" onclick="toggleActionMenu(this)"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu">
                                        <button class="action-menu-item"><i class="fas fa-eye"></i> View</button>
                                        <button class="action-menu-item"><i class="fas fa-paper-plane"></i> Resend Request</button>
                                        <hr class="action-menu-divider">
                                        <button class="action-menu-item danger"><i class="fas fa-undo"></i> Request Refund</button>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- Row 2 --}}
                        <tr>
                            <td>
                                <div class="td-member-name">Elena Rodriguez</div>
                                <div class="td-member-id">ID: MB-88124</div>
                            </td>
                            <td>
                                <div class="td-person">
                                    <div class="td-avatar amber">ER</div>
                                    <span class="td-person-name">Elena Rodriguez</span>
                                </div>
                            </td>
                            <td>
                                <div class="td-service-line">Procedure</div>
                                <div class="td-service-line">Pharmacy</div>
                            </td>
                            <td class="text-right">
                                <div class="td-amount-line">$50.00</div>
                                <div class="td-amount-line">$22.30</div>
                            </td>
                            <td class="text-right"><div class="td-total">$72.30</div></td>
                            <td>
                                <div class="td-pay-method">HIP App</div>
                                <span class="badge badge-pending">Pending</span>
                            </td>
                            <td><div class="td-coins positive"><i class="fas fa-circle-notch"></i> +120</div></td>
                            <td>
                                <div class="td-created-by">By Admin Jane</div>
                                <div class="td-created-time">24 Oct, 09:15 AM</div>
                            </td>
                            <td class="text-center">
                                <div class="action-wrap">
                                    <button class="action-trigger" onclick="toggleActionMenu(this)"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu">
                                        <button class="action-menu-item"><i class="fas fa-eye"></i> View</button>
                                        <button class="action-menu-item"><i class="fas fa-paper-plane"></i> Resend Request</button>
                                        <hr class="action-menu-divider">
                                        <button class="action-menu-item danger"><i class="fas fa-undo"></i> Request Refund</button>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- Row 3 --}}
                        <tr>
                            <td>
                                <div class="td-member-name">Marcus Thorne</div>
                                <div class="td-member-id">ID: MB-12003</div>
                            </td>
                            <td>
                                <div class="td-person">
                                    <div class="td-avatar gray">MT</div>
                                    <span class="td-person-name">Marcus Thorne</span>
                                </div>
                            </td>
                            <td>
                                <div class="td-service-line">Specialist</div>
                            </td>
                            <td class="text-right">
                                <div class="td-amount-line">$200.00</div>
                            </td>
                            <td class="text-right"><div class="td-total">$200.00</div></td>
                            <td>
                                <div class="td-pay-method">HIP Card</div>
                                <span class="badge badge-failed">Failed</span>
                            </td>
                            <td><div class="td-coins zero"><i class="fas fa-circle-notch"></i> 0</div></td>
                            <td>
                                <div class="td-created-by">By Admin Sarah</div>
                                <div class="td-created-time">23 Oct, 04:50 PM</div>
                            </td>
                            <td class="text-center">
                                <div class="action-wrap">
                                    <button class="action-trigger" onclick="toggleActionMenu(this)"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu">
                                        <button class="action-menu-item"><i class="fas fa-eye"></i> View</button>
                                        <button class="action-menu-item"><i class="fas fa-paper-plane"></i> Resend Request</button>
                                        <hr class="action-menu-divider">
                                        <button class="action-menu-item danger"><i class="fas fa-undo"></i> Request Refund</button>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- Row 4 --}}
                        <tr>
                            <td>
                                <div class="td-member-name">Isabella Vane</div>
                                <div class="td-member-id">ID: MB-44501</div>
                            </td>
                            <td>
                                <div class="td-person">
                                    <div class="td-avatar purple">IV</div>
                                    <span class="td-person-name">Isabella Vane</span>
                                </div>
                            </td>
                            <td>
                                <div class="td-service-line">Consultation</div>
                            </td>
                            <td class="text-right">
                                <div class="td-amount-line">$85.00</div>
                            </td>
                            <td class="text-right"><div class="td-total">$85.00</div></td>
                            <td>
                                <div class="td-pay-method">HIP App</div>
                                <span class="badge badge-refunded">Refunded</span>
                            </td>
                            <td><div class="td-coins negative"><i class="fas fa-circle-notch"></i> -150</div></td>
                            <td>
                                <div class="td-created-by">By Admin Jane</div>
                                <div class="td-created-time">23 Oct, 11:20 AM</div>
                            </td>
                            <td class="text-center">
                                <div class="action-wrap">
                                    <button class="action-trigger" onclick="toggleActionMenu(this)"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu">
                                        <button class="action-menu-item"><i class="fas fa-eye"></i> View</button>
                                        <button class="action-menu-item"><i class="fas fa-paper-plane"></i> Resend Request</button>
                                        <hr class="action-menu-divider">
                                        <button class="action-menu-item danger"><i class="fas fa-undo"></i> Request Refund</button>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- @endforelse --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== FOOTER / PAGINATION ===== --}}
    {{-- <div class="pay-footer">
        <span class="pay-footer-label">
            Showing <strong>{{ $payments->firstItem() ?? '1 - 4' }}</strong>
            @if(isset($payments) && $payments->total())
                of {{ $payments->total() }} records
            @else
                of 48 records
            @endif
        </span>

        <div class="pager">
            <button class="pager-nav {{ ($payments->currentPage() ?? 1) <= 1 ? 'disabled' : '' }}">
                <i class="fas fa-chevron-left"></i>
            </button>

            @if(isset($payments) && $payments->lastPage() > 1)
                @for($i = 1; $i <= min($payments->lastPage(), 5); $i++)
                    <a href="{{ $payments->url($i) }}" class="pager-btn {{ $payments->currentPage() == $i ? 'active' : '' }}">
                        {{ $i }}
                    </a>
                @endfor
            @else
                <button class="pager-btn active">1</button>
                <button class="pager-btn">2</button>
                <button class="pager-btn">3</button>
            @endif

            <button class="pager-nav">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div> --}}

<script>
    /* ── Action dropdown toggle ── */
    function toggleActionMenu(btn) {
        const menu = btn.nextElementSibling;
        const isOpen = menu.classList.contains('open');

        // Close all open menus
        document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));

        if (!isOpen) {
            menu.classList.add('open');

            // Smart positioning: flip up if near bottom of viewport
            const rect = menu.getBoundingClientRect();
            if (rect.bottom > window.innerHeight - 20) {
                menu.style.top = 'auto';
                menu.style.bottom = 'calc(100% + 6px)';
            } else {
                menu.style.top = '';
                menu.style.bottom = '';
            }
        }
    }

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-wrap')) {
            document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));
        }
    });
</script>
</div>