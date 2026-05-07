<?php

namespace App\Livewire\HospitalAdmin\Transaction;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Transactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $organizationId = (string) (Auth::user()->organization_id ?? '');

        $query = Transactions::query()
            ->select('transactions.*')
            ->leftJoin('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->leftJoin('healthinpocket_users as creators', 'creators.id', '=', 'invoices.created_by')
            ->leftJoin('healthinpocket_users as transaction_creators', 'transaction_creators.id', '=', 'transactions.created_by')
            ->leftJoin('persons as primary_persons', 'primary_persons.id', '=', 'invoices.primary_person_id')
            ->leftJoin('healthinpocket_users as primary_member_users', 'primary_member_users.id', '=', 'primary_persons.hip_user_id')
            ->leftJoin('persons as invoice_persons', 'invoice_persons.id', '=', 'invoices.person_id')
            ->leftJoin('healthinpocket_users as member_users', 'member_users.id', '=', 'invoice_persons.hip_user_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'creators.hospital_id')
            ->leftJoin('hospitals as transaction_hospitals', 'transaction_hospitals.id', '=', 'transaction_creators.hospital_id')
            ->leftJoin('hospitals as primary_member_hospitals', 'primary_member_hospitals.id', '=', 'primary_member_users.hospital_id')
            ->leftJoin('hospitals as member_hospitals', 'member_hospitals.id', '=', 'member_users.hospital_id')
            ->with(['invoice.primaryPerson.hipUser.hospital', 'invoice.person.hipUser.hospital'])
            ->where(function (Builder $builder) use ($hospitalIds) {
                $builder->whereIn('hospitals.id', $hospitalIds)
                    ->orWhereIn('transaction_hospitals.id', $hospitalIds)
                    ->orWhereIn('primary_member_hospitals.id', $hospitalIds)
                    ->orWhereIn('member_hospitals.id', $hospitalIds);
            });

        if ($organizationId !== '') {
            $query->where(function (Builder $builder) use ($organizationId) {
                $builder->where('creators.organization_id', $organizationId)
                    ->orWhere('transaction_creators.organization_id', $organizationId)
                    ->orWhere('primary_member_users.organization_id', $organizationId)
                    ->orWhere('member_users.organization_id', $organizationId);
            });
        }

        if ($this->hospitalFilter !== 'all') {
            $selectedHospitalId = (int) $this->hospitalFilter;
            $query->where(function (Builder $builder) use ($selectedHospitalId) {
                $builder->where('hospitals.id', $selectedHospitalId)
                    ->orWhere('transaction_hospitals.id', $selectedHospitalId)
                    ->orWhere('primary_member_hospitals.id', $selectedHospitalId)
                    ->orWhere('member_hospitals.id', $selectedHospitalId);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('transactions.status', $this->statusFilter);
        }

        if ($this->serviceTypeFilter !== 'all') {
            $query->whereJsonContains('transactions.service_types', $this->serviceTypeFilter);
        }

        if ($this->fromDate !== '') {
            $query->whereDate(DB::raw('COALESCE(transactions.created_at, invoices.created_at)'), '>=', $this->fromDate);
        }

        if ($this->toDate !== '') {
            $query->whereDate(DB::raw('COALESCE(transactions.created_at, invoices.created_at)'), '<=', $this->toDate);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('transaction_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('primary_member_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('member_hospitals.name', 'like', '%' . $search . '%')
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
        $hospital = $creator ? $hospitals->get((int) $creator->hospital_id) : null;
        $hospitalName = $hospital?->name
            ?: $primary?->hipUser?->hospital?->name
            ?: $person?->hipUser?->hospital?->name
            ?: '-';

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
            'hospital_name' => $hospitalName,
            'amount' => (float) ($transaction->transaction_amount ?? 0),
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
            'total_transactions' => (clone $baseQuery)->count(),
            'successful' => (clone $baseQuery)->where('transactions.status', 'completed')->count(),
            'pending' => (clone $baseQuery)->where('transactions.status', 'pending')->count(),
        ];

        $transactions = (clone $baseQuery)
            ->orderByRaw('COALESCE(transactions.created_at, invoices.created_at) DESC')
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

        $creatorHospitalIds = $creators
            ->pluck('hospital_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $hospitals = Hospital::query()
            ->whereIn('id', $creatorHospitalIds)
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
