<div class="review-details-wrap">

    <style>
        .review-details-wrap {
            font-family: 'DM Sans', 'Segoe UI', sans-serif;
            color: #1a2332;
            padding: 28px 32px;
            background: #f4f6f9;
            min-height: 100vh;
        }

        /* ── Header bar ── */
        .rdw-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .rdw-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #0f1f35;
            letter-spacing: -.3px;
        }
        .rdw-header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .rdw-toggle-group {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: .82rem;
            font-weight: 500;
            color: #475569;
        }
        .toggle-switch {
            position: relative;
            width: 42px;
            height: 24px;
            flex-shrink: 0;
        }
        .toggle-switch input { display: none; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #22c55e;
            border-radius: 99px;
            cursor: pointer;
            transition: background .2s;
        }
        .toggle-slider::after {
            content: '';
            position: absolute;
            top: 3px; left: 3px;
            width: 18px; height: 18px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
        .toggle-switch input:not(:checked) + .toggle-slider { background: #cbd5e1; }
        .toggle-switch input:not(:checked) + .toggle-slider::after { transform: translateX(0); }
        .toggle-switch input:checked + .toggle-slider::after { transform: translateX(18px); }

        .btn-delete {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 8px;
            padding: 7px 16px;
            font-size: .83rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }
        .btn-delete:hover { background: #fef2f2; border-color: #dc2626; }
        .btn-delete svg { width: 15px; height: 15px; }

        /* ── Status bar ── */
        .rdw-status-bar {
            display: flex;
            align-items: center;
            gap: 24px;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 11px 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            font-size: .84rem;
        }
        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #16a34a;
        }
        .status-dot {
            width: 9px; height: 9px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 0 3px #bbf7d0;
        }
        .status-divider {
            width: 1px; height: 18px;
            background: #86efac;
        }
        .status-meta {
            color: #15803d;
            font-weight: 500;
        }
        .status-meta span { color: #166534; font-weight: 600; }

        /* ── Cards ── */
        .rdw-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            padding: 24px 28px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(15,31,53,.04);
        }
        .rdw-card-title {
            font-size: .95rem;
            font-weight: 700;
            color: #0f1f35;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* ── Posting Details grid ── */
        .posting-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 640px) { .posting-grid { grid-template-columns: 1fr; } }
        .posting-field label {
            font-size: .75rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .6px;
            display: block;
            margin-bottom: 4px;
        }
        .posting-field p {
            font-size: .9rem;
            font-weight: 500;
            color: #1e293b;
            margin: 0;
        }

        /* ── Reviewer card ── */
        .reviewer-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .reviewer-avatar {
            width: 50px; height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: #475569;
            flex-shrink: 0;
        }
        .reviewer-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .reviewer-name {
            font-size: 1rem;
            font-weight: 700;
            color: #0f1f35;
            margin: 0 0 3px;
        }
        .reviewer-meta {
            font-size: .78rem;
            color: #94a3b8;
            margin: 0;
        }
        .review-rating {
            margin-bottom: 10px;
        }
        .review-rating label {
            font-size: .78rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .5px;
            display: block;
            margin-bottom: 4px;
        }
        .stars { color: #f59e0b; font-size: 1rem; letter-spacing: 2px; }
        .star-label {
            font-size: .82rem;
            font-weight: 600;
            color: #f59e0b;
            margin-left: 5px;
        }
        .review-text-label {
            font-size: .78rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 8px;
        }
        .review-text {
            font-size: .88rem;
            line-height: 1.7;
            color: #334155;
        }
        .review-text p { margin: 0 0 10px; }
        .review-text p:last-child { margin-bottom: 0; }
        .reviewer-card-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
            font-size: .82rem;
            font-weight: 500;
            color: #64748b;
        }

        /* ── Stats row ── */
        .stats-row {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 22px;
            text-align: center;
            min-width: 110px;
        }
        .stat-chip-label {
            font-size: .72rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 4px;
        }
        .stat-chip-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f1f35;
            line-height: 1;
        }

        /* ── Review list items ── */
        .review-list { display: flex; flex-direction: column; gap: 0; }
        .review-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 12px;
        }
        .review-item:last-child { border-bottom: none; padding-bottom: 0; }
        .review-item-left { flex: 1; min-width: 0; }
        .review-item-name {
            font-size: .88rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 2px;
        }
        .review-item-id {
            font-size: .75rem;
            color: #94a3b8;
            margin: 0 0 6px;
        }
        .review-item-text {
            font-size: .82rem;
            color: #64748b;
            line-height: 1.5;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .review-item-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            flex-shrink: 0;
        }
        .review-item-time {
            font-size: .75rem;
            color: #94a3b8;
            white-space: nowrap;
        }
        .active-label {
            font-size: .72rem;
            font-weight: 600;
            color: #16a34a;
        }
        .toggle-sm {
            position: relative;
            width: 36px;
            height: 20px;
        }
        .toggle-sm input { display: none; }
        .toggle-sm-slider {
            position: absolute;
            inset: 0;
            background: #22c55e;
            border-radius: 99px;
            cursor: pointer;
        }
        .toggle-sm-slider::after {
            content: '';
            position: absolute;
            top: 2px; left: 2px;
            width: 16px; height: 16px;
            background: #fff;
            border-radius: 50%;
            transform: translateX(16px);
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }

        /* ── Delete Modal ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            backdrop-filter: blur(2px);
        }
        .modal-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1);
            min-width: 380px;
            max-width: 500px;
            animation: slideUp .2s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 24px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f1f35;
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: color .15s;
        }
        .modal-close:hover { color: #0f1f35; }
        .modal-close svg { width: 20px; height: 20px; }
        .modal-body {
            padding: 20px 24px;
            color: #475569;
            font-size: .95rem;
            line-height: 1.6;
        }
        .modal-body p { margin: 0; }
        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px 24px;
        }
        .btn-cancel {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #475569;
            border-radius: 8px;
            padding: 9px 18px;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }
        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .btn-confirm-delete {
            background: #dc2626;
            border: 1px solid #dc2626;
            color: #fff;
            border-radius: 8px;
            padding: 9px 18px;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }
        .btn-confirm-delete:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }
        .btn-confirm-delete:active {
            transform: scale(0.98);
        }
    </style>

    {{-- ── Page header ── --}}
    <div class="rdw-header">
        <h1 class="rdw-title">Review Details - #{{ $review->id }}</h1>
        <div class="rdw-header-actions">
            <div class="rdw-toggle-group">
                Set inactive
                <label class="toggle-switch">
                    <input type="checkbox" @if($isActive) checked @endif wire:model.live="isActive">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <button class="btn-delete" wire:click="openDeleteModal">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
            </button>
        </div>
    </div>

    {{-- ── Status bar ── --}}
    <div class="rdw-status-bar">
        <div class="status-badge">
            <span class="status-dot"></span>
            {{ ucfirst($review->status) }}
        </div>
        <div class="status-divider"></div>
        <div class="status-meta">Review Posted At: <span>{{ $review->created_at->format('M d, Y h:i A') }}</span></div>
        <div class="status-divider"></div>
        <div class="status-meta">For: <span>{{ $review->hospital->name ?? 'N/A' }}</span></div>
    </div>

    {{-- ── Posting Details ── --}}
    <div class="rdw-card">
        <div class="rdw-card-title">Posting Details</div>
        <div class="posting-grid">
            <div class="posting-field">
                <label>Posted For</label>
                <p>Hospital</p>
            </div>
            <div class="posting-field">
                <label>Hospital Name</label>
                <p>{{ $review->hospital->name ?? 'N/A' }}</p>
            </div>
            <div class="posting-field">
                <label>Posted Time</label>
                <p>{{ $review->created_at->format('M d Y – h:i A') }}</p>
            </div>
        </div>
    </div>

    {{-- ── Reviewer card ── --}}
    <div class="rdw-card">
        <div class="reviewer-header">
            <div class="reviewer-avatar">
                @if($review->member && $review->member->profile_image)
                    <img src="{{ asset($review->member->profile_image) }}" alt="{{ $review->member->name }}">
                @else
                    {{ substr($review->member->name ?? 'RM', 0, 2) }}
                @endif
            </div>
            <div>
                <p class="reviewer-name">{{ $review->member->name ?? 'N/A' }}</p>
                <p class="reviewer-meta">{{ $review->member->hip_id ?? 'N/A' }} &nbsp;·&nbsp; {{ $review->member->reviews_count ?? 0 }} Reviews &nbsp;·&nbsp; {{ $review->rating }} Rating</p>
            </div>
        </div>

        <div class="review-rating">
            <label>Rating</label>
            <span class="stars">@for($i = 0; $i < $review->rating; $i++)★@endfor</span>
            <span class="star-label">{{ $review->rating }} Star</span>
        </div>

        <div class="review-text-label">Review</div>
        <div class="review-text">
            {!! nl2br(e($review->review)) !!}
        </div>

        <div class="reviewer-card-footer">
            Set inactive
            <label class="toggle-switch" style="width:42px;height:24px;">
                <input type="checkbox" @if($reviewActive) checked @endif wire:model.live="reviewActive">
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>

    {{-- ── Delete Confirmation Modal ── --}}
    @if($showDeleteModal)
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-header">
                    <h2 class="modal-title">Delete Review</h2>
                    <button class="modal-close" wire:click="closeDeleteModal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this review? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-cancel" wire:click="closeDeleteModal">Cancel</button>
                    <button class="btn-confirm-delete" wire:click="confirmDelete">Delete Review</button>
                </div>
            </div>
        </div>
    @endif

</div>