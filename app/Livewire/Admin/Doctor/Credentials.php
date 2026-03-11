<?php

namespace App\Livewire\Admin\Doctor;

use App\Models\Doctor;
use App\Models\DoctorCredential;
use App\Models\HIPUser;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Credentials extends Component
{
    public $doctorId;
    public $doctorName = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $credentialId;

    #[On('open-doctor-credentials')]
    public function openDoctorCredentials($doctorId)
    {
        $doctor = Doctor::find($doctorId);

        if (!$doctor) {
            return;
        }

        $credential = DoctorCredential::where('doctor_id', $doctor->id)->first();

        $this->doctorId = $doctor->id;
        $this->doctorName = $doctor->name ?? '';
        $this->credentialId = $credential?->id;
        $this->email = $doctor->email ?: ($credential?->email ?? '');
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetErrorBag();
        $this->resetValidation();

        Flux::modal('doctor-credentials')->show();
    }

    public function closeModal()
    {
        Flux::modal('doctor-credentials')->close();
        $this->resetForm();
    }

    public function saveCredentials()
    {
        if (!$this->doctorId) {
            return;
        }

        $doctor = Doctor::find($this->doctorId);

        if (!$doctor) {
            return;
        }

        $credential = DoctorCredential::where('doctor_id', $doctor->id)->first();
        $linkedUser = null;

        if (!empty($credential?->email)) {
            $linkedUser = HIPUser::where('email', $credential->email)->first();
        }

        if (!$linkedUser && !empty($doctor->email)) {
            $linkedUser = HIPUser::where('email', $doctor->email)->first();
        }

        $rules = [
            'email' => [
                'required',
                'email',
                Rule::unique('doctors', 'email')->ignore($doctor->id),
                Rule::unique('doctor_credentials', 'email')->ignore($credential?->id),
                Rule::unique('healthinpocket_users', 'email')->ignore($linkedUser?->id),
            ],
        ];

        if ($credential) {
            if (!empty($this->password) || !$linkedUser) {
                $rules['password'] = 'required|min:8|confirmed';
            }
        } else {
            $rules['password'] = 'required|min:8|confirmed';
        }

        $this->validate($rules);

        $doctor->update([
            'email' => $this->email,
        ]);

        $payload = [
            'doctor_id' => $doctor->id,
            'email' => $this->email,
        ];

        if (!empty($this->password)) {
            $payload['password'] = Hash::make($this->password);
        }

        if ($credential) {
            $credential->update($payload);
        } else {
            DoctorCredential::create($payload);
        }

        $doctorRole = Role::firstOrCreate([
            'name' => 'doctor',
            'guard_name' => 'filament',
        ]);

        if ($linkedUser) {
            $linkedUser->email = $this->email;
            $linkedUser->role = 'doctor';

            if (!empty($this->password)) {
                $linkedUser->password = Hash::make($this->password);
            }

            $linkedUser->save();

            if (!$linkedUser->hasRole('doctor')) {
                $linkedUser->assignRole($doctorRole);
            }
        } else {
            $doctorUser = HIPUser::create([
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'doctor',
                'organization_id' => $doctor->organization_id,
            ]);

            $doctorUser->assignRole($doctorRole);
        }

        Flux::modal('doctor-credentials')->close();
        $this->dispatch('relod-doc');
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Doctor credentials saved successfully!'
        );
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->doctorId = null;
        $this->doctorName = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->credentialId = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.doctor.credentials');
    }
}
