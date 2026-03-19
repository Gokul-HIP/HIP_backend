<?php

namespace App\Livewire\HospitalAdmin\Transaction;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Transactions;
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
        $this->fromDate = '';
        $this->toDate = '';
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

        $query = Transactions::query()
            ->select('transactions.*')
            ->join('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->join('healthinpocket_users as creators', 'creators.id', '=', 'invoices.created_by')
            ->join('hospitals', 'hospitals.id', '=', 'creators.hospital_id')
            ->with(['invoice.primaryPerson.hipUser', 'invoice.person.hipUser'])
            ->whereIn('hospitals.id', $hospitalIds);

        if ($this->hospitalFilter !== 'all') {
            $query->where('hospitals.id', (int) $this->hospitalFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('transactions.status', $this->statusFilter);
        }

        if ($this->serviceTypeFilter !== 'all') {
            $query->whereJsonContains('transactions.service_types', $this->serviceTypeFilter);
        }

        if ($this->fromDate !== '') {
            $query->whereDate('transactions.created_at', '>=', $this->fromDate);
        }

        if ($this->toDate !== '') {
            $query->whereDate('transactions.created_at', '<=', $this->toDate);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('hospitals.name', 'like', '%' . $search . '%')
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

        $creator = $creators->get((int) ($invoice?->created_by ?? 0));
        $hospital = $creator ? $hospitals->get((int) $creator->hospital_id) : null;

        $serviceLabels = collect($transaction->service_types ?? [])->map(function ($type) {
            return match ($type) {
                'procedure' => 'Procedure',
                'labTest' => 'Diagnostic',
                'package' => 'Package',
                'pharmacy' => 'Pharmacy',
                default => ucfirst((string) $type),
            };
        })->values()->all();

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
            'hospital_name' => $hospital?->name ?: '-',
            'amount' => (float) ($transaction->total_amount ?? 0),
            'payment_method' => $transaction->payment_method ?: '-',
            'status' => strtolower((string) ($transaction->status ?? 'pending')),
            'status_label' => ucfirst((string) ($transaction->status ?? 'pending')),
            'created_at' => optional($transaction->created_at)?->format('d M, Y h:i A') ?: '-',
        ];
    }

    public function render()
    {
        $baseQuery = $this->filteredTransactionsQuery();

        $stats = [
            'total_received' => (clone $baseQuery)->where('transactions.status', 'completed')->sum('transactions.total_amount'),
            'total_transactions' => (clone $baseQuery)->count(),
            'successful' => (clone $baseQuery)->where('transactions.status', 'completed')->count(),
            'pending' => (clone $baseQuery)->where('transactions.status', 'pending')->count(),
        ];

        $transactions = (clone $baseQuery)
            ->orderByDesc('transactions.created_at')
            ->paginate(10)
            ->withPath(route('healthcare.transactions.index'));

        $creatorIds = $transactions->getCollection()
            ->pluck('invoice.created_by')
            ->filter()
            ->unique()
            ->values();

        $creators = HIPUser::query()
            ->whereIn('id', $creatorIds)
            ->get(['id', 'hospital_id'])
            ->keyBy('id');

        $hospitals = Hospital::query()
            ->whereIn('id', $creators->pluck('hospital_id')->filter()->unique()->values())
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
            'serviceTypeOptions' => [
                'all' => 'All Services',
                'procedure' => 'Procedure',
                'labTest' => 'Diagnostic',
                'package' => 'Package',
                'pharmacy' => 'Pharmacy',
            ],
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
