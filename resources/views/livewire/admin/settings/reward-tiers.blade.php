@section('title', 'Reward Tiers')
@section('breadcrumb', 'Dashboard / Settings / Reward Tiers')

<div class="reward-tiers-wrap">
    <style>
        .reward-tiers-wrap,
        .reward-tiers-wrap * { box-sizing: border-box; }
        .reward-tiers-wrap {
            --brand: #0da2e7;
            --brand-dark: #0886c4;
            font-family: 'DM Sans', sans-serif;
            color: #111827;
        }
        .rt-header {
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
        .rt-header h1 { font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0; }
        .rt-header p { font-size: .85rem; color: #64748b; margin: .25rem 0 0; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: .5rem;
            background: #0da2e7; color: #fff; border: none; border-radius: 10px;
            padding: .65rem 1.25rem; font-size: .875rem; font-weight: 600;
            cursor: pointer; transition: background .15s;
        }
        .btn-primary:hover { background: #0886c4; }
        .tier-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 1024px) {
            .tier-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .tier-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .tier-card-body { padding: 1.25rem 1.5rem; flex: 1; }
        .tier-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .75rem;
        }
        .tier-name { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .tier-badge {
            display: inline-block;
            font-size: .7rem;
            font-weight: 600;
            padding: .2rem .55rem;
            border-radius: 99px;
            margin-left: .5rem;
        }
        .tier-badge.active { background: #e6f4ea; color: #1e7e34; }
        .tier-badge.inactive { background: #f1f5f9; color: #64748b; }
        .tier-actions { display: flex; gap: .35rem; }
        .tier-action-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0;
            background: #f8fafc; color: #64748b; cursor: pointer; display: flex;
            align-items: center; justify-content: center; transition: all .15s;
        }
        .tier-action-btn:hover { background: #e8f6fd; color: #0da2e7; border-color: #b3e3f8; }
        .tier-action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .min-coins-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            font-size: .75rem;
            font-weight: 600;
            padding: .3rem .65rem;
            border-radius: 8px;
            margin-bottom: .65rem;
        }
        .tier-desc {
            font-size: .85rem;
            color: #64748b;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .config-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .5rem;
            margin-bottom: 1rem;
        }
        .config-stat {
            background: #f8fafc;
            border: 1px solid #e8ecf0;
            border-radius: 10px;
            padding: .6rem .5rem;
            text-align: center;
            font-size: .72rem;
            font-weight: 600;
            color: #475569;
        }
        .package-section { margin-top: .5rem; }
        .package-label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            margin-bottom: .5rem;
        }
        .package-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
        .package-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: .3rem .6rem;
            font-size: .75rem;
            color: #334155;
        }
        .chip-free { background: #e6f4ea; color: #1e7e34; border-color: #bbf7d0; font-weight: 600; font-size: .65rem; padding: .15rem .4rem; border-radius: 4px; }
        .chip-off { background: #e8f6fd; color: #0369a1; border-color: #bae6fd; font-weight: 600; font-size: .65rem; padding: .15rem .4rem; border-radius: 4px; }
        .tier-footer {
            padding: .75rem 1.5rem;
            border-top: 1px solid #f1f5f9;
            font-size: .75rem;
            color: #94a3b8;
            background: #fafbfc;
        }
        .modal-section {
            background: #fff;
            border: 1px solid #e8ecf0;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }
        .modal-section-title {
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .form-label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: .55rem .75rem;
            font-size: .875rem;
            color: #0f172a;
            background: #fff;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #0da2e7;
            box-shadow: 0 0 0 3px rgba(13,162,231,.1);
        }
        .package-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: .5rem;
            align-items: end;
            margin-bottom: .5rem;
        }
        @media (max-width: 768px) {
            .package-row { grid-template-columns: 1fr; }
            .config-stats { grid-template-columns: 1fr; }
        }
        .empty-packages {
            text-align: center;
            padding: 1.5rem;
            color: #94a3b8;
            font-size: .875rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px dashed #e2e8f0;
        }
        .btn-remove {
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid #fecaca;
            background: #fef2f2; color: #dc2626; cursor: pointer; font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-secondary {
            background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
            border-radius: 8px; padding: .55rem 1rem; font-size: .875rem; font-weight: 600; cursor: pointer;
        }
    </style>

    @php
        $accentColors = [
            1 => '#cd7f32',
            2 => '#9ca3af',
            3 => '#f59e0b',
            4 => '#38bdf8',
        ];
    @endphp

    <div class="rt-header">
        <div>
            <h1>Reward Tiers</h1>
            <p>Configure loyalty reward tiers and benefits</p>
        </div>
        <button type="button" class="btn-primary" wire:click="openCreate">
            <i class="fas fa-plus"></i> Add Tier
        </button>
    </div>

    <div class="tier-grid">
        @forelse ($tiers as $tier)
            @php
                $accent = $accentColors[$tier->sort_order] ?? '#a855f7';
                $config = $tier->config;
            @endphp
            <div class="tier-card" style="border-left: 4px solid {{ $accent }};">
                <div class="tier-card-body">
                    <div class="tier-card-header">
                        <div>
                            <span class="tier-name">{{ $tier->name }}</span>
                            <span class="tier-badge {{ $tier->is_active ? 'active' : 'inactive' }}">
                                {{ $tier->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="min-coins-badge">From {{ number_format($tier->min_coins) }} coins</div>
                        </div>
                        <div class="tier-actions">
                            <button type="button" class="tier-action-btn" wire:click="openEdit({{ $tier->id }})" title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="tier-action-btn delete" wire:click="confirmDelete({{ $tier->id }})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    @if ($tier->description)
                        <p class="tier-desc">{{ $tier->description }}</p>
                    @endif

                    <div class="config-stats">
                        <div class="config-stat">🎯 {{ $config?->total_discount_percentage ?? 0 }}% Discount</div>
                        <div class="config-stat">🆓 {{ (int) ($config?->free_checkup_count ?? 0) }} Free Checkups</div>
                        <div class="config-stat">🪙 {{ (int) ($config?->earned_coins_per_booking ?? 0) }} Coins/Booking</div>
                    </div>

                    @if ($tier->packageDiscounts->isNotEmpty())
                        <div class="package-section">
                            <div class="package-label">Package Benefits</div>
                            <div class="package-chips">
                                @foreach ($tier->packageDiscounts as $discount)
                                    <span class="package-chip">
                                        {{ $discount->diagnosticPackage?->name ?? 'Package #'.$discount->diagnostic_package_id }}
                                        @if ($discount->promotion_type === 'free')
                                            <span class="chip-free">Free</span>
                                        @else
                                            <span class="chip-off">
                                                @if ($discount->discount_type === 'flat')
                                                    ₹{{ number_format((float) $discount->discount_value, 0) }} off
                                                @else
                                                    {{ rtrim(rtrim(number_format((float) $discount->discount_value, 2), '0'), '.') }}% off
                                                @endif
                                            </span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="tier-footer">Sort order: {{ $tier->sort_order }}</div>
            </div>
        @empty
            <div class="tier-card" style="grid-column: 1 / -1;">
                <div class="tier-card-body" style="text-align:center; padding: 3rem;">
                    <p style="color:#94a3b8; margin:0;">No reward tiers configured yet. Click "+ Add Tier" to create one.</p>
                </div>
            </div>
        @endforelse
    </div>

    <flux:modal name="reward-tier-modal" class="p-0 !max-w-3xl" wire:close="closeModal">
        <div style="padding:1.5rem;">
            <h2 style="font-size:1.15rem; font-weight:700; margin:0 0 1.25rem; color:#0f172a;">
                {{ $editingId ? 'Edit Reward Tier' : 'Add Reward Tier' }}
            </h2>

            <div class="modal-section">
                <div class="modal-section-title">Tier Details</div>
                <div style="display:grid; grid-template-columns:1fr auto; gap:1rem; margin-bottom:1rem;">
                    <div>
                        <label class="form-label">Name</label>
                        <input type="text" class="form-input" wire:model="name" placeholder="e.g. Bronze">
                        @error('name') <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="width:6rem;">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-input" wire:model="sort_order" min="1">
                        @error('sort_order') <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div>
                        <label class="form-label">Min Coins Required</label>
                        <input type="number" class="form-input" wire:model="min_coins" min="0">
                        @error('min_coins') <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="display:flex; align-items:center; padding-top:1.5rem;">
                        <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer; font-size:.875rem;">
                            <input type="checkbox" wire:model="is_active">
                            <span>Is Active</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" wire:model="description" rows="3" placeholder="Optional description"></textarea>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Reward Configuration</div>
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">
                    <div>
                        <label class="form-label">Total Discount %</label>
                        <input type="number" step="0.01" class="form-input" wire:model="total_discount_percentage" min="0" max="100">
                        @error('total_discount_percentage') <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="form-label">Free Checkups</label>
                        <input type="number" class="form-input" wire:model="free_checkup_count" min="0">
                        @error('free_checkup_count') <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="form-label">Coins Earned per Booking</label>
                        <input type="number" class="form-input" wire:model="earned_coins_per_booking" min="0">
                        @error('earned_coins_per_booking') <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <div class="modal-section-title" style="margin:0;">Package Discounts</div>
                    <button type="button" class="btn-secondary" wire:click="addPackageRow">
                        <i class="fas fa-plus"></i> Add Package
                    </button>
                </div>

                @forelse ($packageDiscounts as $index => $row)
                    <div class="package-row" wire:key="package-row-{{ $index }}">
                        <div>
                            <label class="form-label">Package</label>
                            <select class="form-select" wire:model="packageDiscounts.{{ $index }}.diagnostic_package_id">
                                <option value="">Select package</option>
                                @foreach ($allPackages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                                @endforeach
                            </select>
                            @error('packageDiscounts.'.$index.'.diagnostic_package_id')
                                <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label">Promotion</label>
                            <select class="form-select" wire:model.live="packageDiscounts.{{ $index }}.promotion_type">
                                <option value="discount">Discount</option>
                                <option value="free">Free</option>
                            </select>
                        </div>
                        @if (($row['promotion_type'] ?? 'discount') === 'discount')
                            <div>
                                <label class="form-label">Type</label>
                                <select class="form-select" wire:model="packageDiscounts.{{ $index }}.discount_type">
                                    <option value="percentage">Percentage</option>
                                    <option value="flat">Flat</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Value</label>
                                <input type="number" step="0.01" class="form-input" wire:model="packageDiscounts.{{ $index }}.discount_value" min="0">
                                @error('packageDiscounts.'.$index.'.discount_value')
                                    <span style="color:#dc2626; font-size:.75rem;">{{ $message }}</span>
                                @enderror
                            </div>
                        @else
                            <div></div>
                            <div></div>
                        @endif
                        <button type="button" class="btn-remove" wire:click="removePackageRow({{ $index }})" title="Remove">&times;</button>
                    </div>
                @empty
                    <div class="empty-packages">No package discounts configured</div>
                @endforelse
            </div>

            <div style="display:flex; justify-content:flex-end; gap:.75rem; margin-top:1rem;">
                <button type="button" class="btn-secondary" wire:click="closeModal">Cancel</button>
                <button type="button" class="btn-primary" wire:click="save">Save Tier</button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-reward-tier" class="p-0" wire:close="closeDelete">
        <div style="padding:1.5rem;">
            <h3 style="font-size:1rem; font-weight:700; margin:0 0 .5rem;">Delete Reward Tier?</h3>
            <p style="font-size:.875rem; color:#64748b; margin:0 0 1.25rem;">This action cannot be undone. Users on this tier may be affected.</p>
            <div style="display:flex; justify-content:flex-end; gap:.75rem;">
                <button type="button" class="btn-secondary" wire:click="closeDelete">Cancel</button>
                <button type="button" class="btn-primary" style="background:#dc2626;" wire:click="deleteTier">Delete</button>
            </div>
        </div>
    </flux:modal>
</div>
