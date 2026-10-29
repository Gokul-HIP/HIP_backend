<?php

namespace App\Livewire\Admin\MemberProfile;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MemberIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $statusFilter = 'all';

    public bool $showFormModal = false;

    public ?string $editingUserId = null;

    public ?string $editingPersonId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $mobile = '';

    public ?string $dob = null;

    public string $gender = '';

    public ?string $blood_group = null;

    public ?int $hospital_id = null;

    public ?string $email = null;

    public ?string $emergency_contact_name = null;

    public ?string $emergency_contact_number = null;

    public ?string $address = null;

    public string $relationship = 'Self';

    public ?string $relationship_other = null;

    public ?string $primary_id = null;

    public string $primarySearch = '';

    public array $primaryResults = [];

    public string $primaryLabel = '';

    public string $mrn = '';

    public string $mrnStatus = 'idle';

    public bool $showStatusDialog = false;

    public ?string $statusUserId = null;

    public string $statusUserName = '';

    public bool $statusActivating = false;

    protected $listeners = [
        'openEditModal',
        'toggleActive',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->resetForm();
        $this->mrn = $this->generateNextMrn();
        $this->mrnStatus = 'available';
        $this->showFormModal = true;
    }

    public function openEditModal(string $id): void
    {
        $user = $this->patientQuery()->findOrFail($id);
        $person = Persons::query()
            ->where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->first();

        $this->resetErrorBag();
        $this->resetValidation();
        $this->resetForm();

        $this->editingUserId = $user->id;
        $this->editingPersonId = $person?->id;
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->mobile = $user->mobile_num ?? ($person->mobile ?? '');
        $dobSource = $user->dob ?: $person?->dob;
        $this->dob = $dobSource ? Carbon::parse($dobSource)->format('Y-m-d') : null;
        $this->gender = $this->normalizeGender($user->gender ?: $person?->gender);
        $this->email = $user->email ?: $person?->email;
        $this->blood_group = $person?->blood_group;
        $this->hospital_id = $person && $person->hospital_id !== null ? (int) $person->hospital_id : null;
        $this->emergency_contact_name = $person?->emergency_contact_name;
        $this->emergency_contact_number = $person?->emergency_contact_number;
        $this->address = $person?->address;
        $this->relationship = $this->normalizeRelationship($person?->relationship);
        $this->relationship_other = $person?->relationship_other;
        $this->mrn = $person->mrn ?? '';

        if ($this->relationship !== 'Self' && $person?->parent_id && (string) $person->parent_id !== (string) $person->id) {
            $this->selectPrimary((string) $person->parent_id);
        }

        $this->updatedMrn($this->mrn);
        $this->showFormModal = true;
    }

    public function generateNextMrn(): string
    {
        return app(\App\Services\Patients\PatientAccountProvisioner::class)->nextMrn();
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
            ->when($this->editingPersonId, fn ($q) => $q->where('id', '<>', $this->editingPersonId))
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

        $query = Persons::query()
            ->where('is_primary', true)
            ->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere(DB::raw("CONCAT(first_name,' ',last_name)"), 'like', "%{$term}%");

                $digits = preg_replace('/\D/', '', $term);
                if ($digits !== '') {
                    $q->orWhere('mobile', 'like', "%{$digits}%");
                }
            })
            ->when($this->editingPersonId, fn ($q) => $q->where('id', '<>', $this->editingPersonId))
            ->limit(8)
            ->get();

        $this->primaryResults = $query->map(fn ($person) => [
            'id' => $person->id,
            'name' => $person->display_name,
            'mobile' => $person->mobile,
        ])->toArray();
    }

    public function selectPrimary(string $id): void
    {
        $person = Persons::query()->where('is_primary', true)->find($id);
        if (! $person) {
            return;
        }

        $this->primary_id = $person->id;
        $mobile = trim((string) $person->mobile);
        $this->primaryLabel = $person->display_name.($mobile !== '' ? ' — '.$mobile : '');
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
            'mrn' => ['required', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('persons', 'mrn')->ignore($this->editingPersonId)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9][0-9]{9}$/', function (string $attribute, mixed $value, \Closure $fail): void {
                $digits = preg_replace('/\D/', '', (string) $value);
                $userTaken = HIPUser::query()
                    ->where('mobile_num', $digits)
                    ->when($this->editingUserId, fn ($q) => $q->where('id', '!=', $this->editingUserId))
                    ->exists();

                if ($userTaken) {
                    $fail('This mobile number is already used.');

                    return;
                }

                $personTaken = Persons::query()
                    ->where('mobile', $digits)
                    ->where('is_primary', true)
                    ->when($this->editingPersonId, fn ($q) => $q->where('id', '!=', $this->editingPersonId))
                    ->exists();

                if ($personTaken) {
                    $fail('This mobile number is already used by a primary account.');
                }
            }],
            'dob' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'hospital_id' => ['required', 'exists:hospitals,id'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('healthinpocket_users', 'email')->ignore($this->editingUserId)],
            'emergency_contact_name' => ['nullable', 'max:100'],
            'emergency_contact_number' => ['nullable', 'digits:10'],
            'address' => ['nullable', 'max:500'],
            'relationship' => ['required', 'in:Self,Father,Mother,Spouse,Child,Other'],
            'relationship_other' => ['required_if:relationship,Other', 'nullable', 'max:50'],
            'primary_id' => ['nullable', 'required_unless:relationship,Self', Rule::exists('persons', 'id')->where('is_primary', true)],
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
            'blood_group' => 'Blood group',
            'hospital_id' => 'Hospital',
            'relationship_other' => 'Specify',
            'primary_id' => 'Primary account',
        ];
    }

    public function savePatient(): void
    {
        $this->resetErrorBag();
        $this->email = $this->email !== null && trim($this->email) !== '' ? trim($this->email) : null;
        $this->validate();

        if ($this->mrnStatus === 'taken') {
            $this->dispatch('toast', type: 'error', message: 'MRN ID is already taken');

            return;
        }

        $wasEdit = $this->editingUserId !== null;
        $isSelf = $this->relationship === 'Self';

        try {
            DB::transaction(function () use ($isSelf): void {
                $digits = preg_replace('/\D/', '', $this->mobile);
                $staffId = $this->staffId();
                $personAttrs = [
                    'mrn' => $this->mrn,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'mobile' => $digits,
                    'dob' => $this->dob,
                    'gender' => $this->gender,
                    'blood_group' => $this->blood_group,
                    'hospital_id' => $this->hospital_id,
                    'email' => $this->email,
                    'relationship' => $this->relationship,
                    'relationship_other' => $this->relationship === 'Other' ? $this->relationship_other : null,
                    'emergency_contact_name' => $this->emergency_contact_name ?: null,
                    'emergency_contact_number' => $this->emergency_contact_number ?: null,
                    'address' => $this->address ?: null,
                ];

                if ($this->editingUserId) {
                    $user = $this->patientQuery()->findOrFail($this->editingUserId);
                    $user->update([
                        'first_name' => $this->first_name,
                        'last_name' => $this->last_name,
                        'mobile_num' => $digits,
                        'email' => $this->email,
                        'gender' => $this->gender,
                        'dob' => $this->dob,
                    ]);

                    $person = Persons::query()
                        ->where('hip_user_id', $user->id)
                        ->where('is_primary', true)
                        ->first();

                    if (! $person && $this->editingPersonId) {
                        $person = Persons::query()->find($this->editingPersonId);
                    }

                    $personAttrs['updated_by'] = $staffId;
                    $personAttrs['hip_user_id'] = $user->id;

                    if ($isSelf) {
                        $personAttrs['is_primary'] = true;
                        if (! $person) {
                            $personAttrs['created_by'] = $staffId;
                            $personAttrs['is_active'] = true;
                            $personAttrs['deactivated_at'] = null;
                            $personAttrs['parent_id'] = null;
                            $person = Persons::create($personAttrs);
                        }
                        $person->update(array_merge($personAttrs, [
                            'parent_id' => $person->id,
                        ]));
                        $this->editingPersonId = $person->id;
                    } else {
                        $personAttrs['is_primary'] = false;
                        $personAttrs['parent_id'] = $this->primary_id;
                        if (! $person) {
                            $personAttrs['created_by'] = $staffId;
                            $personAttrs['is_active'] = true;
                            $personAttrs['deactivated_at'] = null;
                            Persons::create($personAttrs);
                        } else {
                            $person->update($personAttrs);
                        }
                    }

                    return;
                }

                $personAttrs['created_by'] = $staffId;
                $personAttrs['updated_by'] = $staffId;
                $personAttrs['is_active'] = true;
                $personAttrs['deactivated_at'] = null;

                if ($isSelf) {
                    app(\App\Services\Patients\PatientAccountProvisioner::class)->createPrimary($personAttrs, [
                        'first_name' => $this->first_name,
                        'last_name' => $this->last_name,
                        'email' => $this->email,
                        'mobile_num' => $digits,
                        'gender' => $this->gender,
                        'dob' => $this->dob,
                        'password' => null,
                        'is_active' => true,
                        'deactivated_at' => null,
                    ]);

                    return;
                }

                app(\App\Services\Patients\PatientAccountProvisioner::class)->createDependent(
                    (string) $this->primary_id,
                    $personAttrs
                );
            });
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'duplicate')) {
                if (! $this->editingUserId) {
                    $this->mrn = $this->generateNextMrn();
                    $this->mrnStatus = 'available';
                    $this->dispatch('toast', type: 'warning', message: 'MRN ID was taken, a new one was generated, please review');

                    return;
                }
            }

            throw $e;
        }

        $message = $wasEdit
            ? 'Patient updated'
            : ($isSelf ? 'Patient created' : 'Dependent added. They are linked to the primary account and do not appear in this list.');

        $this->dispatch('toast', type: 'success', message: $message);
        $this->showFormModal = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function toggleActive(string $id): void
    {
        $user = $this->patientQuery()->findOrFail($id);
        $name = trim($user->first_name.' '.$user->last_name);

        $this->statusUserId = $user->id;
        $this->statusUserName = $name !== '' ? $name : 'this patient';
        $this->statusActivating = ! (bool) $user->is_active;
        $this->showStatusDialog = true;
    }

    public function confirmStatusChange(): void
    {
        if (! $this->statusUserId) {
            return;
        }

        $activating = $this->statusActivating;

        DB::transaction(function () use ($activating): void {
            $user = $this->patientQuery()->findOrFail($this->statusUserId);
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
                        'updated_by' => $this->staffId(),
                    ]);
                });

            if (! $activating) {
                $this->revokeAccess($user);
            }
        });

        $this->showStatusDialog = false;
        $this->statusUserId = null;
        $this->statusUserName = '';
        $this->dispatch('toast', type: 'success', message: $activating ? 'Patient activated' : 'Patient deactivated');
        $this->resetPage();
    }

    public function render()
    {
        $patients = $this->filteredPatients()->paginate(10);
        $personMap = Persons::query()
            ->whereIn('hip_user_id', $patients->getCollection()->pluck('id'))
            ->where('is_primary', true)
            ->get()
            ->keyBy('hip_user_id');

        $hospitals = Hospital::query()->orderBy('name')->get(['id', 'name']);
        $patientBase = HIPUser::query()->whereNull('password');

        return view('livewire.admin.member-profile.member-index', [
            'patients' => $patients,
            'personMap' => $personMap,
            'hospitals' => $hospitals,
            'hospitalNames' => $hospitals->pluck('name', 'id'),
            'kpiTotal' => (clone $patientBase)->count(),
            'kpiActive' => (clone $patientBase)->where('is_active', true)->count(),
            'kpiInactive' => (clone $patientBase)->where('is_active', false)->count(),
        ]);
    }

    protected function filteredPatients(): Builder
    {
        $search = trim($this->search);

        return $this->patientQuery()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('mobile_num', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('hip_id', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when($this->statusFilter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    protected function patientQuery(): Builder
    {
        return HIPUser::query()->whereNull('password');
    }

    protected function revokeAccess(HIPUser $user): void
    {
        $user->tokens()->delete();

        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    protected function staffId(): ?string
    {
        $id = auth('filament')->id();

        return $id ? (string) $id : null;
    }

    protected function resetForm(): void
    {
        $this->editingUserId = null;
        $this->editingPersonId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->mobile = '';
        $this->dob = null;
        $this->gender = '';
        $this->blood_group = null;
        $this->hospital_id = null;
        $this->email = null;
        $this->emergency_contact_name = null;
        $this->emergency_contact_number = null;
        $this->address = null;
        $this->relationship = 'Self';
        $this->relationship_other = null;
        $this->primary_id = null;
        $this->primarySearch = '';
        $this->primaryResults = [];
        $this->primaryLabel = '';
        $this->mrn = '';
        $this->mrnStatus = 'idle';
    }

    protected function normalizeGender(?string $gender): string
    {
        $value = strtolower(trim((string) $gender));

        return match ($value) {
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
            default => '',
        };
    }

    protected function normalizeRelationship(?string $relationship): string
    {
        $value = strtolower(trim((string) $relationship));

        return match ($value) {
            'father' => 'Father',
            'mother' => 'Mother',
            'spouse' => 'Spouse',
            'child' => 'Child',
            'other' => 'Other',
            default => 'Self',
        };
    }
}
