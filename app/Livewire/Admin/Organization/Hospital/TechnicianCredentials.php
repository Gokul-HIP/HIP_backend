<?php

namespace App\Livewire\Admin\Organization\Hospital;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\TechnicianCredential;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class TechnicianCredentials extends Component
{
    use WithFileUploads;

    public $email;
    public $password;
    public $password_confirmation;
    public $first_name;
    public $last_name;
    public $mobile_number;
    public $gender;
    public $dob;
    public $profile_image;
    public $old_profile_image;
    public $orgId;
    public $hospitalId;
    public $search = '';
    public $editingUserId = null;
    public $deleteUserId = null;

    public function mount($orgId = null): void
    {
        $this->hospitalId = request()->route('id');
        $hospital = Hospital::find($this->hospitalId);
        $this->orgId = $hospital?->organization_id;
    }

    public function render()
    {
        $query = HIPUser::query()
            ->where('hospital_id', $this->hospitalId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'technician'));

        if ($this->search) {
            $query->where('email', 'like', '%' . $this->search . '%');
        }

        return view('livewire.admin.organization.hospital.technician-credentials', [
            'users' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    public function openAddModal(): void
    {
        $this->resetInput();
        Flux::modal('add-technician-user')->show();
    }

    public function closeModal(): void
    {
        Flux::modal('add-technician-user')->close();
        Flux::modal('edit-technician-user')->close();
        $this->resetInput();
    }

    public function resetInput(): void
    {
        $this->reset([
            'email', 'password', 'password_confirmation', 'first_name', 'last_name',
            'mobile_number', 'gender', 'dob', 'profile_image', 'old_profile_image', 'editingUserId',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function createUser(): void
    {
        $this->validate([
            'email' => 'required|email|unique:healthinpocket_users,email',
            'password' => 'required|min:8|same:password_confirmation',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'dob' => 'nullable|date',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        $userData = [
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'organization_id' => $this->orgId,
            'hospital_id' => $this->hospitalId,
            'role' => 'technician',
            'first_name' => trim((string) $this->first_name) ?: null,
            'last_name' => trim((string) $this->last_name) ?: null,
            'mobile_num' => trim((string) $this->mobile_number) ?: null,
            'gender' => trim((string) $this->gender) ?: null,
            'dob' => trim((string) $this->dob) ?: null,
        ];

        if ($this->profile_image) {
            $filename = Str::uuid() . '_' . hash('sha256', time()) . '.' . $this->profile_image->getClientOriginalExtension();
            $this->profile_image->storeAs('users', $filename, 'public');
            $userData['profile_image'] = $filename;
        }

        $user = HIPUser::create($userData);
        $user->assignRole('technician');

        TechnicianCredential::create([
            'hip_user_id' => $user->id,
            'organization_id' => $this->orgId,
            'hospital_id' => $this->hospitalId,
        ]);

        $this->resetInput();
        Flux::modal('add-technician-user')->close();
        $this->dispatch('toast', type: 'success', message: 'Technician created successfully!');
    }

    public function edit($userId): void
    {
        $user = HIPUser::find($userId);
        if (! $user) {
            return;
        }

        $this->editingUserId = $userId;
        $this->email = $user->email;
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->mobile_number = $user->mobile_num ?? '';
        $this->gender = $user->gender ?? '';
        $this->dob = $user->dob ?? '';
        $this->old_profile_image = $user->profile_image;
        $this->profile_image = null;
        $this->password = '';
        $this->password_confirmation = '';

        Flux::modal('edit-technician-user')->show();
    }

    public function updateUser(): void
    {
        $rules = [
            'email' => 'required|email|unique:healthinpocket_users,email,' . $this->editingUserId,
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'dob' => 'nullable|date',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ];

        if ($this->password) {
            $rules['password'] = 'required|min:8|same:password_confirmation';
        }

        $this->validate($rules);

        $user = HIPUser::find($this->editingUserId);
        if (! $user) {
            return;
        }

        $user->fill([
            'email' => $this->email,
            'first_name' => trim((string) $this->first_name) ?: null,
            'last_name' => trim((string) $this->last_name) ?: null,
            'mobile_num' => trim((string) $this->mobile_number) ?: null,
            'gender' => trim((string) $this->gender) ?: null,
            'dob' => trim((string) $this->dob) ?: null,
            'role' => 'technician',
        ]);

        if ($this->password) {
            $user->password = Hash::make($this->password);
        }

        if ($this->profile_image) {
            if ($user->profile_image && Storage::disk('public')->exists('users/' . $user->profile_image)) {
                Storage::disk('public')->delete('users/' . $user->profile_image);
            }
            $filename = Str::uuid() . '_' . hash('sha256', $user->id . time()) . '.' . $this->profile_image->getClientOriginalExtension();
            $this->profile_image->storeAs('users', $filename, 'public');
            $user->profile_image = $filename;
        }

        $user->syncRoles(['technician']);
        $user->save();

        TechnicianCredential::updateOrCreate(
            ['hip_user_id' => $user->id],
            ['organization_id' => $this->orgId, 'hospital_id' => $this->hospitalId]
        );

        $this->resetInput();
        Flux::modal('edit-technician-user')->close();
        $this->dispatch('toast', type: 'success', message: 'Technician updated successfully!');
    }

    public function delete($userId): void
    {
        $this->deleteUserId = $userId;
        Flux::modal('delete-technician')->show();
    }

    public function destroy(): void
    {
        $user = HIPUser::find($this->deleteUserId);
        if (! $user) {
            return;
        }

        TechnicianCredential::where('hip_user_id', $user->id)->delete();
        $user->removeRole('technician');
        $user->delete();

        $this->deleteUserId = null;
        Flux::modal('delete-technician')->close();
        $this->dispatch('toast', type: 'success', message: 'Technician deleted successfully!');
    }
}
