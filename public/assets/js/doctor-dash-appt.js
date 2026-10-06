window.DoctorDashAppt = (function () {
    const charts = {};
    let legendHidden = { completed: false, follow_up: false, cancelled: false };
    let lastReport = null;
    let releaseFocusTrap = null;

    function cssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    function colors() {
        return {
            brand: cssVar('--primary-color') || '#0da2e7',
            success: cssVar('--success-color') || '#22C55E',
            danger: cssVar('--danger-color') || '#EF4444',
            warning: cssVar('--warning-color') || '#F59E0B',
            info: cssVar('--info-color') || '#6366f1',
            muted: cssVar('--muted-color') || '#6b7280',
            ink: cssVar('--text-color') || '#1a1a2e',
            card: cssVar('--card-bg') || '#ffffff',
        };
    }

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function tooltip() {
        const c = colors();
        return {
            backgroundColor: c.ink,
            titleColor: c.card,
            bodyColor: c.card,
            displayColors: true,
        };
    }

    function stackedBar(canvas, report) {
        if (!canvas || typeof Chart === 'undefined') return;
        destroyChart('bar');
        const labels = (report.series || []).map((row) => row.label);
        const completed = (report.series || []).map((row) => legendHidden.completed ? 0 : row.completed);
        const follow = (report.series || []).map((row) => legendHidden.follow_up ? 0 : row.follow_up);
        const cancelled = (report.series || []).map((row) => legendHidden.cancelled ? 0 : row.cancelled);
        const c = colors();
        charts.bar = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Completed', data: completed, backgroundColor: c.success, stack: 'a' },
                    { label: 'Follow-up', data: follow, backgroundColor: c.info, stack: 'a' },
                    { label: 'Cancelled', data: cancelled, backgroundColor: c.danger, stack: 'a' },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: tooltip() },
                scales: {
                    x: { stacked: true, ticks: { color: c.muted }, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0, color: c.muted } },
                },
            },
        });
    }

    function donut(canvas, report) {
        if (!canvas || typeof Chart === 'undefined') return;
        destroyChart('donut');
        const c = colors();
        const values = (report.donut || []).map((row) => row.value);
        charts.donut = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: (report.donut || []).map((row) => row.label),
                datasets: [{
                    data: values,
                    backgroundColor: [c.success, c.info, c.danger],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false }, tooltip: tooltip() },
            },
        });
        const totalEl = document.querySelector('[data-donut-total]');
        if (totalEl) totalEl.textContent = String(report.donut_total || 0);
    }

    function line(canvas, report) {
        if (!canvas || typeof Chart === 'undefined') return;
        destroyChart('line');
        const c = colors();
        charts.line = new Chart(canvas, {
            type: 'line',
            data: {
                labels: (report.series || []).map((row) => row.label),
                datasets: [
                    {
                        label: 'Online',
                        data: (report.series || []).map((row) => row.online),
                        borderColor: c.brand,
                        backgroundColor: c.brand,
                        tension: 0.35,
                        fill: false,
                    },
                    {
                        label: 'Clinic',
                        data: (report.series || []).map((row) => row.clinic),
                        borderColor: c.warning,
                        backgroundColor: c.warning,
                        tension: 0.35,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: tooltip() },
                scales: {
                    x: { ticks: { color: c.muted }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: c.muted } },
                },
            },
        });
    }

    function syncLegend() {
        document.querySelectorAll('[data-legend-key]').forEach((btn) => {
            const key = btn.getAttribute('data-legend-key');
            btn.classList.toggle('is-off', !!legendHidden[key]);
            btn.setAttribute('aria-pressed', legendHidden[key] ? 'false' : 'true');
        });
    }

    function renderDashboard(report) {
        lastReport = report || lastReport;
        if (!lastReport) return;
        stackedBar(document.getElementById('doc-dash-bar'), lastReport);
        donut(document.getElementById('doc-dash-donut'), lastReport);
        line(document.getElementById('doc-dash-line'), lastReport);
        syncLegend();
    }

    function toggleLegend(key) {
        const visible = Object.keys(legendHidden).filter((k) => !legendHidden[k]);
        if (!legendHidden[key] && visible.length === 1) return;
        legendHidden[key] = !legendHidden[key];
        renderDashboard(lastReport);
    }

    function exportCsv() {
        const table = document.getElementById('doc-dash-report-table');
        if (!table) return;
        const rows = Array.from(table.querySelectorAll('tr')).map((tr) =>
            Array.from(tr.querySelectorAll('th,td'))
                .map((cell) => '"' + cell.innerText.replace(/"/g, '""').trim() + '"')
                .join(',')
        );
        const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'appointments-report.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function printReport() {
        window.print();
    }

    function trapModal(overlay) {
        if (releaseFocusTrap) {
            releaseFocusTrap();
            releaseFocusTrap = null;
        }
        if (!overlay) return;
        const focusable = overlay.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const previous = document.activeElement;
        if (first) first.focus();

        function onKey(event) {
            if (event.key === 'Escape') {
                overlay.dispatchEvent(new CustomEvent('doc-appt-close', { bubbles: true }));
                return;
            }
            if (event.key !== 'Tab' || focusable.length === 0) return;
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        document.addEventListener('keydown', onKey);
        releaseFocusTrap = function () {
            document.removeEventListener('keydown', onKey);
            if (previous && typeof previous.focus === 'function') previous.focus();
        };
    }

    function releaseModal() {
        if (releaseFocusTrap) {
            releaseFocusTrap();
            releaseFocusTrap = null;
        }
    }

    document.addEventListener('click', function (event) {
        const legend = event.target.closest('[data-legend-key]');
        if (legend) {
            event.preventDefault();
            toggleLegend(legend.getAttribute('data-legend-key'));
        }
        if (event.target.closest('[data-doc-export-csv]')) {
            event.preventDefault();
            exportCsv();
        }
        if (event.target.closest('[data-doc-print]')) {
            event.preventDefault();
            printReport();
        }
    });

    return {
        renderDashboard,
        trapModal,
        releaseModal,
        exportCsv,
        printReport,
    };
})();
