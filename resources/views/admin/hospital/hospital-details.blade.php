{{-- resources/views/admin/organization_show.blade.php --}}
@extends('layouts.admin')

{{-- @section('title', $organization->name) --}}
@section('breadcrumb', 'Dashboard / ORGANIZATION / Hospital Details')

@section('content')

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

@livewire('admin.organization.hospital.hospital-details',['hospitalId' => request()->id])

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Month dropdown
    const monthBtn   = document.getElementById('monthBtn');
    const monthMenu  = document.getElementById('monthMenu');
    const monthLabel = document.getElementById('monthLabel');

    monthBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        monthMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => monthMenu.classList.add('hidden'));

    monthMenu.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
            monthLabel.textContent = btn.dataset.value;
            monthMenu.classList.add('hidden');
        });
    });

    // Shared chart options
    function chartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true }
            }
        };
    }

    // Income bar chart
    new Chart(document.getElementById('incomeChart'), {
        type: 'bar',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Income',
                data: [12000, 18000, 9000, 15000],
                backgroundColor: '#0da2e7',
                borderRadius: 6,
                barThickness: 32
            }]
        },
        options: chartOptions()
    });

    // Daily transaction line chart
    new Chart(document.getElementById('transactionChart'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [
                {
                    label: 'Success',
                    data: [450, 520, 480, 600, 550, 620, 580],
                    borderColor: '#0da2e7',
                    borderWidth: 3,
                    tension: .35,
                    pointRadius: 3
                },
                {
                    label: 'Failed',
                    data: [50, 30, 45, 60, 40, 35, 42],
                    borderColor: '#ef4444',
                    borderWidth: 3,
                    tension: .35,
                    pointRadius: 3
                }
            ]
        },
        options: chartOptions()
    });

    // Revenue vs Refunds
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
            datasets: [
                {
                    label: 'Revenue',
                    data: [300,450,800,650,550,600,500],
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(34,197,94,0.18)',
                    fill: true,
                    borderWidth: 3,
                    tension: .35,
                    pointRadius: 3
                },
                {
                    label: 'Refunds',
                    data: [50,30,120,200,80,60,40],
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(251,146,60,0.18)',
                    fill: true,
                    borderWidth: 3,
                    tension: .35,
                    pointRadius: 3
                }
            ]
        },
        options: chartOptions()
    });

    // Number of Transactions
    new Chart(document.getElementById('transactionsChart'), {
        type: 'line',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
            datasets: [{
                label: 'Transactions',
                data: [300,420,680,520,610,480,760],
                borderColor: '#475569',
                borderDash: [5,5],
                borderWidth: 3,
                tension: .3,
                pointRadius: 3
            }]
        },
        options: chartOptions()
    });

    // Feedback doughnut
    new Chart(document.getElementById('feedbackChart'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [72,18,10],
                backgroundColor: ['#34d399','#fbbf24','#f87171']
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });
</script>
@endpush

@endsection
