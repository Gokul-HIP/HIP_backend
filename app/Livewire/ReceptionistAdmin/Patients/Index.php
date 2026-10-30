<?php

namespace App\Livewire\ReceptionistAdmin\Patients;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\Persons;
use App\Models\SecondOpinion;
use App\Services\ReceptionistBookingScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $hospitalFilter = '';

    public string $relationshipFilter = 'all';

    public string $statusFilter = 'all';

    public int $perPage = 10;

    // Form & modal state
    public bool $showFormModal = false;
    public ?string $editingId = null; // null = add
    public string $formPersonName = '';

    public string $mrn = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $mobile = '';
    public ?string $dob = null;
    public string $gender = '';
    public ?string $blood_group = null;
    public ?int $hospital_id = null;
    public ?string $email = null;
    public string $relationship = 'Self';
    public ?string $relationship_other = null;
    public ?string $emergency_contact_name = null;
    public ?string $emergency_contact_number = null;
    public ?string $address = null;
    public bool $is_active = true;

    public ?string $primary_id = null;
    public string $primarySearch = '';
    public array $primaryResults = [];
    public string $primaryLabel = '';

    // mrn status: idle|available|taken|invalid
    public string $mrnStatus = 'idle';

    // hospitals cache
    public array $hospitalsList = [];

    // details drawer and bookings modal
    public bool $showDetailsDrawer = false;
    public array $detailsPerson = [];
    public bool $showBookingsModal = false;
    public array $bookingsList = [];
    public array $bookingPerson = [];
    
    // delete confirmation dialog
    public bool $showDeleteDialog = false;
    public string $deletePersonName = '';
    public bool $showDeactivateDialog = false;
    public ?string $deactivatePersonId = null;
    public string $deactivatePersonName = '';
    public int $deactivateLinkedCount = 0;

    public function mount(ReceptionistBookingScopeService $scope): void
    {
        $this->hospitalsList = $scope->accessibleHospitals()->mapWithKeys(fn($h)=>[$h->id=>$h->name])->toArray();
    }

    protected $listeners = [
        'openDetails',
        'openBookings',
        'openEditModal',
        'confirmDelete',
        'toggleActive',
        'navigate',
    ];

    public function navigate(): void
    {
        // ensure body scroll lock removed on Livewire navigation events
        $this->dispatch('clearModalBodyLock');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingHospitalFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRelationshipFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        if (! in_array((int) $value, [10, 25, 50], true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'relationshipFilter', 'statusFilter']);
        $this->hospitalFilter = '';
        $this->resetPage();
    }

    /***********************
     * Form modal helpers
     ***********************/

    public function openAddModal(?string $presetPrimaryId = null): void
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->editingId = null;
        $this->formPersonName = '';
        $this->relationship = 'Self';
        $this->primary_id = null;
        $this->primaryLabel = '';
        $this->primaryResults = [];
        $this->primarySearch = '';
        $this->is_active = true;
        $this->mrn = $this->generateNextMrn();
        $this->mrnStatus = 'available';
        if ($presetPrimaryId) {
            $this->selectPrimary($presetPrimaryId);
        }
        $this->showFormModal = true;
        $this->dispatch('rp-open-form');
    }

    public function openEditModal(string $id): void
    {
        $person = Persons::with(['parent.hipUser', 'hipUser'])->findOrFail($id);
        $this->editingId = $person->id;
        $this->formPersonName = $person->display_name;
        $this->mrn = $person->mrn ?? '';
        $this->first_name = $person->first_name ?? '';
        $this->last_name = $person->last_name ?? '';
        $this->mobile = $person->mobile ?? '';
        $this->dob = $person->dob ? Carbon::parse($person->dob)->format('Y-m-d') : null;
        $this->gender = $person->gender ?? '';
        $this->blood_group = $person->blood_group ?? null;
        $this->hospital_id = $person->hospital_id;
        $this->email = $person->email;
        $this->relationship = $person->relationship ?? 'Self';
        $this->relationship_other = $person->relationship_other;
        $this->emergency_contact_name = $person->emergency_contact_name;
        $this->emergency_contact_number = $person->emergency_contact_number;
        $this->address = $person->address;
        $this->is_active = (bool) ($person->is_active ?? true);
        if ($person->parent_id) {
            $parent = $person->parent;
            if ($parent) {
                $this->primary_id = $parent->id;
                $this->primaryLabel = $parent->display_name . ' - ' . ($this->formatMobile($parent->mobile) ?? '');
            }
        }
        $this->mrnStatus = 'available';
        $this->showFormModal = true;
        $this->dispatch('rp-open-form');
    }

    public function openDetails(string $id): void
    {
        $p = Persons::with(['hipUser', 'parent.hipUser', 'members.hipUser', 'creator:id,first_name,last_name,email', 'updater:id,first_name,last_name,email'])->findOrFail($id);
        $age = null;
        if ($p->dob) {
            try { $age = Carbon::parse($p->dob)->age; } catch (\Throwable $e) { $age = null; }
        }

        $this->detailsPerson = [
            'id' => $p->id,
            'name' => $p->display_name,
            'initials' => $p->initials,
            'mrn' => $p->mrn,
            'member_id' => $p->hipUser?->hip_id,
            'mobile' => $this->formatMobile($p->mobile),
            'email' => $p->email,
            'gender' => $this->titleLabel($p->gender),
            'dob' => $p->dob ? Carbon::parse($p->dob)->format('d M Y') : null,
            'age' => $age,
            'blood_group' => $p->blood_group,
            'hospital' => $this->hospitalsList[$p->hospital_id] ?? null,
            'relationship' => $this->relationshipLabel($p),
            'is_primary' => $this->isPrimaryPerson($p),
            'is_active' => (bool) ($p->is_active ?? true),
            'deactivated_at' => $this->formatDateTime($p->deactivated_at),
            'deactivated_date' => $p->deactivated_at ? $p->deactivated_at->timezone(config('app.timezone'))->format('d M Y') : null,
            'created_at' => $this->formatDateTime($p->created_at),
            'created_relative' => $this->relativeTime($p->created_at),
            'updated_at' => $this->formatDateTime($p->updated_at),
            'updated_relative' => $this->relativeTime($p->updated_at),
            'creator_name' => $this->staffDisplayName($p->created_by, $p->creator),
            'updater_name' => $this->staffDisplayName($p->updated_by, $p->updater),
            'parent' => $p->parent ? ($p->parent->display_name . ' - ' . ($this->formatMobile($p->parent->mobile) ?? '')) : null,
            'emergency_contact_name' => $p->emergency_contact_name,
            'emergency_contact_number' => $this->formatMobile($p->emergency_contact_number),
            'address' => $p->address,
            'members' => $p->members
                ->reject(fn ($m) => (string) $m->id === (string) $p->id)
                ->map(fn($m)=>[
                    'id' => $m->id,
                    'name' => $m->display_name,
                    'initials' => $m->initials,
                    'relationship' => $this->relationshipLabel($m),
                    'mobile' => $this->formatMobile($m->mobile),
                ])->values()->toArray(),
        ];

        $this->showDetailsDrawer = true;
    }

    public function openBookings(string $id): void
    {
        $person = Persons::with('hipUser')->findOrFail($id);

        $this->bookingPerson = [
            'id' => $person->id,
            'name' => $person->display_name,
            'mrn' => $person->mrn,
            'member_id' => $person->hipUser?->hip_id,
        ];

        $doctor = DoctorBooking::with(['hospital', 'branch', 'doctor', 'department'])
            ->where('patient_id', $id)
            ->get()
            ->map(fn($b) => $this->normalizeDoctorBooking($b));

        $second = SecondOpinion::with(['branch', 'doctor', 'speciality', 'member'])
            ->where('patient_id', $id)
            ->get()
            ->map(fn($b) => $this->normalizeSecondOpinionBooking($b));

        $diag = DiagnosticTestBooking::with(['branch', 'diagnosticCenter', 'diagnosticPackage', 'diseasePackage'])
            ->where('patient_id', $id)
            ->get()
            ->map(fn($b) => $this->normalizeDiagnosticBooking($b));

        $all = $doctor->concat($second)->concat($diag)
            ->sortByDesc('sort_ts')
            ->values()
            ->toArray();
        $this->bookingsList = $all;
        $this->showBookingsModal = true;
    }

    public function editFromDetails(): void
    {
        $id = $this->detailsPerson['id'] ?? null;
        $this->showDetailsDrawer = false;

        if ($id) {
            $this->openEditModal((string) $id);
        }
    }

    public function bookingsFromDetails(): void
    {
        $id = $this->detailsPerson['id'] ?? null;
        $this->showDetailsDrawer = false;

        if ($id) {
            $this->openBookings((string) $id);
        }
    }

    public function generateNextMrn(): string
    {
        return DB::transaction(function () {
            $max = DB::table('persons')
                ->whereRaw("mrn REGEXP ?", ['^MRN[0-9]+$'])
                ->selectRaw('MAX(CAST(SUBSTRING(mrn,4) AS UNSIGNED)) as maxnum')
                ->value('maxnum');

            $next = ((int) $max) + 1;
            $number = str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            return 'MRN' . $number;
        });
    }

    public function regenerateMrn(): void
    {
        $this->mrn = $this->generateNextMrn();
        $this->updatedMrn($this->mrn);
    }

    public function updatedMrn($value): void
    {
        $v = strtoupper(trim((string) $value));
        $v = preg_replace('/[^A-Z0-9-]/', '', $v);
        $v = substr($v, 0, 20);
        $this->mrn = $v;

        if ($v === '') {
            $this->mrnStatus = 'invalid';
            return;
        }

        if (! preg_match('/^[A-Z0-9-]+$/', $v)) {
            $this->mrnStatus = 'invalid';
            return;
        }

        $exists = Persons::where('mrn', $v)
            ->when($this->editingId, fn($q) => $q->where('id', '<>', $this->editingId))
            ->exists();

        $this->mrnStatus = $exists ? 'taken' : 'available';
    }

    public function updatedRelationship(): void
    {
        if ($this->relationship === 'Self') {
            $this->primary_id = null;
            $this->primarySearch = '';
            $this->primaryResults = [];
            $this->primaryLabel = '';
        }

        if ($this->relationship !== 'Other') {
            $this->relationship_other = null;
        }
    }

    public function updatedPrimarySearch(): void
    {
        $term = trim($this->primarySearch);
        if (strlen(preg_replace('/\s+/', '', $term)) < 2) {
            $this->primaryResults = [];
            return;
        }

        $query = Persons::query()->where(function ($q) {
            $q->where('is_primary', true)->orWhereColumn('parent_id', 'id');
        })
            ->with('hipUser')
            ->active()
            ->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere(DB::raw("CONCAT(first_name,' ',last_name)"), 'like', "%{$term}%")
                    ->orWhereHas('hipUser', function ($user) use ($term) {
                        $user->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%$term%"]);
                    });

                $digits = preg_replace('/\D/', '', $term);
                if ($digits !== '') {
                    $q->orWhere(DB::raw("REPLACE(mobile, '+', '')"), 'like', "%{$digits}%");
                }
            })
            ->when($this->editingId, fn($q) => $q->where('id', '<>', $this->editingId))
            ->limit(8)
            ->get();

        $this->primaryResults = $query->map(fn($p)=>[
            'id'=>$p->id,
            'name'=>$p->display_name,
            'mobile'=>$this->formatMobile($p->mobile),
        ])->toArray();
    }

    public function selectPrimary(string $id): void
    {
        $p = Persons::with('hipUser')->find($id);
        if (! $p) return;
        $this->primary_id = $p->id;
        $this->primaryLabel = $p->display_name . ' - ' . ($this->formatMobile($p->mobile) ?? '');
        $this->primaryResults = [];
        $this->primarySearch = '';
    }

    public function clearPrimary(): void
    {
        $this->primary_id = null;
        $this->primaryLabel = '';
    }

    protected function rules(): array
    {
        return [
            'mrn' => [ 'required', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('persons','mrn')->ignore($this->editingId) ],
            'first_name' => ['required','string','max:100'],
            'last_name' => ['required','string','max:100'],
            'mobile' => ['required','digits:10','regex:/^[6-9][0-9]{9}$/'],
            'dob' => ['required','date','before_or_equal:today'],
            'gender' => ['required','in:Male,Female,Other'],
            'blood_group' => ['nullable','in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'hospital_id' => ['required','exists:hospitals,id'],
            'email' => ['nullable','email','max:150'],
            'relationship' => ['required','in:Self,Father,Mother,Spouse,Child,Other'],
            'relationship_other' => ['required_if:relationship,Other','nullable','max:50'],
            'primary_id' => ['nullable', 'required_unless:relationship,Self', 'exists:persons,id'],
            'emergency_contact_name' => ['nullable','max:100'],
            'emergency_contact_number' => ['nullable','digits:10'],
            'address' => ['nullable','max:500'],
            'is_active' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'mrn' => 'MRN ID',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'mobile' => 'Mobile number',
            'dob' => 'Date of birth',
            'relationship_other' => 'Specify relationship',
            'primary_id' => 'Primary account',
        ];
    }

    public function saveMember(): void
    {
        $this->resetErrorBag();
        $this->validate();

        if ($this->mrnStatus === 'taken') {
            $this->dispatch('notify', type: 'error', message: 'MRN ID is already taken');
            return;
        }

        try {
            DB::transaction(function () {
                $active = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN);
                $attrs = [
                    'mrn' => $this->mrn,
                    'first_name' => $this->first_name ?: null,
                    'last_name' => $this->last_name ?: null,
                    'mobile' => preg_replace('/\D/','', $this->mobile) ?: null,
                    'dob' => $this->dob ?: null,
                    'gender' => $this->gender ?: null,
                    'blood_group' => $this->blood_group ?: null,
                    'hospital_id' => $this->hospital_id ?: null,
                    'email' => $this->email ?: null,
                    'relationship' => $this->relationship ?: null,
                    'relationship_other' => $this->relationship === 'Other' ? $this->relationship_other : null,
                    'emergency_contact_name' => $this->emergency_contact_name ?: null,
                    'emergency_contact_number' => $this->emergency_contact_number ?: null,
                    'address' => $this->address ?: null,
                    'is_active' => $active,
                ];

                if ($this->editingId) {
                    $person = Persons::findOrFail($this->editingId);
                    $attrs['updated_by'] = $this->staffId();
                    if ($active) {
                        $attrs['deactivated_at'] = null;
                    } elseif ($person->is_active) {
                        $attrs['deactivated_at'] = now();
                    }
                    // if changing to Self, ensure is_primary true and parent_id null
                    if ($this->relationship === 'Self') {
                        $attrs['is_primary'] = true;
                        $attrs['parent_id'] = null;
                    } else {
                        $attrs['is_primary'] = false;
                        $attrs['parent_id'] = $this->primary_id;
                    }

                    $person->update($attrs);
                    Log::info('Person updated', ['id'=>$person->id, 'attrs'=>$attrs]);
                } else {
                    $staffId = $this->staffId();
                    $attrs['created_by'] = $staffId;
                    $attrs['updated_by'] = $staffId;
                    $attrs['deactivated_at'] = $active ? null : now();

                    if ($this->relationship === 'Self') {
                        $attrs['is_primary'] = true;
                        $attrs['parent_id'] = null;
                        $person = Persons::create($attrs);
                        // set parent_id to self for primary
                        $person->update(['parent_id' => $person->id]);
                    } else {
                        $attrs['is_primary'] = false;
                        $attrs['parent_id'] = $this->primary_id;
                        $person = Persons::create($attrs);
                    }

                    Log::info('Person created', ['id'=>$person->id, 'attrs'=>$attrs]);
                }
            });
        } catch (QueryException $e) {
            // handle duplicate mrn
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'duplicate')) {
                if (! $this->editingId) {
                    $this->mrn = $this->generateNextMrn();
                    $this->mrnStatus = 'available';
                    $this->dispatch('notify', type: 'warning', message: 'MRN ID was taken, a new one was generated, please review');
                    return;
                }
            }

            throw $e;
        }

        $this->dispatch('notify', type: 'success', message: $this->editingId ? 'Member updated' : 'Member created');
        $this->showFormModal = false;
        $this->resetForm();
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->formPersonName = '';
        $this->mrn = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->mobile = '';
        $this->dob = null;
        $this->gender = '';
        $this->blood_group = null;
        $this->hospital_id = null;
        $this->email = null;
        $this->relationship = 'Self';
        $this->relationship_other = null;
        $this->primary_id = null;
        $this->primaryLabel = '';
        $this->primaryResults = [];
        $this->primarySearch = '';
        $this->emergency_contact_name = null;
        $this->emergency_contact_number = null;
        $this->address = null;
        $this->is_active = true;
        $this->mrnStatus = 'idle';
        $this->deletePersonName = '';
        $this->showDeactivateDialog = false;
        $this->deactivatePersonId = null;
        $this->deactivatePersonName = '';
        $this->deactivateLinkedCount = 0;
    }

    public function confirmDelete(string $id): void
    {
        $person = Persons::with('hipUser')->findOrFail($id);
        $this->editingId = $id;
        $this->deletePersonName = $person->display_name;
        $this->showDeleteDialog = true;
    }

    public function deleteMember(): void
    {
        if (! $this->editingId) return;
        $person = Persons::find($this->editingId);
        if (! $person) return;

        // check bookings
        $hasBookings = DoctorBooking::where('patient_id', $person->id)->exists()
            || SecondOpinion::where('patient_id', $person->id)->exists()
            || DiagnosticTestBooking::where('patient_id', $person->id)->exists();

        if ($hasBookings) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete: person has bookings');
            return;
        }

        if ($person->members()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete: person has linked members');
            return;
        }

        if (method_exists($person, 'trashed')) {
            $person->delete();
        } else {
            $person->delete();
        }

        $this->dispatch('notify', type: 'success', message: 'Person deleted');
        $this->showDeleteDialog = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function toggleActive(string $id): void
    {
        $person = Persons::with(['hipUser', 'members'])->findOrFail($id);

        if ((bool) ($person->is_active ?? true)) {
            $this->deactivatePersonId = $person->id;
            $this->deactivatePersonName = $person->display_name;
            $this->deactivateLinkedCount = $this->isPrimaryPerson($person)
                ? $person->members->reject(fn ($member) => (string) $member->id === (string) $person->id)->count()
                : 0;
            $this->showDeactivateDialog = true;

            return;
        }

        $person->update([
            'is_active' => true,
            'deactivated_at' => null,
            'updated_by' => $this->staffId(),
        ]);

        $this->dispatch('notify', type: 'success', message: 'Patient activated');
        $this->resetPage();
    }

    public function deactivateMember(): void
    {
        if (! $this->deactivatePersonId) {
            return;
        }

        $person = Persons::findOrFail($this->deactivatePersonId);
        $person->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'updated_by' => $this->staffId(),
        ]);

        $this->showDeactivateDialog = false;
        $this->deactivatePersonId = null;
        $this->deactivatePersonName = '';
        $this->deactivateLinkedCount = 0;
        $this->dispatch('notify', type: 'success', message: 'Patient deactivated');
        $this->resetPage();
    }

    protected function patientRows(ReceptionistBookingScopeService $scope): Builder
    {
        $hospitalIds = $this->hospitalFilter !== '' ? $scope->hospitalIds($this->hospitalFilter) : [];
        $diagnosticIds = $this->hospitalFilter !== '' ? $scope->diagnosticCenterIds($this->hospitalFilter) : [];
        $search = trim($this->search);

        $personsQuery = Persons::query()
            ->select('persons.*')
            ->with([
                'parent.hipUser',
                'hipUser:id,first_name,last_name,hip_id',
                'creator:id,first_name,last_name,email',
                'updater:id,first_name,last_name,email',
            ]);

        if ($search !== '') {
            $personsQuery->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT_WS(' ', persons.first_name, persons.last_name) LIKE ?", ["%$search%"])
                    ->orWhere('persons.mobile', 'like', "%$search%")
                    ->orWhere('persons.mrn', 'like', "%$search%")
                    ->orWhereHas('hipUser', function ($user) use ($search) {
                        $user->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%$search%"])
                            ->orWhere('hip_id', 'like', "%$search%");
                    });
            });
        }

        if ($this->relationshipFilter !== 'all') {
            $personsQuery->where('persons.relationship', $this->relationshipFilter);
        }

        if ($this->statusFilter === 'active') {
            $personsQuery->where('persons.is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $personsQuery->where('persons.is_active', false);
        }

        if ($this->hospitalFilter !== '') {
            $personsQuery->where(function (Builder $query) use ($hospitalIds, $diagnosticIds) {
                $query->whereIn('persons.hospital_id', $hospitalIds)
                    ->orWhereExists(function ($bookings) use ($hospitalIds) {
                        $bookings->selectRaw('1')
                            ->from('doctor_bookings')
                            ->whereColumn('doctor_bookings.patient_id', 'persons.id')
                            ->whereIn('doctor_bookings.hospital_id', $hospitalIds);
                    })
                    ->orWhereExists(function ($bookings) use ($hospitalIds) {
                        $bookings->selectRaw('1')
                            ->from('second_opinions')
                            ->whereColumn('second_opinions.patient_id', 'persons.id')
                            ->whereIn('second_opinions.branch_id', $hospitalIds);
                    })
                    ->orWhereExists(function ($bookings) use ($hospitalIds, $diagnosticIds) {
                        $bookings->selectRaw('1')
                            ->from('diagnostic_test_bookings')
                            ->whereColumn('diagnostic_test_bookings.patient_id', 'persons.id')
                            ->where(function ($bookingQuery) use ($hospitalIds, $diagnosticIds) {
                                $bookingQuery->whereIn('diagnostic_test_bookings.branch_id', $hospitalIds);
                                if ($diagnosticIds !== []) {
                                    $bookingQuery->orWhereIn('diagnostic_test_bookings.diagnostic_center_id', $diagnosticIds);
                                }
                            });
                    });
            });
        }

        return $personsQuery->orderByDesc('persons.created_at')->orderByDesc('persons.id');
    }

    protected function staffId(): ?string
    {
        return auth('filament')->id();
    }

    protected function staffDisplayName(?string $staffId, $staff): ?string
    {
        if (! $staffId) {
            return null;
        }

        if (! $staff) {
            return 'Deleted user';
        }

        $name = trim(collect([$staff->first_name ?? null, $staff->last_name ?? null])
            ->filter(fn ($part) => filled($part))
            ->implode(' '));

        if ($name !== '') {
            return $name;
        }

        return $staff->email ?: 'Deleted user';
    }

    protected function formatDateTime($date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->timezone(config('app.timezone'))->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function relativeTime($date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->timezone(config('app.timezone'))->diffForHumans();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function relationshipLabel(Persons $person): string
    {
        $relationship = trim((string) $person->relationship);

        if (strtolower($relationship) === 'other') {
            return trim((string) $person->relationship_other) ?: 'Other';
        }

        return $relationship !== '' ? $this->titleLabel($relationship) : '';
    }

    protected function isPrimaryPerson(Persons $person): bool
    {
        return (bool) $person->is_primary || (string) $person->parent_id === (string) $person->id;
    }

    protected function titleLabel(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? ucwords(strtolower(str_replace('_', ' ', $value))) : '';
    }

    protected function formatMobile(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) > 10 && str_starts_with($digits, '91')) {
            $digits = substr($digits, -10);
        }

        return '+91 ' . $digits;
    }

    protected function formatDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function bookingTimestamp($date, $createdAt): int
    {
        try {
            return $date ? Carbon::parse($date)->startOfDay()->timestamp : Carbon::parse($createdAt)->timestamp;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function slotLabel($slots): ?string
    {
        if (is_string($slots)) {
            $decoded = json_decode($slots, true);
            $slots = json_last_error() === JSON_ERROR_NONE ? $decoded : $slots;
        }

        if (is_array($slots)) {
            $labels = collect($slots)->map(function ($slot) {
                if (is_array($slot)) {
                    return $slot['label'] ?? $slot['time'] ?? $slot['slot'] ?? $slot['value'] ?? null;
                }

                return $slot;
            })->filter()->values();

            return $labels->isNotEmpty() ? $labels->join(', ') : null;
        }

        $slots = trim((string) $slots);

        return $slots !== '' ? $slots : null;
    }

    protected function normalizeDoctorBooking(DoctorBooking $booking): array
    {
        $date = $booking->booking_date;

        return [
            'type' => 'Doctor',
            'ref' => 'DR-' . $booking->id,
            'title' => $booking->doctor?->name ?: ($booking->department?->name ?: ($booking->purpose ?: 'Doctor consultation')),
            'hospital' => $booking->hospital?->name ?: $booking->branch?->name,
            'date' => $this->formatDate($date),
            'date_sort' => $date ? Carbon::parse($date)->format('Y-m-d') : null,
            'time' => $this->slotLabel($booking->required_time_slots),
            'status' => $booking->status ?: 'pending',
            'status_label' => $this->titleLabel($booking->status ?: 'pending'),
            'sort_ts' => $this->bookingTimestamp($date, $booking->created_at),
        ];
    }

    protected function normalizeSecondOpinionBooking(SecondOpinion $booking): array
    {
        $date = $booking->preferred_date;

        return [
            'type' => 'Second Opinion',
            'ref' => 'SO-' . $booking->id,
            'title' => $booking->doctor?->name ?: ($booking->speciality?->name ?: ($booking->diagnosis ?: 'Second opinion')),
            'hospital' => $booking->branch?->name,
            'date' => $this->formatDate($date),
            'date_sort' => $date ? Carbon::parse($date)->format('Y-m-d') : null,
            'time' => $this->slotLabel($booking->preferred_time_slots),
            'status' => $booking->status ?: 'pending',
            'status_label' => $this->titleLabel($booking->status ?: 'pending'),
            'sort_ts' => $this->bookingTimestamp($date, $booking->created_at),
        ];
    }

    protected function normalizeDiagnosticBooking(DiagnosticTestBooking $booking): array
    {
        $date = $booking->booking_date;
        $testItems = collect($booking->test_items ?? [])->map(function ($item) {
            if (is_array($item)) {
                return $item['test_name'] ?? $item['name'] ?? $item['label'] ?? null;
            }

            return $item;
        })->filter()->values();

        return [
            'type' => 'Diagnostic',
            'ref' => 'DG-' . $booking->id,
            'title' => $booking->diagnosticPackage?->name
                ?: ($booking->diseasePackage?->name ?: ($testItems->first() ?: ($booking->test_type ?: 'Diagnostic test'))),
            'hospital' => $booking->branch?->name ?: $booking->diagnosticCenter?->name,
            'date' => $this->formatDate($date),
            'date_sort' => $date ? Carbon::parse($date)->format('Y-m-d') : null,
            'time' => $this->slotLabel($booking->required_time_slots),
            'status' => $booking->status ?: 'pending',
            'status_label' => $this->titleLabel($booking->status ?: 'pending'),
            'sort_ts' => $this->bookingTimestamp($date, $booking->created_at),
        ];
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = collect($parts)->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');

        return $letters !== '' ? $letters : '?';
    }

    public function render(ReceptionistBookingScopeService $scope)
    {
        $patients = $this->patientRows($scope)->paginate($this->perPage);
        $patients->getCollection()->each(function (Persons $patient) {
            $patient->setAttribute('creator_name', $this->staffDisplayName($patient->created_by, $patient->creator));
            $patient->setAttribute('created_label', $this->formatDateTime($patient->created_at));
        });

        return view('livewire.receptionist-admin.patients.index', [
            'patients' => $patients,
            'hospitals' => $scope->accessibleHospitals(),
        ]);
    }
}
