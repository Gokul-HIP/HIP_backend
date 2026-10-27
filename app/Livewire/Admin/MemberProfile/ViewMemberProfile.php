<?php

namespace App\Livewire\Admin\MemberProfile;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use App\Services\Api\PaymentApiService;
use App\Services\FamilyPackageService;
use App\Services\MembershipEntitlementService;
use App\Services\RewardTierService;
use App\Services\TransactionBreakdownBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ViewMemberProfile extends Component
{
    use WithPagination;

    public ?string $userId = null;

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    public string $dependentSearch = '';

    public string $appointmentHospital = '';

    public string $appointmentStatus = 'all';

    public string $appointmentType = 'all';

    public string $appointmentFrom = '';

    public string $appointmentTo = '';

    public string $transactionStatus = 'all';

    public string $transactionFrom = '';

    public string $transactionTo = '';

    public bool $showStatusDialog = false;

    public function mount($user = null): void
    {
        if ($user === null || $user === '') {
            return;
        }

        $this->userId = (string) $user;
        $this->normalizeTab();
    }

    public function updatedTab(): void
    {
        $this->normalizeTab();
    }

    public function updatingDependentSearch(): void
    {
        $this->resetPage(pageName: 'dependentsPage');
    }

    public function updatingAppointmentHospital(): void
    {
        $this->resetPage(pageName: 'appointmentsPage');
    }

    public function updatingAppointmentStatus(): void
    {
        $this->resetPage(pageName: 'appointmentsPage');
    }

    public function updatingAppointmentFrom(): void
    {
        $this->resetPage(pageName: 'appointmentsPage');
    }

    public function updatingAppointmentTo(): void
    {
        $this->resetPage(pageName: 'appointmentsPage');
    }

    public function updatedAppointmentType(): void
    {
        if ($this->appointmentStatus !== 'all' && ! array_key_exists($this->appointmentStatus, $this->statusOptions())) {
            $this->appointmentStatus = 'all';
        }

        $this->resetPage(pageName: 'appointmentsPage');
    }

    public function updatingTransactionStatus(): void
    {
        $this->resetPage(pageName: 'transactionsPage');
    }

    public function updatingTransactionFrom(): void
    {
        $this->resetPage(pageName: 'transactionsPage');
    }

    public function updatingTransactionTo(): void
    {
        $this->resetPage(pageName: 'transactionsPage');
    }

    public function clearAppointmentFilters(): void
    {
        $this->appointmentHospital = '';
        $this->appointmentStatus = 'all';
        $this->appointmentType = 'all';
        $this->appointmentFrom = '';
        $this->appointmentTo = '';
        $this->resetPage(pageName: 'appointmentsPage');
    }

    public function clearTransactionFilters(): void
    {
        $this->transactionStatus = 'all';
        $this->transactionFrom = '';
        $this->transactionTo = '';
        $this->resetPage(pageName: 'transactionsPage');
    }

    public function askStatusChange(): void
    {
        $this->showStatusDialog = true;
    }

    public function confirmStatusChange(): void
    {
        if (! $this->userId) {
            return;
        }

        $user = HIPUser::query()->findOrFail($this->userId);
        $activating = ! (bool) $user->is_active;

        DB::transaction(function () use ($user, $activating): void {
            $user->update([
                'is_active' => $activating,
                'deactivated_at' => $activating ? null : now(),
            ]);

            Persons::query()
                ->where('hip_user_id', $user->id)
                ->where('is_primary', true)
                ->get()
                ->each(function (Persons $person) use ($activating): void {
                    $person->update([
                        'is_active' => $activating,
                        'deactivated_at' => $activating ? null : now(),
                        'updated_by' => auth('filament')->id(),
                    ]);
                });

            if (! $activating) {
                $user->tokens()->delete();

                if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
            }
        });

        $this->showStatusDialog = false;
        $this->dispatch('toast', type: 'success', message: $activating ? 'Patient activated' : 'Patient deactivated');
    }

    public function render()
    {
        if (! $this->userId) {
            return view('livewire.admin.member-profile.view-member-profile', [
                'ready' => false,
            ]);
        }

        $user = HIPUser::query()->findOrFail($this->userId);
        $primary = Persons::query()
            ->where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->first();

        $familyIds = $this->familyIds($primary);
        $rewardTierCard = $this->rewardTierCard((string) $user->id);
        $membershipCard = $this->membershipCard((string) $user->id);
        $hospital = $primary && $primary->hospital_id
            ? Hospital::query()->find($primary->hospital_id)
            : null;
        $creatorName = $this->creatorName($primary?->created_by);
        $hipCardId = $primary
            ? DB::table('h_i_p_cards')->where('patient_id', $primary->id)->orderByDesc('id')->value('hip_card_id')
            : null;
        $coinBalance = $primary
            ? DB::table('coins')->where('person_id', $primary->id)->orderByDesc('id')->value('coins')
            : null;

        $dependents = $this->tab === 'dependents'
            ? $this->dependentQuery($primary)->paginate(10, ['*'], 'dependentsPage')
            : null;
        $appointments = $this->tab === 'appointments'
            ? $this->appointmentQuery($familyIds)->orderByRaw('appt_date IS NULL, appt_date DESC')->orderByDesc('booking_id')->paginate(10, ['*'], 'appointmentsPage')
            : null;
        $transactions = $this->tab === 'transactions'
            ? $this->transactionQuery($familyIds)->paginate(10, ['*'], 'transactionsPage')
            : null;
        $coinsEarned = $this->tab === 'transactions'
            ? $this->coinsEarnedSummary((string) $user->id, $familyIds)
            : ['total' => 0, 'breakdown' => [], 'note' => 'No recorded coin earnings'];

        if ($transactions) {
            $this->attachTransactionBreakdowns($transactions->getCollection());
            $transactions->getCollection()->transform(function ($row) {
                $row->description = $this->serviceLabel($row->service_types);
                $row->when_label = $row->created_at
                    ? Carbon::parse($row->created_at)->timezone(config('app.timezone'))->format('d M Y, h:i A')
                    : null;

                return $row;
            });
        }

        return view('livewire.admin.member-profile.view-member-profile', [
            'ready' => true,
            'user' => $user,
            'primary' => $primary,
            'hospitalName' => $hospital?->name,
            'creatorName' => $creatorName,
            'hipCardId' => $hipCardId,
            'coinBalance' => $coinBalance,
            'coinsEarned' => $coinsEarned,
            'rewardTierCard' => $rewardTierCard,
            'membershipCard' => $membershipCard,
            'age' => $this->age($user->dob ?: $primary?->dob),
            'joined' => $user->created_at
                ? Carbon::parse($user->created_at)->timezone(config('app.timezone'))->format('d M Y')
                : null,
            'dependentCount' => $primary
                ? Persons::query()->where('parent_id', $primary->id)->where('id', '!=', $primary->id)->count()
                : 0,
            'appointmentCount' => $this->appointmentTotal($familyIds),
            'transactionCount' => $this->transactionTotal($familyIds),
            'coinsUsed' => $this->coinsUsedFromBookings($familyIds),
            'dependents' => $dependents,
            'appointments' => $appointments,
            'transactions' => $transactions,
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    protected function dependentQuery(?Persons $primary): Builder
    {
        $query = DB::table('persons')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'persons.hospital_id')
            ->select('persons.*', 'hospitals.name as hospital_name')
            ->whereRaw('1 = 0');

        if (! $primary) {
            return $query;
        }

        $search = trim($this->dependentSearch);

        return DB::table('persons')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'persons.hospital_id')
            ->select('persons.*', 'hospitals.name as hospital_name')
            ->where('persons.parent_id', $primary->id)
            ->where('persons.id', '!=', $primary->id)
            ->when($search !== '', function (Builder $inner) use ($search) {
                $inner->where(function (Builder $name) use ($search) {
                    $name->where('persons.first_name', 'like', "%{$search}%")
                        ->orWhere('persons.last_name', 'like', "%{$search}%")
                        ->orWhere('persons.mobile', 'like', "%{$search}%")
                        ->orWhere('persons.relationship', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT_WS(' ', persons.first_name, persons.last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->orderBy('persons.first_name')
            ->orderBy('persons.id');
    }

    protected function appointmentQuery(array $familyIds): Builder
    {
        if ($familyIds === []) {
            return DB::query()->fromSub(
                DB::query()->selectRaw("'doctor' as booking_type, 0 as booking_id, '' as person_name, '' as hospital_name, '' as detail_name, NULL as appt_date, '' as booking_status, NULL as amount, NULL as coins_used")->whereRaw('1 = 0'),
                'appointments'
            );
        }

        $parts = [];
        if ($this->appointmentType === 'all' || $this->appointmentType === 'doctor') {
            $parts[] = $this->doctorAppointments($familyIds);
        }
        if ($this->appointmentType === 'all' || $this->appointmentType === 'second_opinion') {
            $parts[] = $this->secondAppointments($familyIds);
        }
        if ($this->appointmentType === 'all' || $this->appointmentType === 'diagnostic') {
            $parts[] = $this->diagnosticAppointments($familyIds);
        }

        $union = array_shift($parts);
        foreach ($parts as $part) {
            $union = $union->unionAll($part);
        }

        return DB::query()->fromSub($union, 'appointments');
    }

    protected function doctorAppointments(array $familyIds): Builder
    {
        $query = DB::table('doctor_bookings')
            ->leftJoin('persons', 'persons.id', '=', 'doctor_bookings.patient_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'doctor_bookings.hospital_id')
            ->leftJoin('hospitals as branch_hospital', 'branch_hospital.id', '=', 'doctor_bookings.branch_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'doctor_bookings.doctor_id')
            ->whereIn('doctor_bookings.patient_id', $familyIds)
            ->select([
                DB::raw("'doctor' as booking_type"),
                'doctor_bookings.id as booking_id',
                DB::raw("TRIM(CONCAT_WS(' ', persons.first_name, persons.last_name)) as person_name"),
                DB::raw('COALESCE(hospitals.name, branch_hospital.name) as hospital_name'),
                DB::raw("COALESCE(NULLIF(doctors.name, ''), 'Doctor') as detail_name"),
                DB::raw('COALESCE(doctor_bookings.booking_date, DATE(doctor_bookings.created_at)) as appt_date'),
                DB::raw('doctor_bookings.status COLLATE utf8mb4_unicode_ci as booking_status'),
                DB::raw('CAST(doctor_bookings.total_amount AS DECIMAL(10,2)) as amount'),
                DB::raw('CAST(doctor_bookings.coins_used AS SIGNED) as coins_used'),
            ]);

        return $this->applyAppointmentFilters($query, 'doctor_bookings.booking_date', 'doctor_bookings.created_at', 'doctor_bookings.status', function (Builder $inner): void {
            $inner->where('doctor_bookings.hospital_id', $this->appointmentHospital);
        });
    }

    protected function secondAppointments(array $familyIds): Builder
    {
        $query = DB::table('second_opinions')
            ->leftJoin('persons', 'persons.id', '=', 'second_opinions.patient_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'second_opinions.branch_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'second_opinions.doctor_id')
            ->whereIn('second_opinions.patient_id', $familyIds)
            ->select([
                DB::raw("'second_opinion' as booking_type"),
                'second_opinions.id as booking_id',
                DB::raw("TRIM(CONCAT_WS(' ', persons.first_name, persons.last_name)) as person_name"),
                'hospitals.name as hospital_name',
                DB::raw("COALESCE(NULLIF(doctors.name, ''), NULLIF(second_opinions.diagnosis, ''), 'Second opinion') as detail_name"),
                DB::raw('COALESCE(second_opinions.preferred_date, DATE(second_opinions.created_at)) as appt_date'),
                DB::raw('second_opinions.status COLLATE utf8mb4_unicode_ci as booking_status'),
                DB::raw('CAST(second_opinions.total_amount AS DECIMAL(10,2)) as amount'),
                DB::raw('NULL as coins_used'),
            ]);

        return $this->applyAppointmentFilters($query, 'second_opinions.preferred_date', 'second_opinions.created_at', 'second_opinions.status', function (Builder $inner): void {
            $inner->where('second_opinions.branch_id', $this->appointmentHospital);
        });
    }

    protected function diagnosticAppointments(array $familyIds): Builder
    {
        $query = DB::table('diagnostic_test_bookings')
            ->leftJoin('persons', 'persons.id', '=', 'diagnostic_test_bookings.patient_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'diagnostic_test_bookings.branch_id')
            ->whereIn('diagnostic_test_bookings.patient_id', $familyIds)
            ->select([
                DB::raw("'diagnostic' as booking_type"),
                'diagnostic_test_bookings.id as booking_id',
                DB::raw("TRIM(CONCAT_WS(' ', persons.first_name, persons.last_name)) as person_name"),
                'hospitals.name as hospital_name',
                DB::raw("COALESCE(NULLIF(diagnostic_test_bookings.purpose, ''), NULLIF(diagnostic_test_bookings.test_type, ''), 'Diagnostic') as detail_name"),
                DB::raw('COALESCE(diagnostic_test_bookings.booking_date, DATE(diagnostic_test_bookings.created_at)) as appt_date'),
                DB::raw('diagnostic_test_bookings.status COLLATE utf8mb4_unicode_ci as booking_status'),
                DB::raw('CAST(diagnostic_test_bookings.total_amount AS DECIMAL(10,2)) as amount'),
                DB::raw('CAST(diagnostic_test_bookings.coins_used AS SIGNED) as coins_used'),
            ]);

        return $this->applyAppointmentFilters($query, 'diagnostic_test_bookings.booking_date', 'diagnostic_test_bookings.created_at', 'diagnostic_test_bookings.status', function (Builder $inner): void {
            $inner->where('diagnostic_test_bookings.branch_id', $this->appointmentHospital);
        });
    }

    protected function applyAppointmentFilters(Builder $query, string $dateColumn, string $fallbackColumn, string $statusColumn, \Closure $hospital): Builder
    {
        if ($this->appointmentStatus !== '' && $this->appointmentStatus !== 'all') {
            $query->where($statusColumn, $this->appointmentStatus);
        }
        if ($this->appointmentFrom !== '') {
            $query->whereRaw('DATE(COALESCE('.$dateColumn.', '.$fallbackColumn.')) >= ?', [$this->appointmentFrom]);
        }
        if ($this->appointmentTo !== '') {
            $query->whereRaw('DATE(COALESCE('.$dateColumn.', '.$fallbackColumn.')) <= ?', [$this->appointmentTo]);
        }
        if ($this->appointmentHospital !== '') {
            $hospital($query);
        }

        return $query;
    }

    protected function transactionQuery(array $familyIds): Builder
    {
        $query = DB::table('transactions')
            ->join('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->select(
                'transactions.*',
                'invoices.service_types',
                'invoices.amount as invoice_original_amount',
                'invoices.total_amount as invoice_total_amount',
                'invoices.discount_price as invoice_discount_amount',
                'invoices.coins_applied as invoice_coins_used',
                'invoices.service_charges as invoice_service_charge',
                'invoices.total_gst as invoice_gst',
                'invoices.payment_gateway_charges as invoice_gateway_charge',
                'invoices.doctor_booking_id',
                'invoices.second_opinion_id',
                'invoices.diagnostic_test_booking_id',
                'invoices.pharmacy_order_id',
            )
            ->whereRaw('1 = 0');

        if ($familyIds === []) {
            return $query->orderByDesc('transactions.id');
        }

        return DB::table('transactions')
            ->join('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->select(
                'transactions.*',
                'invoices.service_types',
                'invoices.amount as invoice_original_amount',
                'invoices.total_amount as invoice_total_amount',
                'invoices.discount_price as invoice_discount_amount',
                'invoices.coins_applied as invoice_coins_used',
                'invoices.service_charges as invoice_service_charge',
                'invoices.total_gst as invoice_gst',
                'invoices.payment_gateway_charges as invoice_gateway_charge',
                'invoices.doctor_booking_id',
                'invoices.second_opinion_id',
                'invoices.diagnostic_test_booking_id',
                'invoices.pharmacy_order_id',
            )
            ->where(function (Builder $inner) use ($familyIds) {
                $inner->whereIn('invoices.primary_person_id', $familyIds)
                    ->orWhereIn('invoices.person_id', $familyIds);
            })
            ->when($this->transactionStatus !== '' && $this->transactionStatus !== 'all', function (Builder $inner) {
                $inner->where('transactions.status', $this->transactionStatus);
            })
            ->when($this->transactionFrom !== '', function (Builder $inner) {
                $inner->whereDate('transactions.created_at', '>=', $this->transactionFrom);
            })
            ->when($this->transactionTo !== '', function (Builder $inner) {
                $inner->whereDate('transactions.created_at', '<=', $this->transactionTo);
            })
            ->orderByDesc('transactions.created_at')
            ->orderByDesc('transactions.id');
    }

    /** Load all booking and ledger context for the visible page in bounded queries. */
    protected function attachTransactionBreakdowns(\Illuminate\Support\Collection $rows): void
    {
        $doctorIds = $rows->pluck('doctor_booking_id')->filter()->unique()->values()->all();
        $secondOpinionIds = $rows->pluck('second_opinion_id')->filter()->unique()->values()->all();
        $packageIds = $rows->pluck('diagnostic_test_booking_id')->filter()->unique()->values()->all();
        $pharmacyIds = $rows->pluck('pharmacy_order_id')->filter()->unique()->values()->all();

        $doctorBookings = $doctorIds !== [] && Schema::hasTable('doctor_bookings')
            ? DB::table('doctor_bookings')->whereIn('id', $doctorIds)
                ->get(['id', 'consultation_fee', 'total_discount', 'coins_used'])->keyBy('id')
            : collect();
        $secondOpinions = $secondOpinionIds !== [] && Schema::hasTable('second_opinions')
            ? DB::table('second_opinions')->whereIn('id', $secondOpinionIds)
                ->get(['id', 'consultation_fee', 'total_discount', 'coins_used'])->keyBy('id')
            : collect();
        $packageBookings = $packageIds !== [] && Schema::hasTable('diagnostic_test_bookings')
            ? DB::table('diagnostic_test_bookings')->whereIn('id', $packageIds)
                ->get(['id', 'package_fee', 'total_discount', 'coins_used'])->keyBy('id')
            : collect();
        $pharmacyOrders = $pharmacyIds !== [] && Schema::hasTable('pharmacy_orders')
            ? DB::table('pharmacy_orders')->whereIn('id', $pharmacyIds)
                ->get(['id', 'subtotal', 'discount', 'delivery_fee', 'tax', 'service_charge', 'gateway_charge', 'total', 'coins_applied'])->keyBy('id')
            : collect();

        $bookingPairs = $rows->map(function ($row): ?array {
            foreach ([
                'doctor_booking_id' => 'doctor_booking',
                'second_opinion_id' => 'second_opinion',
                'diagnostic_test_booking_id' => 'diagnostic_test_booking',
            ] as $invoiceColumn => $bookingType) {
                if (! empty($row->{$invoiceColumn})) {
                    return ['booking_type' => $bookingType, 'booking_id' => (int) $row->{$invoiceColumn}];
                }
            }

            return null;
        })->filter()->unique(fn (array $pair) => $pair['booking_type'].':'.$pair['booking_id'])->values();

        $membershipUsages = collect();
        if ($bookingPairs->isNotEmpty() && Schema::hasTable('subscription_usage_logs')) {
            $membershipQuery = DB::table('subscription_usage_logs')->select('booking_type', 'booking_id', 'usage_type');
            $membershipQuery->where(function ($query) use ($bookingPairs): void {
                foreach ($bookingPairs as $pair) {
                    $query->orWhere(function ($pairQuery) use ($pair): void {
                        $pairQuery->where('booking_type', $pair['booking_type'])->where('booking_id', $pair['booking_id']);
                    });
                }
            });
            $membershipUsages = $membershipQuery->get()->keyBy(fn ($usage) => $usage->booking_type.':'.$usage->booking_id);
        }

        $rewardUsages = collect();
        if ($bookingPairs->isNotEmpty() && Schema::hasTable('reward_tier_discount_usages')) {
            $rewardQuery = DB::table('reward_tier_discount_usages')
                ->leftJoin('reward_tiers', 'reward_tiers.id', '=', 'reward_tier_discount_usages.reward_tier_id')
                ->leftJoin('reward_tier_configs', 'reward_tier_configs.reward_tier_id', '=', 'reward_tier_discount_usages.reward_tier_id')
                ->select(
                    'reward_tier_discount_usages.booking_type',
                    'reward_tier_discount_usages.booking_id',
                    'reward_tier_discount_usages.status',
                    'reward_tiers.name as tier_name',
                    'reward_tier_configs.total_discount_percentage as tier_percentage',
                );
            $rewardQuery->where(function ($query) use ($bookingPairs): void {
                foreach ($bookingPairs as $pair) {
                    $query->orWhere(function ($pairQuery) use ($pair): void {
                        $pairQuery->where('reward_tier_discount_usages.booking_type', $pair['booking_type'])
                            ->where('reward_tier_discount_usages.booking_id', $pair['booking_id']);
                    });
                }
            });
            $rewardUsages = $rewardQuery->get()->keyBy(fn ($usage) => $usage->booking_type.':'.$usage->booking_id);
        }

        $coinRate = app(PaymentApiService::class)->amountForOneCoin();
        $builder = app(TransactionBreakdownBuilder::class);

        foreach ($rows as $row) {
            $bookingType = 'other';
            $bookingId = null;
            $booking = null;
            foreach ([
                'doctor_booking_id' => ['doctor_booking', $doctorBookings],
                'second_opinion_id' => ['second_opinion', $secondOpinions],
                'diagnostic_test_booking_id' => ['diagnostic_test_booking', $packageBookings],
            ] as $invoiceColumn => [$type, $source]) {
                if (! empty($row->{$invoiceColumn})) {
                    $bookingType = $type;
                    $bookingId = (int) $row->{$invoiceColumn};
                    $bookingRow = $source->get($bookingId);
                    $booking = $bookingRow ? (array) $bookingRow : null;
                    break;
                }
            }

            $pharmacyOrder = ! empty($row->pharmacy_order_id)
                ? (($pharmacyRow = $pharmacyOrders->get((int) $row->pharmacy_order_id)) ? (array) $pharmacyRow : null)
                : null;
            if ($pharmacyOrder !== null) {
                $bookingType = 'pharmacy';
                $bookingId = (int) $row->pharmacy_order_id;
            }

            $pairType = match ($bookingType) {
                'doctor_booking' => 'doctor_booking',
                'second_opinion' => 'second_opinion',
                'diagnostic_test_booking' => 'diagnostic_test_booking',
                default => null,
            };
            $pairKey = $pairType !== null && $bookingId !== null ? $pairType.':'.$bookingId : null;
            $membershipRow = $pairKey !== null ? $membershipUsages->get($pairKey) : null;
            $rewardRow = $pairKey !== null ? $rewardUsages->get($pairKey) : null;
            $membershipUsage = $membershipRow ? (array) $membershipRow : null;
            $rewardUsage = $rewardRow ? (array) $rewardRow : null;

            $row->breakdown = $builder->build(
                [
                    'transaction_amount' => $row->transaction_amount,
                    'service_charge' => $row->service_charges,
                    'gateway_charge' => $row->payment_gateway_charges,
                    'discount_amount' => $row->discount_amount,
                    'gst' => $row->total_gst,
                    'total_paid' => $row->total_amount,
                ],
                [
                    'original_amount' => $row->invoice_original_amount,
                    'total_amount' => $row->invoice_total_amount,
                    'discount_amount' => $row->invoice_discount_amount,
                    'coins_used' => $row->invoice_coins_used,
                    'service_charge' => $row->invoice_service_charge,
                    'gst' => $row->invoice_gst,
                    'gateway_charge' => $row->invoice_gateway_charge,
                    'doctor_booking_id' => $row->doctor_booking_id,
                    'second_opinion_id' => $row->second_opinion_id,
                    'diagnostic_test_booking_id' => $row->diagnostic_test_booking_id,
                    'pharmacy_order_id' => $row->pharmacy_order_id,
                ],
                $booking,
                $membershipUsage,
                $rewardUsage,
                $pharmacyOrder,
                $coinRate,
            );
        }
    }

    /** Read the same progress payload used by the reward API without creating progress on page views. */
    protected function rewardTierCard(string $hipUserId): ?array
    {
        $service = app(RewardTierService::class);
        $progress = $service->getReadOnlyRewardProgressPayload($hipUserId);

        if ($progress === null) {
            return ['no_tier' => true, 'tier_name' => null];
        }

        $usage = $service->getReadOnlyDiscountUsageSummary($hipUserId);

        $usage ??= ['limit' => 0, 'used' => 0, 'remaining' => null];

        return [
            'no_tier' => false,
            'tier_name' => trim((string) ($progress['current_tier_name'] ?? '')),
            'next_tier_name' => trim((string) ($progress['next_tier_name'] ?? '')) ?: null,
            'discount_percentage' => $progress['total_discount_percentage'],
            'earned_in_tier' => (int) $progress['earned_coins_in_tier'],
            'coins_to_next_tier' => (int) $progress['coins_to_next_tier'],
            'progress_percentage' => min(100, max(0, (float) $progress['progress_percentage'])),
            'lifetime_coins' => (int) $progress['total_lifetime_coins'],
            'limit' => (int) $usage['limit'],
            'used' => (int) $usage['used'],
            'remaining' => $usage['remaining'],
        ];
    }

    /** Read-only membership card data, using the entitlement service's active-subscription rules. */
    protected function membershipCard(string $hipUserId): array
    {
        if (! Schema::hasTable('user_family_subscriptions') || ! Schema::hasTable('family_packages')) {
            return ['active' => null, 'expired_label' => null];
        }

        $eligibility = app(MembershipEntitlementService::class)
            ->evaluateConsultationEligibility($hipUserId, null, true);
        $subscription = $eligibility['subscription'] ?? null;
        $package = $eligibility['package'] ?? null;

        if ($subscription && $package) {
            $usageRows = Schema::hasTable('subscription_usage_logs')
                ? DB::table('subscription_usage_logs')
                    ->where('user_family_subscription_id', $subscription->id)
                    ->get(['usage_type', 'booking_id', 'booking_type'])
                : collect();
            $consultationsUsed = $usageRows->where('usage_type', 'consultation')->count();
            $labBookingIds = $usageRows->where('usage_type', 'lab_test')
                ->where('booking_type', 'diagnostic_test_booking')
                ->pluck('booking_id')->filter()->unique()->values()->all();
            $labUses = [];
            if ($labBookingIds !== [] && Schema::hasTable('diagnostic_test_bookings')) {
                $labUses = DB::table('diagnostic_test_bookings')
                    ->whereIn('id', $labBookingIds)
                    ->where(function (Builder $query): void {
                        $query->whereNull('package_type')->orWhere('package_type', '!=', 'disease');
                    })
                    ->select('package_id', DB::raw('COUNT(*) as used'))
                    ->groupBy('package_id')
                    ->pluck('used', 'package_id')->all();
            }

            $benefits = $package->structuredPackageBenefits();
            $freeBenefits = array_values(array_filter($benefits, fn (array $benefit) => $benefit['benefit_type'] === 'free'));
            $packageIds = array_values(array_unique(array_column($freeBenefits, 'diagnostic_package_id')));
            $packageNames = $packageIds !== [] && Schema::hasTable('diagnostic_packages')
                ? DB::table('diagnostic_packages')->whereIn('id', $packageIds)->pluck('name', 'id')->all()
                : [];
            $freePackages = array_map(function (array $benefit) use ($labUses, $packageNames): array {
                $id = (int) $benefit['diagnostic_package_id'];
                $limit = (int) $benefit['max_lab_tests'];

                return [
                    'name' => $packageNames[$id] ?? ('Package #'.$id),
                    'used' => (int) ($labUses[$id] ?? 0),
                    'limit' => $limit,
                ];
            }, $freeBenefits);

            $branchIds = array_values(array_unique(array_map('intval', $package->branch_ids ?? [])));
            $branches = $branchIds === []
                ? ['All branches']
                : (Schema::hasTable('hospitals')
                    ? DB::table('hospitals')->whereIn('id', $branchIds)->pluck('name')->map(fn ($name) => (string) $name)->all()
                    : []);
            if ($branchIds !== [] && $branches === []) {
                $branches = array_map(fn (int $id) => 'Branch #'.$id, $branchIds);
            }

            $endDate = Carbon::parse($subscription->end_date)->startOfDay();

            return [
                'active' => [
                    'plan_name' => $package->name,
                    'status' => ucfirst((string) $subscription->status),
                    'start_date' => Carbon::parse($subscription->start_date)->format('d M Y'),
                    'end_date' => $endDate->format('d M Y'),
                    'days_left' => max(0, (int) Carbon::today()->diffInDays($endDate, false)),
                    'branches' => $branches,
                    'consultations_used' => $consultationsUsed,
                    'consultations_limit' => (int) ($package->max_consultations ?? 0),
                    'free_packages' => $freePackages,
                ],
                'expired_label' => null,
            ];
        }

        $expired = DB::table('user_family_subscriptions')
            ->where('hip_user_id', $hipUserId)
            ->where(function (Builder $query): void {
                $query->where('status', 'expired')
                    ->orWhere(function (Builder $activeQuery): void {
                        $activeQuery->where('status', 'active')
                            ->whereDate('end_date', '<', now()->toDateString());
                    });
            })
            ->orderByDesc('end_date')->orderByDesc('id')->first(['end_date']);

        return [
            'active' => null,
            'expired_label' => $expired?->end_date
                ? 'Membership expired on '.Carbon::parse($expired->end_date)->format('d M Y')
                : null,
        ];
    }

    /** Total spendable coins recorded on completed invoices and non-invoice wallet earning entries. */
    protected function coinsEarnedSummary(string $hipUserId, array $familyIds): array
    {
        $bookingCoins = 0;
        $otherInvoiceCoins = 0;
        $missingInvoiceAwards = 0;

        if ($familyIds !== [] && Schema::hasTable('invoices')) {
            $invoiceQuery = DB::table('invoices')->where('status', 'completed')
                ->where(function (Builder $query) use ($familyIds): void {
                    $query->whereIn('primary_person_id', $familyIds)->orWhereIn('person_id', $familyIds);
                });
            $bookingCoins = (int) (clone $invoiceQuery)
                ->where(function (Builder $query): void {
                    $query->whereNotNull('doctor_booking_id')->orWhereNotNull('second_opinion_id')
                        ->orWhereNotNull('diagnostic_test_booking_id');
                })->sum('coins_earned');
            $otherInvoiceCoins = (int) (clone $invoiceQuery)
                ->whereNull('doctor_booking_id')->whereNull('second_opinion_id')->whereNull('diagnostic_test_booking_id')
                ->sum('coins_earned');
            if (Schema::hasColumn('invoices', 'coins_earned')) {
                $missingInvoiceAwards = (clone $invoiceQuery)->whereNull('coins_earned')->count();
            }
        }

        $nonInvoiceEarnings = collect();
        $hasWalletHistory = Schema::hasTable('reward_points_transitions');
        if ($hasWalletHistory) {
            $nonInvoiceEarnings = DB::table('reward_points_transitions')
                ->where('hip_user_id', $hipUserId)->where('change_type', 'earned')->where('points_changed', '>', 0)
                ->whereNull('invoice_id')->select('reward_source', DB::raw('SUM(points_changed) as coins'))
                ->groupBy('reward_source')->get()->keyBy('reward_source');
        }

        $walletEarnings = [];
        foreach ($nonInvoiceEarnings as $earning) {
            $source = strtolower(trim((string) $earning->reward_source));
            $sourceLabel = match (true) {
                $source === 'bill_analysis' => 'Bill analysis',
                str_contains($source, 'content'), str_contains($source, 'how_to_earn') => 'Content',
                $source === '' => 'Other wallet earnings',
                default => ucwords(str_replace(['_', '-'], ' ', $source)),
            };
            $walletEarnings[$sourceLabel] = ($walletEarnings[$sourceLabel] ?? 0) + (int) $earning->coins;
        }

        $breakdownAmounts = [
            'Bookings' => $bookingCoins,
            'Other paid invoices' => $otherInvoiceCoins,
        ] + $walletEarnings;
        $breakdown = [];
        foreach ($breakdownAmounts as $source => $amount) {
            if ($amount > 0) {
                $breakdown[] = $source.' '.$amount;
            }
        }

        $note = $breakdown !== [] ? implode(' · ', $breakdown) : 'No recorded coin earnings';
        if ($missingInvoiceAwards > 0) {
            $note .= ' · Some older paid invoices have no stored earned amount';
        }
        if (! $hasWalletHistory) {
            $note .= ' · Non-invoice wallet earnings are not fully recorded';
        } else {
            $note .= ' · Older non-invoice wallet earnings may not be fully recorded';
        }

        return [
            'total' => $bookingCoins + $otherInvoiceCoins + array_sum($walletEarnings),
            'breakdown' => $breakdown,
            'note' => $note,
        ];
    }

    protected function familyIds(?Persons $primary): array
    {
        if (! $primary) {
            return [];
        }

        $dependentIds = Persons::query()
            ->where('parent_id', $primary->id)
            ->where('id', '!=', $primary->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return array_values(array_unique(array_merge([(string) $primary->id], $dependentIds)));
    }

    protected function appointmentTotal(array $familyIds): int
    {
        if ($familyIds === []) {
            return 0;
        }

        return DB::table('doctor_bookings')->whereIn('patient_id', $familyIds)->count()
            + DB::table('second_opinions')->whereIn('patient_id', $familyIds)->count()
            + DB::table('diagnostic_test_bookings')->whereIn('patient_id', $familyIds)->count();
    }

    protected function coinsUsedFromBookings(array $familyIds): int
    {
        if ($familyIds === []) {
            return 0;
        }

        $doctor = (int) DB::table('doctor_bookings')
            ->whereIn('patient_id', $familyIds)
            ->where('status', '!=', 'cancelled')
            ->sum('coins_used');
        $diagnostic = (int) DB::table('diagnostic_test_bookings')
            ->whereIn('patient_id', $familyIds)
            ->where('status', '!=', 'cancelled')
            ->sum('coins_used');

        return $doctor + $diagnostic;
    }

    protected function transactionTotal(array $familyIds): int
    {
        if ($familyIds === []) {
            return 0;
        }

        return DB::table('transactions')
            ->join('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->where(function (Builder $inner) use ($familyIds) {
                $inner->whereIn('invoices.primary_person_id', $familyIds)
                    ->orWhereIn('invoices.person_id', $familyIds);
            })
            ->count();
    }

    protected function statusOptions(): array
    {
        $statusesByType = [
            'doctor' => [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'cancelled' => 'Cancelled',
                'completed' => 'Completed',
                'missed' => 'Missed',
            ],
            'second_opinion' => [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'cancelled' => 'Cancelled',
                'completed' => 'Completed',
            ],
            'diagnostic' => [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'cancelled' => 'Cancelled',
                'completed' => 'Completed',
            ],
        ];

        $types = $this->appointmentType === 'all' ? array_keys($statusesByType) : [$this->appointmentType];
        $options = [];
        foreach ($types as $type) {
            foreach ($statusesByType[$type] ?? [] as $value => $label) {
                $options[$value] = $label;
            }
        }

        return $options;
    }

    protected function creatorName(?string $staffId): string
    {
        if (! $staffId) {
            return 'Not recorded';
        }

        $staff = HIPUser::query()->find($staffId);
        if (! $staff) {
            return 'Deleted user';
        }

        $name = trim(collect([$staff->first_name, $staff->last_name])->filter(fn ($part) => filled($part))->implode(' '));

        return $name !== '' ? $name : ($staff->email ?: 'Deleted user');
    }

    protected function age($dob): ?int
    {
        if (! $dob) {
            return null;
        }

        try {
            return Carbon::parse($dob)->age;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function serviceLabel($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        if (is_array($value)) {
            $parts = collect($value)->map(function ($item) {
                if (is_array($item)) {
                    return $item['name'] ?? $item['type'] ?? $item['label'] ?? null;
                }

                return $item;
            })->filter()->map(fn ($item) => ucwords(str_replace('_', ' ', (string) $item)))->values();

            return $parts->isNotEmpty() ? $parts->join(', ') : '—';
        }

        $text = trim((string) $value);

        return $text !== '' ? ucwords(str_replace('_', ' ', $text)) : '—';
    }

    protected function normalizeTab(): void
    {
        if (! in_array($this->tab, ['overview', 'dependents', 'appointments', 'transactions'], true)) {
            $this->tab = 'overview';
        }
    }
}
