<div class="doc-dash" wire:loading.class="is-loading" wire:target="setRange">
    <div class="doc-dash-page-header">
        <div>
            <h1>{{ $report['greeting'] ?? 'Good morning' }}, {{ $report['doctor_name'] ?? ($doctorFirstName.' '.$doctorLastName) }}</h1>
            <p>You have {{ (int) ($report['kpis']['today'] ?? 0) }} appointment{{ ((int) ($report['kpis']['today'] ?? 0)) === 1 ? '' : 's' }} scheduled for today.</p>
        </div>
        <div class="doc-dash-period" role="group" aria-label="Report period">
            @foreach([1 => 'Last month', 3 => '3 months', 6 => '6 months', 9 => '9 months'] as $value => $label)
                <button type="button"
                        wire:click="setRange({{ $value }})"
                        class="{{ (int) $range === (int) $value ? 'is-active' : '' }}"
                        aria-pressed="{{ (int) $range === (int) $value ? 'true' : 'false' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        {{-- <button class="btn-new-consultation" wire:click="newConsultation">
            <i class="fas fa-plus"></i> New Consultation
        </button> --}}
    </div>

    <div class="doc-dash-kpis">
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-today"><i class="fas fa-calendar-day"></i></div>
                <span class="doc-kpi-chip">Today</span>
            </div>
            <div class="doc-kpi-label">Today's Appointments</div>
            <div class="doc-kpi-number">{{ (int) ($report['kpis']['today'] ?? 0) }}</div>
        </div>
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-follow"><i class="fas fa-redo"></i></div>
                <span class="doc-kpi-chip">Due this week</span>
            </div>
            <div class="doc-kpi-label">Follow-up</div>
            <div class="doc-kpi-number">{{ (int) ($report['kpis']['follow_up'] ?? 0) }}</div>
        </div>
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-upcoming"><i class="fas fa-clock"></i></div>
                <span class="doc-kpi-chip">Next 7 days</span>
            </div>
            <div class="doc-kpi-label">Upcoming</div>
            <div class="doc-kpi-number">{{ (int) ($report['kpis']['upcoming'] ?? 0) }}</div>
        </div>
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-completed"><i class="fas fa-check-circle"></i></div>
                <span class="doc-kpi-chip">All</span>
            </div>
            <div class="doc-kpi-label">Completed</div>
            <div class="doc-kpi-number">{{ (int) ($report['kpis']['completed'] ?? 0) }}</div>
        </div>
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-cancelled"><i class="fas fa-times-circle"></i></div>
                <span class="doc-kpi-chip">{{ $report['period_label'] ?? '3 months' }}</span>
            </div>
            <div class="doc-kpi-label">Cancelled</div>
            <div class="doc-kpi-number">{{ (int) ($report['kpis']['cancelled'] ?? 0) }}</div>
        </div>
        {{-- <div class="dd-stat-card">
            <div class="dd-stat-top">
                <div class="dd-stat-icon dd-icon-orange"><i class="fas fa-file-prescription"></i></div>
                <span class="dd-stat-badge badge-red">Urgent</span>
            </div>
            <div class="dd-stat-label">Pending Prescriptions</div>
            <div class="dd-stat-number">{{ $pendingPrescriptionsCount ?? 3 }}</div>
        </div> --}}
    </div>

    <div class="doc-dash-charts">
        <section class="doc-card">
            <div class="doc-card-head">
                <h2>Appointments report</h2>
                <div class="doc-legend">
                    <button type="button" data-legend-key="completed" aria-pressed="true"><i class="doc-legend-dot is-completed"></i> Completed</button>
                    <button type="button" data-legend-key="follow_up" aria-pressed="true"><i class="doc-legend-dot is-follow"></i> Follow-up</button>
                    <button type="button" data-legend-key="cancelled" aria-pressed="true"><i class="doc-legend-dot is-cancelled"></i> Cancelled</button>
                </div>
            </div>
            <div class="doc-chart-canvas" wire:ignore>
                <canvas id="doc-dash-bar" aria-label="Appointments report chart"></canvas>
            </div>
        </section>
        <section class="doc-card">
            <div class="doc-card-head">
                <h2>Status split</h2>
            </div>
            @if((int) ($report['donut_total'] ?? 0) === 0)
                <div class="doc-dash-empty">
                    <h3>No report data</h3>
                    <p>There are no completed, follow-up, or cancelled appointments in this period.</p>
                </div>
            @endif
            <div class="doc-donut-wrap" @if((int) ($report['donut_total'] ?? 0) === 0) hidden @endif>
                <div class="doc-chart-canvas" wire:ignore>
                    <canvas id="doc-dash-donut" aria-label="Status split chart"></canvas>
                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;pointer-events:none;">
                        <strong data-donut-total>{{ (int) ($report['donut_total'] ?? 0) }}</strong>
                        <span class="doc-kpi-chip">Total</span>
                    </div>
                </div>
                <ul class="doc-donut-legend">
                    @foreach(($report['donut'] ?? []) as $slice)
                        <li>
                            <span>{{ $slice['label'] }}</span>
                            <strong>{{ (int) $slice['value'] }} · {{ (int) $slice['pct'] }}%</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </div>

    <div class="doc-dash-grid-2">
        <section class="doc-card">
            <div class="doc-card-head">
                <h2>Online vs clinic visits</h2>
                <div class="doc-legend">
                    <span><i class="doc-legend-dot is-online"></i> Online</span>
                    <span><i class="doc-legend-dot is-clinic"></i> Clinic</span>
                </div>
            </div>
            <div class="doc-chart-canvas" wire:ignore>
                <canvas id="doc-dash-line" aria-label="Online versus clinic visits"></canvas>
            </div>
        </section>
        <section class="doc-card">
            <div class="doc-card-head">
                <h2>Today's schedule</h2>
                <a class="doc-link" href="{{ route('doctor.my-appointment.index', ['tab' => 'today']) }}">View all</a>
            </div>
            <div class="doc-schedule-list">
                @forelse(($report['today_schedule'] ?? []) as $item)
                    <button type="button"
                            class="doc-schedule-item"
                            wire:key="dash-sched-{{ $item['id'] }}"
                            wire:click="viewAppointment({{ $item['id'] }})">
                        <div class="doc-schedule-time">{{ $item['time'] }}</div>
                        <div>
                            <div class="doc-schedule-name">{{ $item['patient_name'] }}</div>
                            <div class="doc-schedule-meta">{{ $item['reason'] }}</div>
                        </div>
                        <div class="doc-schedule-meta">
                            @if(!empty($item['is_online']))
                                <i class="fas fa-video" aria-hidden="true"></i>
                            @endif
                            {{ $item['mode'] }}
                        </div>
                    </button>
                @empty
                    <div class="doc-dash-empty">
                        <h3>No appointments today</h3>
                        <p>There is nothing on today's schedule.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="doc-card">
        <div class="doc-card-head">
            <h2>Monthly report</h2>
            <div class="doc-card-actions">
                <button type="button" class="doc-btn" data-doc-export-csv>Export CSV</button>
                <button type="button" class="doc-btn doc-btn-primary" data-doc-print>Print</button>
            </div>
        </div>
        <div class="doc-table-wrap">
            <table class="doc-table" id="doc-dash-report-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Booked</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                        <th>Follow-ups</th>
                        <th>Online</th>
                        <th>Completion %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($report['table'] ?? []) as $row)
                        <tr>
                            <td>{{ $row['period'] }}</td>
                            <td>{{ (int) $row['booked'] }}</td>
                            <td>{{ (int) $row['completed'] }}</td>
                            <td>{{ (int) $row['cancelled'] }}</td>
                            <td>{{ (int) $row['follow_ups'] }}</td>
                            <td>{{ (int) $row['online'] }}</td>
                            <td>
                                <div class="doc-progress">
                                    <div class="doc-progress-track">
                                        <div class="doc-progress-fill" style="width: {{ (int) $row['completion_pct'] }}%;"></div>
                                    </div>
                                    {{ (int) $row['completion_pct'] }}%
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="doc-dash-empty">No rows for the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(!empty($report['table']))
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td>{{ (int) ($report['totals']['booked'] ?? 0) }}</td>
                            <td>{{ (int) ($report['totals']['completed'] ?? 0) }}</td>
                            <td>{{ (int) ($report['totals']['cancelled'] ?? 0) }}</td>
                            <td>{{ (int) ($report['totals']['follow_ups'] ?? 0) }}</td>
                            <td>{{ (int) ($report['totals']['online'] ?? 0) }}</td>
                            <td>{{ (int) ($report['totals']['completion_pct'] ?? 0) }}%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    {{-- <div class="dd-footer">
        © {{ date('Y') }} Nano Hospitals HIPAA Compliant Provider Portal. Secure Session Active.
    </div> --}}
</div>

@assets
    <script src="{{ versioned_asset('assets/js/doctor-dash-appt.js') }}"></script>
@endassets

@script
<script>
    const render = (payload) => {
        const report = payload?.report ?? payload ?? $wire.report;
        window.DoctorDashAppt?.renderDashboard(report);
    };

    $wire.on('doctor-dashboard-charts', (payload) => render(payload));
    queueMicrotask(() => render({ report: $wire.report }));
</script>
@endscript
