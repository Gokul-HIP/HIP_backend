<?php

namespace App\Livewire\HospitalAdmin\Transaction;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Transactions;
use App\Support\TransactionReportHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $serviceTypeFilter = 'all';
    public string $statusFilter = 'all';
    public string $hospitalFilter = 'all';
    public string $fromDate = '';
    public string $toDate = '';

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingServiceTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingHospitalFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
    }

    public function updatingToDate(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->serviceTypeFilter = 'all';
        $this->statusFilter = 'all';
        $this->hospitalFilter = 'all';
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function filteredTransactionsQuery(): Builder
    {
        $hospitalIds = $this->organizationHospitalIds();

        $query = TransactionReportHelper::beginReportQuery();

        TransactionReportHelper::applyBookingJoins($query)
            ->with(TransactionReportHelper::invoiceEagerLoads());

        TransactionReportHelper::applyHospitalIdsScope($query, $hospitalIds);

        if ($this->hospitalFilter !== 'all') {
            TransactionReportHelper::applyHospitalIdsScope($query, [(int) $this->hospitalFilter]);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('transactions.status', $this->statusFilter);
        }

        TransactionReportHelper::applyServiceTypeFilter($query, $this->serviceTypeFilter);

        TransactionReportHelper::applyDateRangeFilter($query, $this->fromDate, $this->toDate);

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('cashier_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('transaction_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('doctor_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('doctor_branches.name', 'like', '%' . $search . '%')
                    ->orWhere('second_opinion_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('diagnostic_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('transactions.id', 'like', '%' . $search . '%')
                    ->orWhereHas('invoice.person', function (Builder $personQuery) use ($search) {
                        $personQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%')
                            ->orWhereHas('hipUser', function (Builder $hipUserQuery) use ($search) {
                                $hipUserQuery->where('hip_id', 'like', '%' . $search . '%');
                            });
                    })
                    ->orWhereHas('invoice.primaryPerson', function (Builder $personQuery) use ($search) {
                        $personQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%')
                            ->orWhereHas('hipUser', function (Builder $hipUserQuery) use ($search) {
                                $hipUserQuery->where('hip_id', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        return $query;
    }

    protected function mapTransactionToRow(Transactions $transaction, Collection $creators, Collection $hospitals): array
    {
        $invoice = $transaction->invoice;
        $primary = $invoice?->primaryPerson;
        $person = $invoice?->person;

        $member = $primary ?: $person;
        $memberName = trim(($member?->first_name ?? '') . ' ' . ($member?->last_name ?? '')) ?: '-';
        $memberId = $member?->hipUser?->hip_id ?: '-';
        $memberMobile = $member?->mobile ?: '-';
        $memberImage = $member?->image ? asset('storage/users/' . $member->image) : null;

        $creatorId = (string) ($invoice?->created_by ?? $transaction->created_by ?? '');
        $creator = $creatorId !== '' ? $creators->get($creatorId) : null;
        $creatorHospital = $creator ? $hospitals->get((int) $creator->hospital_id) : null;
        $hospitalName = TransactionReportHelper::resolveHospitalName($invoice, $creatorHospital);

        $serviceLabels = collect($transaction->service_types ?? [])
            ->map(fn ($type) => TransactionReportHelper::serviceTypeLabel((string) $type))
            ->values()
            ->all();

        return [
            'id' => $transaction->id,
            'payment_id' => 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
            'member_name' => $memberName,
            'member_id' => $memberId,
            'member_mobile' => $memberMobile,
            'member_image' => $memberImage,
            'initials' => collect(explode(' ', $memberName))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode(''),
            'services' => $serviceLabels,
            'service_summary' => implode(', ', $serviceLabels) ?: '-',
            'hospital_name' => $hospitalName,
            'amount' => TransactionReportHelper::transactionAmount($transaction),
            'payment_method' => $transaction->payment_method ?: '-',
            'status' => strtolower((string) ($transaction->status ?? 'pending')),
            'status_label' => ucfirst((string) ($transaction->status ?? 'pending')),
            'created_at' => optional($transaction->created_at ?? $invoice?->created_at)?->format('d M, Y h:i A') ?: '-',
        ];
    }

    public function render()
    {
        $baseQuery = $this->filteredTransactionsQuery();

        $stats = [
            'total_received' => (clone $baseQuery)->where('transactions.status', 'completed')->sum('transactions.total_amount'),
            'total_transactions' => (clone $baseQuery)->count('transactions.id'),
            'successful' => (clone $baseQuery)->where('transactions.status', 'completed')->count('transactions.id'),
            'pending' => (clone $baseQuery)->where('transactions.status', 'pending')->count('transactions.id'),
        ];

        $transactions = TransactionReportHelper::applyDefaultOrdering(clone $baseQuery)
            ->paginate(10)
            ->withPath(route('healthcare.transactions.index'));

        $invoiceCreatorIds = $transactions->getCollection()
            ->pluck('invoice.created_by')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        $transactionCreatorIds = $transactions->getCollection()
            ->pluck('created_by')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        $creatorIds = $invoiceCreatorIds
            ->merge($transactionCreatorIds)
            ->unique()
            ->values();

        $creators = HIPUser::query()
            ->whereIn('id', $creatorIds)
            ->get(['id', 'hospital_id'])
            ->keyBy(fn (HIPUser $user) => (string) $user->id);

        $resolvedHospitalIds = $transactions->getCollection()
            ->map(function (Transactions $transaction) use ($creators) {
                $invoice = $transaction->invoice;
                $creatorId = (string) ($invoice?->created_by ?? $transaction->created_by ?? '');
                $creator = $creatorId !== '' ? $creators->get($creatorId) : null;
                $creatorHospital = $creator ? Hospital::query()->find((int) $creator->hospital_id) : null;

                return TransactionReportHelper::resolveHospital($invoice, $creatorHospital)?->id;
            })
            ->merge($creators->pluck('hospital_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $hospitals = Hospital::query()
            ->whereIn('id', $resolvedHospitalIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $transactions->setCollection(
            $transactions->getCollection()->map(
                fn (Transactions $transaction) => $this->mapTransactionToRow($transaction, $creators, $hospitals)
            )
        );

        return view('livewire.hospital-admin.transaction.index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'availableHospitals' => Hospital::query()
                ->where('organization_id', Auth::user()->organization_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'serviceTypeOptions' => TransactionReportHelper::serviceTypeOptions(),
            'statusOptions' => [
                'all' => 'All Status',
                'completed' => 'Completed',
                'pending' => 'Pending',
                'failed' => 'Failed',
                'refunded' => 'Refunded',
                'cancelled' => 'Cancelled',
            ],
        ]);
    }
}
