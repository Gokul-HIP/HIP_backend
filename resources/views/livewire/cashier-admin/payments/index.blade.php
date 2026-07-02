{{-- ============================================================
   Payments Index — Transaction List
   Path: resources/views/livewire/cashier/payments/index.blade.php
   Uses: Font Awesome + custom CSS + Tailwind
   ============================================================ --}}

   <style>
    /* ── Action dropdown ── */
    .action-wrap { position: relative; display: inline-block; }

    .action-trigger {
        width: 32px; height: 32px; border-radius: 50%;
        border: none; background: transparent; color: #94a3b8;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background .13s, color .13s; font-size: 16px;
    }
    .action-trigger:hover { background: #f1f5f9; color: #475569; }

    .action-menu {
        position: fixed;
        width: 196px; background: #fff;
        border: 1px solid #e2e8f0; border-radius: 10px;
        box-shadow: 0 8px 28px rgba(0,0,0,0.13);
        z-index: 9999; overflow: hidden; display: none;
    }
    .action-menu.open { display: block; animation: menuPop .14s ease; }

    @keyframes menuPop {
        from { opacity: 0; transform: translateY(-6px) scale(.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .action-item {
        display: flex; align-items: center; gap: 10px;
        width: 100%; padding: 10px 14px;
        font-size: 13.5px; color: #374151;
        background: none; border: none;
        cursor: pointer; text-align: left; transition: background .11s;
    }
    .action-item:hover { background: #f8fafc; }
    .action-item i { color: #94a3b8; font-size: 13px; width: 15px; text-align: center; }
    .action-item.danger { color: #dc2626; }
    .action-item.danger:hover { background: #fef2f2; }
    .action-item.danger i { color: #dc2626; }
    .action-divider { border: none; border-top: 1px solid #f1f5f9; margin: 3px 0; }

    /* ── Badges ── */
    .badge {
        display: inline-flex; align-items: center;
        padding: 2px 8px; border-radius: 20px;
        font-size: 10px; font-weight: 700;
        letter-spacing: .05em; text-transform: uppercase;
    }
    .badge-completed { background:#d1fae5; color:#065f46; }
    .badge-pending   { background:#fef3c7; color:#92400e; }
    .badge-failed    { background:#fee2e2; color:#991b1b; }
    .badge-refunded  { background:#e2e8f0; color:#475569; }
    .badge-app       { background:#dbeafe; color:#1d4ed8; }
    .badge-admin     { background:#ede9fe; color:#6d28d9; }

    /* ── Coins ── */
    .coins-pos { color:#d97706; font-weight:700; }
    .coins-zer { color:#94a3b8; font-weight:600; }
    .coins-neg { color:#94a3b8; font-weight:600; font-style:italic; }

    /* ── Table ── */
    .pay-tbl { width:100%; border-collapse:collapse; font-size:13.5px; }
    .pay-tbl thead tr { background:#f8fafc; border-bottom:1px solid #e2e8f0; }
    .pay-tbl thead th {
        padding:12px 16px; text-align:left;
        font-size:11px; font-weight:700; color:#94a3b8;
        letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
    }
    .pay-tbl thead th.r { text-align:right; }
    .pay-tbl thead th.c { text-align:center; }
    .pay-tbl tbody tr { border-bottom:1px solid #f1f5f9; transition:background .12s; }
    .pay-tbl tbody tr:last-child { border-bottom:none; }
    .pay-tbl tbody tr:hover { background:#f8fafc; }
    .pay-tbl td { padding:14px 16px; vertical-align:top; color:#374151; }
    .pay-tbl td.r { text-align:right; }
    .pay-tbl td.c { text-align:center; }

    /* ── Avatar ── */
    .td-av {
        width:32px; height:32px; border-radius:50%; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        font-size:11px; font-weight:700; border:1.5px solid #e2e8f0; overflow:hidden;
    }
    .td-av img { width:100%; height:100%; object-fit:cover; }
    .av-blue   { background:#dbeafe; color:#2563eb; }
    .av-purple { background:#ede9fe; color:#7c3aed; }
    .av-gray   { background:#f1f5f9; color:#475569; }
    .av-amber  { background:#fef3c7; color:#d97706; }
    .av-green  { background:#d1fae5; color:#059669; }
</style>

<div>

    {{-- ===== TOP BAR ===== --}}
    <div class="flex items-center justify-between px-6 py-4 flex-wrap gap-3">
        <div class="flex gap-2">
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                <i class="fas fa-sliders-h text-slate-400 text-xs"></i> Filter
            </button>
            <a href="{{ route('cashier.payments.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                <i class="fas fa-download text-slate-400 text-xs"></i> Export
            </a>
        </div>
        <a href="{{ route('cashier.payments.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-semibold text-sm rounded-lg shadow-md shadow-sky-400/30 hover:-translate-y-0.5 transition-all" style="background: var(--button-color); hover:background: var(--button-hover);">
            <i class="fas fa-plus text-xs"></i> New Payment
        </a>
    </div>

    {{-- ===== TABLE ===== --}}
    <div class="px-6 pb-6">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm" style="overflow:visible;">
            <div style="overflow-x:auto; overflow-y:visible;">
                <table class="pay-tbl">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Person</th>
                            <th>Services</th>
                            <th class="r">Itemized<br>Payable</th>
                            <th class="r">Total<br>Amount</th>
                            <th>Payment<br>Info</th>
                            <th>Coins</th>
                            <th>Created Info</th>
                            <th class="c">Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse($payments ?? [] as $p)
                        <tr wire:key="payment-row-{{ $p['id'] }}">
                            <td>
                                <div class="font-bold text-slate-900 text-sm">{{ $p['member_name'] }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">ID: {{ $p['member_id'] }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    <div class="td-av {{ $p['avatar_color'] ?? 'av-gray' }}">
                                        @if(!empty($p['avatar_img']))
                                            <img src="{{ $p['avatar_img'] }}" alt="">
                                        @else
                                            {{ strtoupper(substr($p['person_name'],0,1)) }}{{ strtoupper(substr(strrchr($p['person_name'],' ')??'',1,1)) }}
                                        @endif
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">{{ $p['person_name'] }}</span>
                                </div>
                            </td>
                            <td>
                                @foreach($p['services'] as $s)<div class="text-slate-600 leading-7">{{ $s }}</div>@endforeach
                            </td>
                            <td class="r">
                                @foreach($p['itemized'] as $a)<div class="font-mono text-slate-500 leading-7">₹{{ number_format($a,2) }}</div>@endforeach
                            </td>
                            <td class="r"><div class="text-base font-black text-slate-900">₹{{ number_format($p['total'],2) }}</div></td>
                            <td>
                                <div class="font-semibold text-slate-700 text-sm">{{ $p['payment_method'] }}</div>
                                @php $bc=match(strtolower($p['status'])){'completed'=>'badge-completed','pending'=>'badge-pending','failed'=>'badge-failed','refunded'=>'badge-refunded',default=>'badge-refunded'}; @endphp
                                <span class="badge {{ $bc }} mt-1.5">{{ $p['status'] }}</span>
                            </td>
                            <td>
                                @php $c=$p['coins']; $cc=$c>0?'coins-pos':($c<0?'coins-neg':'coins-zer'); $cl=$c>0?'+'.$c:($c==0?'0':$c); @endphp
                                <div class="flex items-center gap-1.5 {{ $cc }} text-sm">
                                    <i class="fas fa-circle text-[7px]"></i> {{ $cl }}
                                </div>
                            </td>
                            <td>
                                @if(($p['source_type'] ?? '') === 'app')
                                    <span class="badge badge-app">App Invoice</span>
                                @else
                                    <span class="badge badge-admin">Admin</span>
                                    @if(!empty($p['created_by']) && $p['created_by'] !== '—')
                                        <div class="text-sm font-semibold text-slate-800 mt-1.5">{{ $p['created_by'] }}</div>
                                    @endif
                                @endif
                                <div class="text-xs text-slate-400 mt-0.5">{{ $p['created_at'] }}</div>
                            </td>
                            <td class="c">
                                <div class="action-wrap">
                                    <button type="button" class="action-trigger" onclick="event.preventDefault(); event.stopPropagation(); toggleMenu(this)"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu">
                                        <button type="button" class="action-item" wire:click.stop.prevent="viewPayment({{ $p['id'] }})"><i class="fas fa-eye"></i> View</button>
                                        <button
                                            type="button"
                                            class="action-item"
                                            wire:click.stop.prevent="resendRequest({{ $p['id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="resendRequest"
                                            onclick="event.preventDefault(); event.stopPropagation(); if (window.Livewire && window.Livewire.first()) { window.Livewire.first().call('resendRequest', {{ $p['id'] }}); }"
                                        >
                                            <i class="fas fa-paper-plane"></i> Resend Request
                                        </button>
                                        <hr class="action-divider">
                                        <button type="button" class="action-item danger" wire:click.stop.prevent="requestRefund({{ $p['id'] }})"><i class="fas fa-undo"></i> Request Refund</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty

                        {{-- ── Static fallback ── --}}
                        @php
                        $rows = [
                            ['member'=>'Robert Chen',    'mid'=>'MB-99201','person'=>'Robert Chen',    'av'=>'av-blue',  'ini'=>'RC','img'=>null,'svcs'=>['Procedure','Diagnostic','Pharmacy'],'amts'=>['$120.00','$45.50','$12.00'], 'total'=>'$177.50','method'=>'HIP Card','status'=>'COMPLETED','badge'=>'badge-completed','coins'=>'+450','cc'=>'coins-pos','by'=>'By Admin Sarah','at'=>'24 Oct, 10:30 AM'],
                            ['member'=>'Elena Rodriguez','mid'=>'MB-88124','person'=>'Elena Rodriguez','av'=>'av-amber','ini'=>'ER','img'=>null,'svcs'=>['Procedure','Pharmacy'],              'amts'=>['$50.00','$22.30'],         'total'=>'$72.30', 'method'=>'HIP App', 'status'=>'PENDING',   'badge'=>'badge-pending',  'coins'=>'+120','cc'=>'coins-pos','by'=>'By Admin Jane', 'at'=>'24 Oct, 09:15 AM'],
                            ['member'=>'Marcus Thorne',  'mid'=>'MB-12003','person'=>'Marcus Thorne',  'av'=>'av-gray', 'ini'=>'MT','img'=>null,'svcs'=>['Specialist'],                        'amts'=>['$200.00'],                 'total'=>'$200.00','method'=>'HIP Card','status'=>'FAILED',    'badge'=>'badge-failed',   'coins'=>'0',   'cc'=>'coins-zer','by'=>'By Admin Sarah','at'=>'23 Oct, 04:50 PM'],
                            ['member'=>'Isabella Vane',  'mid'=>'MB-44501','person'=>'Isabella Vane',  'av'=>'av-purple','ini'=>'IV','img'=>null,'svcs'=>['Consultation'],                     'amts'=>['$85.00'],                  'total'=>'$85.00', 'method'=>'HIP App', 'status'=>'REFUNDED',  'badge'=>'badge-refunded', 'coins'=>'-150','cc'=>'coins-neg','by'=>'By Admin Jane', 'at'=>'23 Oct, 11:20 AM'],
                        ];
                        @endphp

                        @foreach($rows as $r)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-900 text-sm">{{ $r['member'] }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">ID: {{ $r['mid'] }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    <div class="td-av {{ $r['av'] }}">{{ $r['ini'] }}</div>
                                    <span class="text-sm font-medium text-slate-700">{{ $r['person'] }}</span>
                                </div>
                            </td>
                            <td>@foreach($r['svcs'] as $s)<div class="text-slate-600 leading-7">{{ $s }}</div>@endforeach</td>
                            <td class="r">@foreach($r['amts'] as $a)<div class="font-mono text-slate-500 leading-7">{{ $a }}</div>@endforeach</td>
                            <td class="r"><div class="text-base font-black text-slate-900">{{ $r['total'] }}</div></td>
                            <td>
                                <div class="font-semibold text-slate-700 text-sm">{{ $r['method'] }}</div>
                                <span class="badge {{ $r['badge'] }} mt-1.5">{{ $r['status'] }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5 {{ $r['cc'] }} text-sm">
                                    <i class="fas fa-circle text-[7px]"></i> {{ $r['coins'] }}
                                </div>
                            </td>
                            <td>
                                <div class="text-sm font-semibold text-slate-800">{{ $r['by'] }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ $r['at'] }}</div>
                            </td>
                            <td class="c">
                                <div class="action-wrap">
                                    <button type="button" class="action-trigger" onclick="event.preventDefault(); event.stopPropagation(); toggleMenu(this)"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu">
                                        <button type="button" class="action-item"><i class="fas fa-eye"></i> View</button>
                                        <button type="button" class="action-item" disabled>
                                            <i class="fas fa-paper-plane"></i> Resend Request
                                        </button>
                                        <hr class="action-divider">
                                        <button type="button" class="action-item danger"><i class="fas fa-undo"></i> Request Refund</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach

                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex items-center justify-between px-5 py-4 border-t border-slate-100">
                <span class="text-sm text-slate-500">
                    @if(isset($payments) && method_exists($payments, 'firstItem'))
                        Showing
                        <strong class="text-slate-800">
                            {{ $payments->firstItem() }} – {{ $payments->lastItem() }}
                        </strong>
                        of {{ $payments->total() }} records
                    @else
                        Showing <strong class="text-slate-800">0 – 0</strong> of 0 records
                    @endif
                </span>
                <div class="flex items-center gap-1.5">
                    @if(isset($payments) && method_exists($payments, 'lastPage') && $payments->lastPage() > 1)
                        <nav class="flex items-center gap-1.5" aria-label="Pagination">
                            @php
                                $current = $payments->currentPage();
                                $last = $payments->lastPage();
                            @endphp

                            <a href="{{ $payments->previousPageUrl() ?: '#' }}"
                               class="px-3 py-1.5 border rounded {{ $payments->onFirstPage() ? 'pointer-events-none opacity-50 text-slate-400' : 'text-slate-700 hover:bg-slate-50' }}">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            @for($page = 1; $page <= $last; $page++)
                                <a href="{{ $payments->url($page) }}"
                                   class="px-3 py-1.5 border rounded text-sm {{ $current === $page ? 'bg-blue-50 border-blue-400 text-blue-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                    {{ $page }}
                                </a>
                            @endfor

                            <a href="{{ $payments->nextPageUrl() ?: '#' }}"
                               class="px-3 py-1.5 border rounded {{ $current === $last ? 'pointer-events-none opacity-50 text-slate-400' : 'text-slate-700 hover:bg-slate-50' }}">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function toggleMenu(btn) {
        const menu = btn.nextElementSibling;
        const isOpen = menu.classList.contains('open');

        // Close all open menus
        document.querySelectorAll('.action-menu.open').forEach(m => {
            m.classList.remove('open');
            m.style.cssText = '';
        });

        if (!isOpen) {
            const btnRect = btn.getBoundingClientRect();
            const menuW = 196;

            // Temporarily render off-screen to measure real height
            menu.style.cssText = 'position:fixed;visibility:hidden;display:block;top:-9999px;left:-9999px;width:' + menuW + 'px;';
            const menuH = menu.offsetHeight;
            menu.style.cssText = '';

            // Now position correctly
            const spaceBelow = window.innerHeight - btnRect.bottom;
            let top, left;

            if (spaceBelow < menuH + 12) {
                // Flip upward
                top = btnRect.top - menuH - 4;
            } else {
                top = btnRect.bottom + 4;
            }

            left = btnRect.right - menuW;
            if (left < 8) left = 8;

            menu.style.position = 'fixed';
            menu.style.width    = menuW + 'px';
            menu.style.zIndex   = '9999';
            menu.style.top      = top + 'px';
            menu.style.left     = left + 'px';
            menu.style.right    = 'auto';
            menu.style.bottom   = 'auto';

            menu.classList.add('open');
        }
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('.action-wrap')) {
            document.querySelectorAll('.action-menu.open').forEach(m => {
                m.classList.remove('open');
                m.style.cssText = '';
            });
        }
    });

    // Close on any scroll (page or table)
    document.addEventListener('scroll', () => {
        document.querySelectorAll('.action-menu.open').forEach(m => {
            m.classList.remove('open');
            m.style.cssText = '';
        });
    }, true);
</script>
