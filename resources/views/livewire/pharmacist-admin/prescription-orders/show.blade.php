<div class="po-page po-show">
    <header class="po-header po-hero">
        <div>
            <h1 class="po-title">{{ $patient['order_number'] }}</h1>
            <p class="po-muted">Submitted {{ $patient['submitted'] !== '' ? $patient['submitted'] : '—' }}</p>
        </div>
        <div class="po-header-actions">
            <span class="po-pill is-{{ $statusColour }}">{{ $status?->label() ?? '—' }}</span>
            <span class="po-pill is-{{ $paymentColour }}">{{ $payment?->label() ?? '—' }}</span>
            <button type="button" class="po-btn po-btn-ghost" wire:click="openStatus({{ $orderId }})">Update status</button>
            <a class="po-btn po-btn-ghost" href="{{ route('pharmacist.prescription-orders.index') }}">Back to list</a>
        </div>
    </header>

    @if($notice !== '')
        <p class="po-notice" role="status">{{ $notice }}</p>
    @endif

    <div class="po-show-grid">
        <div class="po-show-main">
            <section class="po-card is-info" id="po-viewer" aria-label="Prescription">
                @if(! $hasFile)
                    <div class="po-card-head">
                        <div class="po-card-ident">
                            <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v6h6M8 13h8M8 17h6"/></svg></span>
                            <h2 class="po-card-title">Prescription</h2>
                        </div>
                    </div>
                    <p class="po-muted">No prescription file is available.</p>
                @elseif($prescriptionKind === 'image')
                    <div
                        class="po-viewer"
                        x-data="{ zoom: 1, rotate: 0 }"
                    >
                        <div class="po-card-head">
                            <div class="po-card-ident">
                            <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v6h6M8 13h8M8 17h6"/></svg></span>
                            <h2 class="po-card-title">Prescription</h2>
                        </div>
                            <div class="po-viewer-bar">
                                <a class="po-btn po-btn-ghost" href="{{ $fileUrl }}?download=1">Download</a>
                                <a class="po-btn po-btn-ghost" href="{{ $fileUrl }}" target="_blank" rel="noopener">Open full size</a>
                                <div class="po-viewer-tools">
                                    <button type="button" class="po-btn po-btn-ghost" @click="zoom = Math.min(zoom + 0.25, 4)" aria-label="Zoom in" title="Zoom in"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="M20 20l-3.5-3.5M11 8v6M8 11h6"/></svg><span class="po-sr">Zoom in</span></button>
                                    <button type="button" class="po-btn po-btn-ghost" @click="zoom = Math.max(zoom - 0.25, 0.5)" aria-label="Zoom out" title="Zoom out"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="M20 20l-3.5-3.5M8 11h6"/></svg><span class="po-sr">Zoom out</span></button>
                                    <button type="button" class="po-btn po-btn-ghost" @click="rotate = (rotate + 90) % 360" aria-label="Rotate" title="Rotate"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg><span class="po-sr">Rotate</span></button>
                                    <button type="button" class="po-btn po-btn-ghost" @click="zoom = 1; rotate = 0" aria-label="Reset" title="Reset"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg><span class="po-sr">Reset</span></button>
                                </div>
                            </div>
                        </div>
                        <div class="po-viewer-stage">
                            <img
                                src="{{ $fileUrl }}"
                                alt="Prescription for {{ $patient['order_number'] }}"
                                :style="`transform: scale(${zoom}) rotate(${rotate}deg)`"
                            >
                        </div>
                    </div>
                @elseif($prescriptionKind === 'pdf')
                    <div class="po-card-head">
                        <div class="po-card-ident">
                            <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v6h6M8 13h8M8 17h6"/></svg></span>
                            <h2 class="po-card-title">Prescription</h2>
                        </div>
                        <div class="po-viewer-bar">
                            <a class="po-btn po-btn-ghost" href="{{ $fileUrl }}?download=1">Download</a>
                            <a class="po-btn po-btn-ghost" href="{{ $fileUrl }}" target="_blank" rel="noopener">Open full size</a>
                        </div>
                    </div>
                    <iframe class="po-pdf" src="{{ $fileUrl }}" title="Prescription for {{ $patient['order_number'] }}"></iframe>
                @else
                    <div class="po-card-head">
                        <div class="po-card-ident">
                            <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v6h6M8 13h8M8 17h6"/></svg></span>
                            <h2 class="po-card-title">Prescription</h2>
                        </div>
                        @if($hasFile)
                            <div class="po-viewer-bar">
                                <a class="po-btn po-btn-ghost" href="{{ $fileUrl }}?download=1">Download</a>
                                <a class="po-btn po-btn-ghost" href="{{ $fileUrl }}" target="_blank" rel="noopener">Open full size</a>
                            </div>
                        @endif
                    </div>
                    <p class="po-muted">This prescription can be downloaded or opened in a new tab.</p>
                @endif
            </section>

            <section class="po-card is-primary" id="po-patient" aria-label="Patient and delivery">
                <div class="po-card-head">
                    <div class="po-card-ident">
                        <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
                        <h2 class="po-card-title">Patient and delivery</h2>
                    </div>
                </div>
                <div class="po-person">
                    <span class="po-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim((string) $patient['name']), 0, 1)) }}{{ mb_strtoupper(mb_substr(trim((string) (strrchr(trim((string) $patient['name']), ' ') ?: '')), 0, 1)) }}</span>
                    <div>
                        <p class="po-person-name">{{ $patient['name'] }}</p>
                        <p class="po-muted">{{ $patient['hip_id'] !== '' ? $patient['hip_id'] : '—' }}</p>
                    </div>
                </div>
                <dl class="po-facts">
                    <div><dt>Name</dt><dd>{{ $patient['name'] }}</dd></div>
                    <div><dt>HIP ID</dt><dd>{{ $patient['hip_id'] !== '' ? $patient['hip_id'] : '—' }}</dd></div>
                    <div><dt>MRN</dt><dd class="{{ $patient['mrn'] === '' ? 'is-empty' : '' }}">{{ $patient['mrn'] !== '' ? $patient['mrn'] : '—' }}</dd></div>
                    @if($patient['relationship'] !== '')
                        <div><dt>Relationship</dt><dd>{{ $patient['relationship'] }}</dd></div>
                    @endif
                    <div class="po-fact-phone"><dt><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 3h3l2 5-2 1a12 12 0 0 0 6 6l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z"/></svg>Mobile</dt><dd>{{ $patient['mobile'] !== '' ? $patient['mobile'] : '—' }}</dd></div>
                    <div><dt>Address</dt><dd class="{{ $patient['address'] === '' ? 'is-empty' : '' }}">{{ $patient['address'] !== '' ? $patient['address'] : '—' }}</dd></div>
                    <div><dt>Delivery name</dt><dd>{{ $patient['delivery_name'] !== '' ? $patient['delivery_name'] : '—' }}</dd></div>
                    <div class="po-fact-phone"><dt><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 3h3l2 5-2 1a12 12 0 0 0 6 6l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z"/></svg>Delivery phone</dt><dd>{{ $patient['delivery_phone'] !== '' ? $patient['delivery_phone'] : '—' }}</dd></div>
                    <div><dt>Delivery address</dt><dd>{{ $patient['delivery_address'] !== '' ? $patient['delivery_address'] : '—' }}</dd></div>
                    <div><dt>Hospital</dt><dd>{{ $patient['hospital'] !== '' ? $patient['hospital'] : '—' }}</dd></div>
                    <div><dt>Pharmacy</dt><dd>{{ $patient['pharmacy'] !== '' ? $patient['pharmacy'] : '—' }}</dd></div>
                    <div><dt>Order number</dt><dd>{{ $patient['order_number'] }}</dd></div>
                    <div><dt>Submitted</dt><dd>{{ $patient['submitted'] !== '' ? $patient['submitted'] : '—' }}</dd></div>
                </dl>
            </section>

            <div class="po-show-rail">
            <section
                class="po-card is-primary"
                id="po-bill"
                aria-label="Bill"
                x-data
                x-init="if (new URLSearchParams(window.location.search).get('edit') === '1') { $nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'start' }); document.getElementById('po-bill-discount')?.focus(); }) }"
            >
                <div class="po-card-head">
                    <div class="po-card-ident">
                        <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                        <h2 class="po-card-title">Bill</h2>
                    </div>
                    @if($editable && $billDirty)
                        <span class="po-dirty">Unsaved changes</span>
                    @endif
                </div>

                @if($status?->value === 'pending_payment' && $activeInvoice)
                    <p class="po-note">Payment request already sent</p>
                @elseif($status?->value === 'pending_payment')
                    <p class="po-note">Save the bill, then send a new payment request.</p>
                @elseif(! $editable)
                    <p class="po-note">This bill is read-only.</p>
                @endif

                @if($quickNotice !== '')
                    <p class="po-note po-alert" role="status">{{ $quickNotice }}</p>
                @endif

                @if($editable)
                    <div class="po-search">
                        <label class="po-stack" for="po-medicine-search">Search medicines</label>
                        <div class="po-search-field">
                            <svg class="po-search-icon po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="M20 20l-3.5-3.5"/></svg>
                            <input id="po-medicine-search" type="search" wire:model.live="medicineSearch" placeholder="Name, code, brand, or strength" autocomplete="off">
                        </div>
                        @if($productMatches->isNotEmpty())
                            <ul class="po-search-list" role="listbox">
                                @foreach($productMatches as $product)
                                    @php
                                        $stock = $product->stock_quantity;
                                        $stockState = $stock === null ? '' : ((int) $stock <= 0 ? 'out' : ((int) $stock <= 10 ? 'low' : ''));
                                    @endphp
                                    <li wire:key="po-match-{{ $product->id }}">
                                        <button type="button" class="po-search-option" wire:click="addCatalogProduct({{ $product->id }})">
                                            <span>
                                                <strong>{{ $product->product_name }}</strong>
                                                <span class="po-muted">{{ $product->strength ?: '—' }} · {{ $product->dosage_form ?: '—' }} · ₹{{ number_format((float) $product->selling_price, 2) }}</span>
                                            </span>
                                            <span class="po-muted">Stock {{ $stock === null ? '—' : (int) $stock }}</span>
                                            @if($stockState !== '')
                                                <span class="po-stock is-{{ $stockState }}">{{ $stockState === 'out' ? 'Out of stock' : 'Low stock' }}</span>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <button type="button" class="po-btn po-btn-ghost po-btn-add" wire:click="openQuickAdd"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Add new medicine</button>
                @endif

                <div class="po-lines">
                    @forelse($billLines as $index => $line)
                        @php
                            $lineTotal = round(max(0, (int) ($line['qty'] ?? 0)) * max(0, (float) ($line['unit_price'] ?? 0)), 2);
                            $stock = $line['stock'];
                            $stockState = $stock === null || $stock === '' ? '' : ((int) $stock <= 0 ? 'out' : ((int) $stock <= 10 ? 'low' : ''));
                            $selectedWhen = $line['when_to_take'] ?? [];
                        @endphp
                        <article class="po-line" wire:key="po-line-{{ $line['key'] }}">
                            <div class="po-line-head">
                                <span class="po-line-num">{{ $index + 1 }}</span>
                                <div class="po-line-title">
                                    <h3>Medicine {{ $index + 1 }}</h3>
                                    @if($line['name'] !== '')
                                        <strong class="po-line-brand">{{ $line['name'] }}</strong>
                                    @endif
                                    <span class="po-tag {{ $line['is_custom'] ? 'is-custom' : 'is-catalog' }}">{{ $line['is_custom'] ? 'Custom' : 'Catalogue' }}</span>
                                </div>
                                @if($editable)
                                    <button type="button" class="po-btn po-btn-danger po-line-remove" wire:click="removeLine({{ $index }})" aria-label="Remove"><span class="po-sr">Remove</span><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
                                @endif
                            </div>
                            @if($line['is_custom'])
                                <label class="po-stack" for="po-line-name-{{ $line['key'] }}">Name</label>
                                <input id="po-line-name-{{ $line['key'] }}" type="text" wire:model="billLines.{{ $index }}.name" @disabled(! $editable)>
                                <div class="po-line-grid">
                                    <div>
                                        <label class="po-stack" for="po-line-strength-{{ $line['key'] }}">Strength</label>
                                        <input id="po-line-strength-{{ $line['key'] }}" type="text" wire:model="billLines.{{ $index }}.strength" @disabled(! $editable)>
                                    </div>
                                    <div>
                                        <label class="po-stack" for="po-line-form-{{ $line['key'] }}">Dosage form</label>
                                        <input id="po-line-form-{{ $line['key'] }}" type="text" wire:model="billLines.{{ $index }}.dosage_form" @disabled(! $editable)>
                                    </div>
                                </div>
                            @else
                                <p class="po-line-name">{{ $line['name'] }}</p>
                                <p class="po-muted">{{ $line['strength'] !== '' ? $line['strength'] : '—' }} · {{ $line['dosage_form'] !== '' ? $line['dosage_form'] : '—' }}</p>
                            @endif
                            @if($stockState !== '')
                                <p class="po-stock is-{{ $stockState }}">{{ $stockState === 'out' ? 'Out of stock' : 'Low stock' }} — you can still add it.</p>
                            @endif
                            <div class="po-line-grid">
                                <div>
                                    <label class="po-stack" for="po-line-dosage-{{ $line['key'] }}">Dosage</label>
                                    <select id="po-line-dosage-{{ $line['key'] }}" wire:model="billLines.{{ $index }}.dosage" @disabled(! $editable)>
                                        <option value="">Select</option>
                                        @foreach($dosages as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="po-stack" for="po-line-frequency-{{ $line['key'] }}">Frequency</label>
                                    <select id="po-line-frequency-{{ $line['key'] }}" wire:model="billLines.{{ $index }}.frequency" @disabled(! $editable)>
                                        <option value="">Select</option>
                                        @foreach($frequencies as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="po-stack" for="po-line-duration-{{ $line['key'] }}">Duration</label>
                                    <select id="po-line-duration-{{ $line['key'] }}" wire:model="billLines.{{ $index }}.duration" @disabled(! $editable)>
                                        <option value="">Select</option>
                                        @foreach($durations as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p class="po-stack" id="po-line-when-{{ $line['key'] }}">When to take</p>
                            <div class="po-chips" role="group" aria-labelledby="po-line-when-{{ $line['key'] }}">
                                @foreach($whenOptions as $option)
                                    <button
                                        type="button"
                                        class="po-chip {{ in_array($option, ['Before Food', 'After Food', 'With Food'], true) ? 'is-food' : 'is-time' }} {{ in_array($option, $selectedWhen, true) ? 'is-on' : '' }}"
                                        wire:click="toggleWhen({{ $index }}, @js($option))"
                                        aria-pressed="{{ in_array($option, $selectedWhen, true) ? 'true' : 'false' }}"
                                        @disabled(! $editable)
                                    >{{ $option }}</button>
                                @endforeach
                            </div>
                            <div class="po-line-grid">
                                <div>
                                    <label class="po-stack" for="po-line-qty-{{ $line['key'] }}">Qty</label>
                                    <input id="po-line-qty-{{ $line['key'] }}" type="number" min="1" step="1" wire:model.live="billLines.{{ $index }}.qty" @disabled(! $editable)>
                                </div>
                                <div>
                                    <label class="po-stack" for="po-line-price-{{ $line['key'] }}">Unit price</label>
                                    <input id="po-line-price-{{ $line['key'] }}" type="number" min="0" step="0.01" wire:model.live="billLines.{{ $index }}.unit_price" @disabled(! $editable || ! $line['is_custom']) @readonly(! $line['is_custom'])>
                                </div>
                                <div class="po-line-sum">
                                    <p class="po-stack">Line total</p>
                                    <p class="po-line-total">₹{{ number_format($lineTotal, 2) }}</p>
                                </div>
                            </div>
                            <label class="po-stack" for="po-line-note-{{ $line['key'] }}">Special instruction</label>
                            <textarea id="po-line-note-{{ $line['key'] }}" class="po-line-note" rows="2" wire:model="billLines.{{ $index }}.special_instruction" @disabled(! $editable)></textarea>
                        </article>
                    @empty
                        <p class="po-muted">No medicines on this bill yet.</p>
                    @endforelse
                </div>

                <div class="po-totals">
                    <div class="po-line-grid">
                        <div>
                            <label class="po-stack" for="po-bill-discount">Discount</label>
                            <input id="po-bill-discount" type="number" min="0" step="0.01" wire:model.live="discount" @disabled(! $editable)>
                        </div>
                        <div>
                            <label class="po-stack" for="po-bill-delivery">Delivery fee</label>
                            <input id="po-bill-delivery" type="number" min="0" step="0.01" wire:model.live="deliveryFee" @disabled(! $editable)>
                        </div>
                    </div>
                    @if($discountTooHigh)
                        <p class="po-error" role="alert">Discount cannot exceed the subtotal.</p>
                    @endif
                    <dl class="po-facts">
                        <div><dt>Subtotal</dt><dd>₹{{ number_format($preview['subtotal'], 2) }}</dd></div>
                        <div class="po-row-discount"><dt>Discount</dt><dd>−₹{{ number_format($preview['discount'], 2) }}</dd></div>
                        <div><dt>Delivery fee</dt><dd>₹{{ number_format($preview['delivery_fee'], 2) }}</dd></div>
                        <div class="po-row-fee"><dt>GST</dt><dd>₹{{ number_format($preview['tax'], 2) }}</dd></div>
                        <div class="po-row-fee"><dt>Service charge</dt><dd>₹{{ number_format($preview['service_charge'], 2) }}</dd></div>
                        <div class="po-row-fee"><dt>Gateway charge</dt><dd>₹{{ number_format($preview['gateway_charge'], 2) }}</dd></div>
                        <div class="po-row-total"><dt>Total</dt><dd>₹{{ number_format($preview['total'], 2) }}</dd></div>
                    </dl>
                    @if($billError !== '')
                        <p class="po-error" role="alert">{{ $billError }}</p>
                    @endif
                    <div class="po-modal-actions po-bill-actions">
                        <button type="button" class="po-btn po-btn-primary po-btn-save" wire:click="saveBill" @disabled(! $editable)><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h11l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M7 3v6h8M7 21v-6h10v6"/></svg>Save bill</button>
                        @if($canRequestPayment)
                            <button type="button" class="po-btn po-btn-primary po-btn-send" wire:click="openPaymentConfirm"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h14M13 6l6 6-6 6"/></svg>Send payment request</button>
                        @else
                            <button type="button" class="po-btn po-btn-primary po-btn-send" disabled><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h14M13 6l6 6-6 6"/></svg>Send payment request</button>
                        @endif
                    </div>
                </div>
            </section>

            @if($invoice && in_array((string) $invoice->status, ['pending', 'completed'], true))
                <section class="po-card is-success" id="po-payment" aria-label="Payment">
                    <div class="po-card-head">
                        <div class="po-card-ident">
                            <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></svg></span>
                            <h2 class="po-card-title">Payment</h2>
                        </div>
                    </div>
                    <dl class="po-facts">
                        <div><dt>Invoice</dt><dd>INV#{{ $invoice->id }}</dd></div>
                        <div><dt>Amount</dt><dd>₹{{ number_format((float) $invoice->total_amount, 2) }}</dd></div>
                        <div><dt>Invoice status</dt><dd>{{ $invoice->status }}</dd></div>
                        <div><dt>Payment</dt><dd><span class="po-pill is-{{ $paymentColour }}">{{ $payment?->label() ?? '—' }}</span></dd></div>
                        <div><dt>Paid on</dt><dd>{{ $order->paid_at ? $order->paid_at->timezone(config('app.timezone'))->format('d M Y h:i A') : '—' }}</dd></div>
                    </dl>
                    @if($invoicePdfUrl)
                        <a class="po-btn po-btn-ghost" href="{{ $invoicePdfUrl }}" target="_blank" rel="noopener">Invoice PDF</a>
                    @endif
                    @if((string) $invoice->status === 'pending')
                        <button type="button" class="po-btn po-btn-ghost" wire:click="reviseBill">Edit bill (cancels the pending payment request)</button>
                    @endif
                </section>
            @endif
            </div>
        </div>

        <div class="po-show-side">
            <section class="po-card is-success" id="po-chat" aria-label="Messages">
                <div class="po-card-head">
                    <div class="po-card-ident">
                        <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-4 3v-3H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg></span>
                        <h2 class="po-card-title">Messages</h2>
                    </div>
                </div>
                <div
                    class="po-chat-wrap"
                    wire:poll.5s="refreshComments"
                    x-data="{
                        stick: true,
                        unseen: 0,
                        nearBottom() {
                            const el = this.$refs.log;
                            return el.scrollHeight - el.scrollTop - el.clientHeight < 48;
                        },
                        jump() {
                            const el = this.$refs.log;
                            el.scrollTop = el.scrollHeight;
                            this.stick = true;
                            this.unseen = 0;
                        }
                    }"
                    x-init="$nextTick(() => jump())"
                    x-on:po-chat-arrived.window="const added = Number($event.detail.count || 0); if (added < 1) return; if (stick) { $nextTick(() => jump()); } else { unseen += added; }"
                    x-on:po-chat-jump.window="$nextTick(() => jump())"
                >
                    <div
                        id="po-chat-log"
                        class="po-chat-log"
                        x-ref="log"
                        x-on:scroll="stick = nearBottom(); if (stick) unseen = 0"
                    >
                        @forelse($comments as $comment)
                            <div class="po-bubble-row is-{{ in_array($comment['author_type'], ['patient', 'pharmacist'], true) ? $comment['author_type'] : 'system' }}">
                                <span class="po-avatar is-sm" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim((string) $comment['author']), 0, 1)) }}</span>
                                <article class="po-bubble is-{{ in_array($comment['author_type'], ['patient', 'pharmacist'], true) ? $comment['author_type'] : 'system' }}" wire:key="po-comment-{{ $comment['id'] }}">
                                    <p>{{ $comment['body'] }}</p>
                                    <p class="po-muted">{{ $comment['author'] }}@if($comment['time']) · {{ $comment['time'] }}@endif</p>
                                </article>
                            </div>
                        @empty
                            <p class="po-muted">No messages yet.</p>
                        @endforelse
                    </div>
                    <button type="button" class="po-new-messages" x-show="unseen > 0" x-cloak @click="jump()">
                        <span x-text="unseen + ' new messages'"></span>
                    </button>
                </div>
                @if($chatClosed)
                    <p class="po-note">Messages are closed for this order.</p>
                    <textarea id="po-chat-composer" class="po-composer" rows="2" maxlength="1000" disabled placeholder="Messages are closed"></textarea>
                @else
                    <label class="po-sr" for="po-chat-composer">Message</label>
                    <div class="po-composer">
                        <textarea
                            id="po-chat-composer"
                            rows="2"
                            maxlength="1000"
                            wire:model="commentBody"
                            placeholder="Write a message"
                            x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.sendComment(); }"
                        ></textarea>
                        <button type="button" class="po-btn po-btn-primary po-btn-save" wire:click="sendComment"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h14M13 6l6 6-6 6"/></svg>Send</button>
                    </div>
                    <div class="po-chat-foot">
                        <span class="po-muted">{{ mb_strlen($commentBody) }}/1000</span>
                    </div>
                    @if($commentError !== '')
                        <p class="po-error" role="alert">{{ $commentError }}</p>
                    @endif
                @endif
            </section>

            <section class="po-card is-warning" id="po-timeline" aria-label="Timeline">
                <div class="po-card-head">
                    <div class="po-card-ident">
                        <span class="po-card-mark" aria-hidden="true"><svg class="po-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg></span>
                        <h2 class="po-card-title">Timeline</h2>
                    </div>
                </div>
                <ol class="po-timeline">
                    @forelse($timeline as $entry)
                        <li class="is-{{ $entry['colour'] }}" wire:key="po-log-{{ $entry['id'] }}">
                            <span class="po-pill is-{{ $entry['colour'] }}">{{ $entry['label'] }}</span>
                            <p class="po-muted">{{ $entry['actor'] }}@if($entry['time']) · {{ $entry['time'] }}@endif</p>
                            @if(filled($entry['note']))
                                <p>{{ $entry['note'] }}</p>
                            @endif
                        </li>
                    @empty
                        <li><p class="po-muted">No status changes yet.</p></li>
                    @endforelse
                </ol>
            </section>
            <div class="po-show-fill" aria-hidden="true"></div>
        </div>
    </div>

    @if($statusOrderId)
        <template x-teleport="body">
            <div id="po-show-status-modal" class="po-show" x-on:keydown.escape.window="$wire.closeStatus()">
                <div class="po-modal-backdrop" wire:click="closeStatus"></div>
                <div class="po-modal-layer">
                    <div class="po-modal" role="dialog" aria-modal="true" aria-labelledby="po-show-status-title" wire:key="po-show-status-{{ $statusOrderId }}">
                        <div class="po-modal-head">
                            <h2 id="po-show-status-title" class="po-modal-title">Update status</h2>
                            <button type="button" class="po-icon-btn" wire:click="closeStatus" aria-label="Close">&times;</button>
                        </div>
                        <p class="po-muted">{{ $statusOrderNumber }}</p>
                        @if($statusChoices === [])
                            <p>No manual status changes are available for this order.</p>
                        @else
                            <label class="po-stack" for="po-show-target-status">New status</label>
                            <select id="po-show-target-status" wire:model.live="targetStatus">
                                <option value="">Choose a status</option>
                                @foreach($statusChoices as $choice)
                                    <option value="{{ $choice['value'] }}">{{ $choice['label'] }}</option>
                                @endforeach
                            </select>
                            @if($targetStatus === 'dispatched')
                                <label class="po-stack" for="po-show-partner">Delivery partner</label>
                                <input id="po-show-partner" type="text" wire:model="deliveryPartner" maxlength="255">
                                <label class="po-stack" for="po-show-rider">Rider name</label>
                                <input id="po-show-rider" type="text" wire:model="riderName" maxlength="255">
                                <label class="po-stack" for="po-show-tracking">Tracking reference</label>
                                <input id="po-show-tracking" type="text" wire:model="trackingRef" maxlength="255">
                                <label class="po-stack" for="po-show-expected">Expected delivery</label>
                                <input id="po-show-expected" type="datetime-local" wire:model="expectedDeliveryAt">
                            @endif
                            @if(in_array($targetStatus, ['rejected', 'cancelled'], true))
                                <label class="po-stack" for="po-show-reason">Reason</label>
                                <textarea id="po-show-reason" wire:model="statusReason" rows="3" maxlength="1000"></textarea>
                            @endif
                        @endif
                        @if($statusError !== '')
                            <p class="po-error" role="alert">{{ $statusError }}</p>
                        @endif
                        <div class="po-modal-actions">
                            <button type="button" class="po-btn po-btn-ghost" wire:click="closeStatus">Cancel</button>
                            @if($statusChoices !== [])
                                <button type="button" class="po-btn po-btn-primary" wire:click="applyStatus">Save</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @if($confirmPayment)
        <template x-teleport="body">
            <div id="po-payment-modal" class="po-show" x-on:keydown.escape.window="$wire.closePaymentConfirm()">
                <div class="po-modal-backdrop" wire:click="closePaymentConfirm"></div>
                <div class="po-modal-layer">
                    <div class="po-modal" role="dialog" aria-modal="true" aria-labelledby="po-payment-title">
                        <div class="po-modal-head">
                            <h2 id="po-payment-title" class="po-modal-title">Send payment request</h2>
                            <button type="button" class="po-icon-btn" wire:click="closePaymentConfirm" aria-label="Close">&times;</button>
                        </div>
                        <p class="po-note">The patient will be asked to pay this bill.</p>
                        <dl class="po-facts">
                            @foreach($billLines as $line)
                                <div wire:key="po-pay-line-{{ $line['key'] }}">
                                    <dt>{{ $line['name'] !== '' ? $line['name'] : 'Medicine' }} × {{ (int) ($line['qty'] ?? 0) }}</dt>
                                    <dd>₹{{ number_format(round(max(0, (int) ($line['qty'] ?? 0)) * max(0, (float) ($line['unit_price'] ?? 0)), 2), 2) }}</dd>
                                </div>
                            @endforeach
                            <div><dt>Total</dt><dd>₹{{ number_format($preview['total'], 2) }}</dd></div>
                        </dl>
                        <div class="po-modal-actions">
                            <button type="button" class="po-btn po-btn-ghost" wire:click="closePaymentConfirm">Cancel</button>
                            <button type="button" class="po-btn po-btn-primary" wire:click="sendPaymentRequest">Send payment request</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @if($showQuickAdd)
        <template x-teleport="body">
            <div id="po-quick-modal" class="po-show" x-on:keydown.escape.window="$wire.closeQuickAdd()">
                <div class="po-modal-backdrop" wire:click="closeQuickAdd"></div>
                <div class="po-modal-layer">
                    <div class="po-modal" role="dialog" aria-modal="true" aria-labelledby="po-quick-title">
                        <div class="po-modal-head">
                            <h2 id="po-quick-title" class="po-modal-title">Add new medicine</h2>
                        </div>
                        <div class="po-modal-body">
                        <label class="po-stack" for="po-master-search">Search medicine master</label>
                        <input id="po-master-search" type="search" wire:model.live="masterSearch" placeholder="Name, code, brand, or strength" autocomplete="off">
                        @if($masterMatches->isNotEmpty())
                            <ul class="po-search-list">
                                @foreach($masterMatches as $master)
                                    <li wire:key="po-master-{{ $master->id }}">
                                        <button type="button" class="po-search-option" wire:click="prefillFromMaster({{ $master->id }})">
                                            <span>
                                                <strong>{{ $master->name }}</strong>
                                                <span class="po-muted">{{ $master->strength ?: '—' }} · {{ $master->dosage_form ?: '—' }} · {{ $master->brand_name ?: '—' }}</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="po-quick-fields">
                        <div class="po-span">
                        <label class="po-stack" for="po-quick-name">Name</label>
                        <input id="po-quick-name" type="text" wire:model="quickName" required>
                        </div>
                        <div>
                        <label class="po-stack" for="po-quick-strength">Strength</label>
                        <input id="po-quick-strength" type="text" wire:model="quickStrength">
                        </div>
                        <div>
                        <label class="po-stack" for="po-quick-form">Dosage form</label>
                        <input id="po-quick-form" type="text" wire:model="quickDosageForm">
                        </div>
                        <div>
                        <label class="po-stack" for="po-quick-brand">Brand</label>
                        <input id="po-quick-brand" type="text" wire:model="quickBrand">
                        </div>
                        <div>
                        <label class="po-stack" for="po-quick-price">Selling price</label>
                        <input id="po-quick-price" type="number" min="0" step="0.01" wire:model="quickSellingPrice" required>
                        </div>
                        <div>
                        <label class="po-stack" for="po-quick-mrp">MRP</label>
                        <input id="po-quick-mrp" type="number" min="0" step="0.01" wire:model="quickMrp">
                        </div>
                        <div>
                        <label class="po-stack" for="po-quick-stock">Stock</label>
                        <input id="po-quick-stock" type="number" min="0" step="1" wire:model="quickStock">
                        </div>
                        </div>
                        @if($quickError !== '')
                            <p class="po-error" role="alert">{{ $quickError }}</p>
                        @endif
                        </div>
                        <div class="po-modal-actions">
                            <button type="button" class="po-btn po-btn-ghost" wire:click="closeQuickAdd">Cancel</button>
                            <button type="button" class="po-btn po-btn-ghost" wire:click="addCustomLine">Custom line (not saved to the catalogue)</button>
                            <button type="button" class="po-btn po-btn-primary" wire:click="saveQuickAdd">Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
