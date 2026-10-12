<div class="po-page">
    <header class="po-header">
        <div>
            <h1 class="po-title">Prescription Orders</h1>
            <p class="po-muted">Orders submitted to your pharmacy</p>
        </div>
    </header>

    @if($notice !== '')
        <p class="po-notice" role="status">{{ $notice }}</p>
    @endif

    <div class="po-kpis" role="group" aria-label="Order counts">
        @foreach($kpis as $card)
            <button
                type="button"
                class="po-kpi {{ $status === $card['key'] ? 'is-active' : '' }}"
                wire:click="setKpi('{{ $card['key'] }}')"
                wire:key="po-kpi-{{ $card['key'] }}"
                aria-pressed="{{ $status === $card['key'] ? 'true' : 'false' }}"
            >
                <span class="po-kpi-label">{{ $card['label'] }}</span>
                <span class="po-kpi-count">{{ $card['count'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="po-filters">
        <label class="po-field po-field-search">
            <span class="po-sr">Search orders</span>
            <input
                id="po-search"
                type="search"
                wire:model.live="search"
                placeholder="Search order number, patient, mobile, HIP ID"
                autocomplete="off"
            >
        </label>
        <label class="po-field">
            <span class="po-sr">Status</span>
            <select id="po-status-filter" wire:model.live="status" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach($statusOptions as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
                <option value="delivery_queue">Ready, dispatched, or out for delivery</option>
            </select>
        </label>
        <label class="po-field">
            <span class="po-sr">Payment status</span>
            <select id="po-payment-filter" wire:model.live="paymentStatus" aria-label="Filter by payment status">
                <option value="">All payments</option>
                @foreach($paymentOptions as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="po-field">
            <span class="po-field-label">From</span>
            <input id="po-from" type="date" wire:model.live="fromDate" aria-label="Created from">
        </label>
        <label class="po-field">
            <span class="po-field-label">To</span>
            <input id="po-to" type="date" wire:model.live="toDate" aria-label="Created to">
        </label>
        <button type="button" class="po-btn po-btn-ghost" wire:click="clearFilters">Clear</button>
    </div>

    <p class="po-loading" wire:loading.delay role="status">Loading orders…</p>

    <div class="po-table-card">
        @if($orders->isEmpty())
            <div class="po-empty">
                <p class="po-empty-title">No prescription orders</p>
                <p class="po-muted">
                    @if($filtersActive)
                        Nothing matches these filters.
                    @else
                        Orders for your pharmacy will show up here.
                    @endif
                </p>
                @if($filtersActive)
                    <button type="button" class="po-btn po-btn-ghost" wire:click="clearFilters">Clear</button>
                @endif
            </div>
        @else
            <table class="po-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Patient</th>
                        <th>Phone</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th>Unread</th>
                        <th><span class="po-sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        @php
                            $person = $order->person;
                            $patientName = trim((($person->first_name ?? '').' '.($person->last_name ?? '')));
                            if ($patientName === '') {
                                $patientName = trim((string) $order->delivery_name);
                            }
                            if ($patientName === '') {
                                $patientName = 'Unknown patient';
                            }
                            $nameParts = preg_split('/\s+/', $patientName) ?: [];
                            $initials = mb_strtoupper(mb_substr((string) ($nameParts[0] ?? '?'), 0, 1).mb_substr((string) ($nameParts[1] ?? ''), 0, 1));
                            $hipId = trim((string) ($order->hipUser->hip_id ?? ''));
                            $phone = trim((string) ($person->mobile ?? $order->delivery_phone ?? ''));
                            $orderStatus = $order->status instanceof \App\Enums\PharmacyOrderStatus
                                ? $order->status
                                : \App\Enums\PharmacyOrderStatus::tryFrom((string) $order->status);
                            $payment = $order->payment_status instanceof \App\Enums\PharmacyPaymentStatus
                                ? $order->payment_status
                                : \App\Enums\PharmacyPaymentStatus::tryFrom((string) $order->payment_status);
                            $statusColour = in_array($orderStatus?->colour(), ['amber', 'violet', 'orange', 'blue', 'indigo', 'cyan', 'sky', 'green', 'slate', 'red'], true)
                                ? $orderStatus->colour()
                                : 'slate';
                            $paymentColour = in_array($payment?->colour(), ['amber', 'violet', 'orange', 'blue', 'indigo', 'cyan', 'sky', 'green', 'slate', 'red'], true)
                                ? $payment->colour()
                                : 'slate';
                            $viewUrl = route('pharmacist.prescription-orders.show', $order);
                            $editUrl = $viewUrl.'?edit=1';
                            $unread = (int) $order->unread_patient_comments_count;
                        @endphp
                        <tr wire:key="po-order-{{ $order->id }}">
                            <td data-label="Order #">
                                <span class="po-order-number">
                                    <i class="fa-solid fa-file-prescription" aria-hidden="true"></i>
                                    {{ $order->order_number }}
                                </span>
                            </td>
                            <td data-label="Patient">
                                <div class="po-patient">
                                    <span class="po-avatar" aria-hidden="true">{{ $initials }}</span>
                                    <span>
                                        <span class="po-patient-name">{{ $patientName }}</span>
                                        <span class="po-muted po-patient-id">{{ $hipId !== '' ? $hipId : '—' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Phone">{{ $phone !== '' ? $phone : '—' }}</td>
                            <td data-label="Submitted">
                                @if($order->created_at)
                                    <span class="po-submitted">{{ $order->created_at->format('d M Y') }}</span>
                                    <span class="po-muted po-submitted-time">{{ $order->created_at->format('h:i A') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Status">
                                <span class="po-pill is-{{ $statusColour }}">{{ $orderStatus?->label() ?? '—' }}</span>
                            </td>
                            <td data-label="Payment">
                                <span class="po-pill is-{{ $paymentColour }}">{{ $payment?->label() ?? '—' }}</span>
                            </td>
                            <td data-label="Total">₹{{ number_format((float) $order->total, 2) }}</td>
                            <td data-label="Unread">
                                @if($unread > 0)
                                    <span class="po-badge" aria-label="{{ $unread }} unread comments">{{ $unread }}</span>
                                @else
                                    <span class="po-muted">0</span>
                                @endif
                            </td>
                            <td data-label="Actions">
                                <button
                                    type="button"
                                    class="po-icon-btn"
                                    aria-haspopup="menu"
                                    aria-label="Actions for {{ $order->order_number }}"
                                    onclick="window.__poOpenMenu(this, @js($order->id), @js($viewUrl), @js($editUrl))"
                                >
                                    <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="po-table-footer">
                <p class="po-muted">Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }}</p>
                {{ $orders->links('pagination.po') }}
            </div>
        @endif
    </div>

    @if($statusOrderId)
        <template x-teleport="body">
            <div id="po-status-modal" x-on:keydown.escape.window="$wire.closeStatus()">
                <div class="po-modal-backdrop" wire:click="closeStatus"></div>
                <div class="po-modal-layer">
                    <div class="po-modal" role="dialog" aria-modal="true" aria-labelledby="po-status-title" wire:key="po-status-{{ $statusOrderId }}">
                        <div class="po-modal-head">
                            <h2 id="po-status-title" class="po-modal-title">Update status</h2>
                            <button type="button" class="po-icon-btn" wire:click="closeStatus" aria-label="Close">&times;</button>
                        </div>
                        <p class="po-muted">{{ $statusOrderNumber }}</p>
                        @if($statusChoices === [])
                            <p>No manual status changes are available for this order.</p>
                        @else
                            <label class="po-stack" for="po-target-status">New status</label>
                            <select id="po-target-status" wire:model.live="targetStatus">
                                <option value="">Choose a status</option>
                                @foreach($statusChoices as $choice)
                                    <option value="{{ $choice['value'] }}">{{ $choice['label'] }}</option>
                                @endforeach
                            </select>
                            @if($targetStatus === 'dispatched')
                                <label class="po-stack" for="po-partner">Delivery partner</label>
                                <input id="po-partner" type="text" wire:model="deliveryPartner" maxlength="255">
                                <label class="po-stack" for="po-rider">Rider name</label>
                                <input id="po-rider" type="text" wire:model="riderName" maxlength="255">
                                <label class="po-stack" for="po-tracking">Tracking reference</label>
                                <input id="po-tracking" type="text" wire:model="trackingRef" maxlength="255">
                                <label class="po-stack" for="po-expected">Expected delivery</label>
                                <input id="po-expected" type="datetime-local" wire:model="expectedDeliveryAt">
                            @endif
                            @if(in_array($targetStatus, ['rejected', 'cancelled'], true))
                                <label class="po-stack" for="po-reason">Reason</label>
                                <textarea id="po-reason" wire:model="statusReason" rows="3" maxlength="1000"></textarea>
                            @endif
                        @endif
                        @if($statusError !== '')
                            <p class="po-error" role="alert">{{ $statusError }}</p>
                        @endif
                        <div class="po-modal-actions">
                            <button type="button" class="po-btn po-btn-ghost" wire:click="closeStatus">Cancel</button>
                            @if($statusChoices !== [])
                                <button type="button" class="po-btn po-btn-primary" wire:click="applyStatus" wire:loading.attr="disabled">Save</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @if($deleteOrderId)
        <template x-teleport="body">
            <div id="po-delete-modal" x-on:keydown.escape.window="$wire.closeDelete()">
                <div class="po-modal-backdrop" wire:click="closeDelete"></div>
                <div class="po-modal-layer">
                    <div class="po-modal" role="dialog" aria-modal="true" aria-labelledby="po-delete-title" wire:key="po-delete-{{ $deleteOrderId }}">
                        <div class="po-modal-head">
                            <h2 id="po-delete-title" class="po-modal-title">Delete order</h2>
                            <button type="button" class="po-icon-btn" wire:click="closeDelete" aria-label="Close">&times;</button>
                        </div>
                        <p>Delete {{ $deleteOrderNumber }}? This can only happen while the order is unpaid and still requested, in review, cancelled, or rejected.</p>
                        @if($deleteError !== '')
                            <p class="po-error" role="alert">{{ $deleteError }}</p>
                        @endif
                        <div class="po-modal-actions">
                            <button type="button" class="po-btn po-btn-ghost" wire:click="closeDelete">Cancel</button>
                            <button type="button" class="po-btn po-btn-danger" wire:click="deleteOrder" wire:loading.attr="disabled">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

    <script>
        (function () {
            window.__poOpenMenu = function (button, id, viewUrl, editUrl) {
                if (window.__poMenuEl) {
                    window.__poMenuEl.remove();
                    window.__poMenuEl = null;
                }

                var rect = button.getBoundingClientRect();
                var menu = document.createElement('div');
                menu.className = 'po-menu';
                menu.setAttribute('role', 'menu');
                menu.style.right = (window.innerWidth - rect.right) + 'px';
                menu.style.top = (rect.bottom + 6) + 'px';
                menu.innerHTML = ''
                    + '<button type="button" data-action="view" role="menuitem"><i class="fa-solid fa-eye" aria-hidden="true"></i><span>View</span></button>'
                    + '<button type="button" data-action="edit" role="menuitem"><i class="fa-solid fa-pen" aria-hidden="true"></i><span>Edit</span></button>'
                    + '<button type="button" data-action="status" role="menuitem"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i><span>Update status</span></button>'
                    + '<hr>'
                    + '<button type="button" class="is-danger" data-action="delete" role="menuitem"><i class="fa-solid fa-trash" aria-hidden="true"></i><span>Delete</span></button>';

                document.body.appendChild(menu);
                window.__poMenuEl = menu;

                var menuRect = menu.getBoundingClientRect();
                if (menuRect.bottom > window.innerHeight) {
                    menu.style.top = Math.max(8, rect.top - menuRect.height - 6) + 'px';
                }

                function close() {
                    if (window.__poMenuEl) {
                        window.__poMenuEl.remove();
                        window.__poMenuEl = null;
                    }
                    document.removeEventListener('mousedown', onDoc);
                    window.removeEventListener('scroll', close, true);
                    window.removeEventListener('resize', close);
                }

                function onDoc(event) {
                    if (!menu.contains(event.target) && event.target !== button) {
                        close();
                    }
                }

                document.addEventListener('mousedown', onDoc);
                window.addEventListener('scroll', close, true);
                window.addEventListener('resize', close);

                menu.addEventListener('click', function (event) {
                    var target = event.target;
                    while (target && target !== menu && !target.getAttribute('data-action')) {
                        target = target.parentElement;
                    }
                    var action = target ? target.getAttribute('data-action') : null;
                    if (!action) {
                        return;
                    }
                    close();
                    if (action === 'view') {
                        window.location = viewUrl;
                    }
                    if (action === 'edit') {
                        window.location = editUrl;
                    }
                    if (action === 'status') {
                        Livewire.dispatch('po-open-status', { id: Number(id) });
                    }
                    if (action === 'delete') {
                        Livewire.dispatch('po-confirm-delete', { id: Number(id) });
                    }
                });
            };
        })();
    </script>
</div>
