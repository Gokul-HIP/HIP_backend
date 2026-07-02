@php
    $typeMeta = [
        'booking_appointment' => [
            'icon' => 'fa-calendar-check',
            'color' => '#0da2e7',
            'bg' => '#e8f6fd',
            'desc' => 'Doctor consultation redemption flow in the wallet.',
        ],
        'health_package' => [
            'icon' => 'fa-box-open',
            'color' => '#7c3aed',
            'bg' => '#ede9fe',
            'desc' => 'Health package checkup and wellness redemption.',
        ],
        'second_opinion' => [
            'icon' => 'fa-comments',
            'color' => '#d97706',
            'bg' => '#fef3c7',
            'desc' => 'Specialist second opinion coin redemption.',
        ],
        'family_plan' => [
            'icon' => 'fa-people-roof',
            'color' => '#059669',
            'bg' => '#d1fae5',
            'desc' => 'Family plan subscription coin usage.',
        ],
    ];
@endphp

<div class="hte-wrap">
    <style>
        .hte-wrap,
        .hte-wrap * { box-sizing: border-box; }
        .hte-wrap {
            --brand: var(--button-color);
            --brand-dark: var(--button-hover);
            color: #111827;
        }
        .hte-header {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid #e8ecf0;
            margin-bottom: 1rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
            flex-wrap: wrap;
        }
        .hte-header h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .hte-header p {
            font-size: .875rem;
            color: #64748b;
            margin: .35rem 0 0;
        }
        .hte-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .65rem 1.15rem;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            white-space: nowrap;
        }
        .hte-btn-primary:hover:not(:disabled) { background: var(--brand-dark); }
        .hte-btn-primary:disabled { opacity: .55; cursor: not-allowed; }
        .hte-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 768px) {
            .hte-stats { grid-template-columns: 1fr; }
        }
        .hte-stat {
            background: #fff;
            border: 1px solid #e8ecf0;
            border-radius: 14px;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .hte-stat-label {
            font-size: .75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .hte-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: .35rem;
            line-height: 1;
        }
        .hte-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        @media (max-width: 1024px) {
            .hte-grid { grid-template-columns: 1fr; }
        }
        .hte-card {
            background: #fff;
            border: 1px solid #e8ecf0;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 220px;
        }
        .hte-card.empty {
            border-style: dashed;
            border-color: #cbd5e1;
            background: #f8fafc;
        }
        .hte-card-top {
            height: 4px;
            background: var(--card-accent, var(--button-color));
        }
        .hte-card-body {
            padding: 1.25rem 1.35rem 1.35rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .hte-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .85rem;
        }
        .hte-type-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .hte-type-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .hte-type-desc {
            font-size: .8rem;
            color: #64748b;
            margin-top: .2rem;
            line-height: 1.45;
        }
        .hte-badge {
            display: inline-flex;
            align-items: center;
            font-size: .7rem;
            font-weight: 600;
            padding: .25rem .55rem;
            border-radius: 99px;
            white-space: nowrap;
        }
        .hte-badge.active { background: #e6f4ea; color: #1e7e34; }
        .hte-badge.inactive { background: #f1f5f9; color: #64748b; }
        .hte-badge.missing { background: #fff7ed; color: #c2410c; }
        .hte-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .65rem;
            margin: .5rem 0 1rem;
        }
        .hte-metric {
            background: #f8fafc;
            border-radius: 10px;
            padding: .65rem .75rem;
        }
        .hte-metric-label {
            font-size: .7rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .hte-metric-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #334155;
            margin-top: .15rem;
        }
        .hte-preview {
            font-size: .8rem;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .hte-card-actions {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding-top: .75rem;
            border-top: 1px solid #f1f5f9;
        }
        .hte-updated {
            font-size: .75rem;
            color: #94a3b8;
        }
        .hte-icon-actions { display: flex; gap: .35rem; }
        .hte-icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
        }
        .hte-icon-btn:hover { background: #e8f6fd; color: var(--button-color); border-color: #b3e3f8; }
        .hte-icon-btn.danger:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .hte-empty-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1rem .5rem;
            color: #64748b;
        }
        .hte-empty-body i {
            font-size: 1.75rem;
            color: #cbd5e1;
            margin-bottom: .65rem;
        }
        .hte-empty-body p {
            font-size: .85rem;
            margin: 0 0 .85rem;
            max-width: 220px;
            line-height: 1.45;
        }
        .hte-btn-outline {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            border-radius: 9px;
            padding: .5rem .9rem;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
        }
        .hte-btn-outline:hover {
            border-color: var(--button-color);
            color: var(--button-color);
            background: #f0f9ff;
        }
        .hte-form-section {
            background: #f8fafc;
            border: 1px solid #e8ecf0;
            border-radius: 12px;
            padding: 1rem 1.1rem;
        }
        .hte-form-section h3 {
            font-size: .95rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 .25rem;
        }
        .hte-form-section .hint {
            font-size: .75rem;
            color: #64748b;
            margin-bottom: .85rem;
        }
        .hte-input,
        .hte-select,
        .hte-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: .6rem .75rem;
            font-size: .875rem;
            background: #fff;
            color: #111827;
        }
        .hte-input:focus,
        .hte-select:focus,
        .hte-textarea:focus {
            outline: none;
            border-color: var(--button-color);
            box-shadow: 0 0 0 3px rgba(13,162,231,.12);
        }
        .hte-step-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: .85rem;
        }
        .hte-step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--button-color);
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: .15rem;
        }
        .hte-link-btn {
            font-size: .8rem;
            font-weight: 600;
            color: var(--button-color);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }
        .hte-link-btn:hover { color: var(--button-hover); }
        .hte-remove-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: #fef2f2;
            color: #dc2626;
            border-radius: 8px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .hte-remove-btn:disabled { opacity: .4; cursor: not-allowed; }
        .hte-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: .65rem;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .hte-btn-secondary {
            background: #64748b;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .6rem 1rem;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
        }
    </style>

    <div class="hte-header">
        <div>
            <h1>How to Earn Content</h1>
            <p>Manage redemption steps and terms for wallet redeem options in the mobile app.</p>
        </div>
        <button type="button" wire:click="openCreateModal"
            @disabled(count($availableTypes) === 0)
            class="hte-btn-primary">
            <i class="fas fa-plus"></i> Add Content
        </button>
    </div>

    <div class="hte-stats">
        <div class="hte-stat">
            <div class="hte-stat-label">Configured Types</div>
            <div class="hte-stat-value">{{ $totalConfigured }}<span style="font-size:1rem;color:#94a3b8;"> / 4</span></div>
        </div>
        <div class="hte-stat">
            <div class="hte-stat-label">Active</div>
            <div class="hte-stat-value" style="color:#059669;">{{ $totalActive }}</div>
        </div>
        <div class="hte-stat">
            <div class="hte-stat-label">Remaining</div>
            <div class="hte-stat-value" style="color:#d97706;">{{ $typesRemaining }}</div>
        </div>
    </div>

    <div class="hte-grid">
        @foreach($typeOptions as $typeKey => $typeLabel)
            @php
                $meta = $typeMeta[$typeKey] ?? ['icon' => 'fa-coins', 'color' => '#0da2e7', 'bg' => '#e8f6fd', 'desc' => ''];
                $content = $contentsByType->get($typeKey);
                $firstStep = $content?->how_to_earn[0]['title'] ?? null;
            @endphp

            @if($content)
                <div class="hte-card" wire:key="hte-card-{{ $typeKey }}" style="--card-accent: {{ $meta['color'] }};">
                    <div class="hte-card-top"></div>
                    <div class="hte-card-body">
                        <div class="hte-card-head">
                            <div class="flex items-start gap-3">
                                <div class="hte-type-icon" style="background: {{ $meta['bg'] }}; color: {{ $meta['color'] }};">
                                    <i class="fas {{ $meta['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="hte-type-title">{{ $typeLabel }}</div>
                                    <div class="hte-type-desc">{{ $meta['desc'] }}</div>
                                </div>
                            </div>
                            <span class="hte-badge {{ $content->is_active ? 'active' : 'inactive' }}">
                                {{ $content->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="hte-metrics">
                            <div class="hte-metric">
                                <div class="hte-metric-label">How to Earn</div>
                                <div class="hte-metric-value">{{ count($content->how_to_earn ?? []) }} steps</div>
                            </div>
                            <div class="hte-metric">
                                <div class="hte-metric-label">Terms</div>
                                <div class="hte-metric-value">{{ count($content->terms_conditions ?? []) }} items</div>
                            </div>
                        </div>

                        @if($firstStep)
                            <div class="hte-preview">
                                <strong>First step:</strong> {{ $firstStep }}
                            </div>
                        @endif

                        <div class="hte-card-actions">
                            <span class="hte-updated">Updated {{ $content->updated_at?->format('d M Y') }}</span>
                            <div class="hte-icon-actions">
                                <button type="button" class="hte-icon-btn" wire:click="openEditModal({{ $content->id }})" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="hte-icon-btn danger"
                                    wire:click="deleteContent({{ $content->id }})"
                                    wire:confirm="Delete this content?"
                                    title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="hte-card empty" wire:key="hte-empty-{{ $typeKey }}" style="--card-accent: {{ $meta['color'] }};">
                    <div class="hte-card-top" style="opacity:.35;"></div>
                    <div class="hte-card-body">
                        <div class="hte-card-head">
                            <div class="flex items-start gap-3">
                                <div class="hte-type-icon" style="background: {{ $meta['bg'] }}; color: {{ $meta['color'] }};">
                                    <i class="fas {{ $meta['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="hte-type-title">{{ $typeLabel }}</div>
                                    <div class="hte-type-desc">{{ $meta['desc'] }}</div>
                                </div>
                            </div>
                            <span class="hte-badge missing">Not set</span>
                        </div>

                        <div class="hte-empty-body">
                            <i class="fas fa-file-circle-plus"></i>
                            <p>No How to Earn steps or Terms &amp; Conditions added for this type yet.</p>
                            <button type="button" class="hte-btn-outline" wire:click="openCreateModal('{{ $typeKey }}')">
                                <i class="fas fa-plus"></i> Configure
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <flux:modal name="how-to-earn-form" class="p-0 max-w-3xl" wire:close="closeModal">
        <div class="p-6 max-h-[85vh] overflow-y-auto">
            <h2 class="text-xl font-bold text-gray-900 mb-1">{{ $contentId ? 'Edit Content' : 'Add Content' }}</h2>
            <p class="text-sm text-gray-500 mb-5">Define how users redeem HIP coins and the terms shown in the app.</p>

            <div class="space-y-5">
                <div class="hte-form-section">
                    <h3>Content Type</h3>
                    <p class="hint">Each redeem category can have only one content configuration.</p>
                    @if($contentId)
                        <div class="hte-input" style="background:#f1f5f9;border-color:#e2e8f0;font-weight:600;">
                            {{ $typeOptions[$type] ?? $type }}
                        </div>
                    @else
                        <select wire:model="type" class="hte-select">
                            <option value="">Select type</option>
                            @foreach($availableTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    @endif
                    <label class="inline-flex items-center gap-2 text-sm mt-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-500 focus:ring-sky-500">
                        <span class="text-gray-700">Active (visible in app)</span>
                    </label>
                </div>

                <div class="hte-form-section">
                    <h3>How to Earn</h3>
                    <p class="hint">Numbered steps shown as “How It Works” in the redeem flow.</p>
                    <div class="space-y-3">
                        @foreach($how_to_earn as $index => $step)
                            <div class="hte-step-card" wire:key="how-to-earn-step-{{ $index }}">
                                <div class="flex items-start gap-3">
                                    <div class="hte-step-num">{{ $index + 1 }}</div>
                                    <div class="flex-1 space-y-2">
                                        <input type="text" wire:model="how_to_earn.{{ $index }}.title"
                                            class="hte-input"
                                            placeholder="Step title (e.g. Select Redemption Amount)">
                                        @error('how_to_earn.'.$index.'.title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        <textarea wire:model="how_to_earn.{{ $index }}.description" rows="2"
                                            class="hte-textarea"
                                            placeholder="Step description"></textarea>
                                        @error('how_to_earn.'.$index.'.description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <button type="button" wire:click="removeHowToEarnRow({{ $index }})"
                                        class="hte-remove-btn" @if(count($how_to_earn) <= 1) disabled @endif>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addHowToEarnRow" class="hte-link-btn mt-3">
                        <i class="fas fa-plus"></i> Add Step
                    </button>
                </div>

                <div class="hte-form-section">
                    <h3>Terms &amp; Conditions</h3>
                    <p class="hint">Rules and notes displayed below the steps in the app.</p>
                    <div class="space-y-3">
                        @foreach($terms_conditions as $index => $term)
                            <div class="flex items-start gap-2" wire:key="terms-row-{{ $index }}">
                                <textarea wire:model="terms_conditions.{{ $index }}.description" rows="2"
                                    class="hte-textarea"
                                    placeholder="Terms &amp; conditions text"></textarea>
                                <button type="button" wire:click="removeTermsRow({{ $index }})"
                                    class="hte-remove-btn" @if(count($terms_conditions) <= 1) disabled @endif>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @error('terms_conditions.'.$index.'.description') <span class="text-red-500 text-xs block -mt-2 mb-1">{{ $message }}</span> @enderror
                        @endforeach
                    </div>
                    <button type="button" wire:click="addTermsRow" class="hte-link-btn mt-3">
                        <i class="fas fa-plus"></i> Add Term
                    </button>
                </div>
            </div>

            <div class="hte-modal-footer">
                <button type="button" wire:click="closeModal" class="hte-btn-secondary">Cancel</button>
                <button type="button" wire:click="saveContent" class="hte-btn-primary">Save Content</button>
            </div>
        </div>
    </flux:modal>
</div>
