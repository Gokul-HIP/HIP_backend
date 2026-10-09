<?php

namespace App\Livewire\Admin\MemberProfile;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
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

        if ($transactions) {
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
            ->select('transactions.*', 'invoices.service_types')
            ->whereRaw('1 = 0');

        if ($familyIds === []) {
            return $query->orderByDesc('transactions.id');
        }

        return DB::table('transactions')
            ->join('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->select('transactions.*', 'invoices.service_types')
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
