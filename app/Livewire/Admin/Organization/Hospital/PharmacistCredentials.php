<?php

namespace App\Livewire\Admin\Organization\Hospital;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\PharmacistCredential;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class PharmacistCredentials extends Component
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
            ->whereHas('roles', fn ($q) => $q->where('name', 'pharmacist'));

        if ($this->search) {
            $query->where('email', 'like', '%' . $this->search . '%');
        }

        return view('livewire.admin.organization.hospital.pharmacist-credentials', [
            'users' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    public function openAddModal(): void
    {
        $this->resetInput();
        Flux::modal('add-pharmacist-user')->show();
    }

    public function closeModal(): void
    {
        Flux::modal('add-pharmacist-user')->close();
        Flux::modal('edit-pharmacist-user')->close();
        Flux::modal('delete-pharmacist')->close();
        $this->resetInput();
    }

    public function resetInput(): void
    {
        $this->reset([
            'email', 'password', 'password_confirmation', 'first_name', 'last_name',
            'mobile_number', 'gender', 'dob', 'profile_image', 'old_profile_image', 'editingUserId', 'deleteUserId',
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
            'role' => 'pharmacist',
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
        $user->assignRole('pharmacist');

        PharmacistCredential::create([
            'hip_user_id' => $user->id,
            'organization_id' => $this->orgId,
            'hospital_id' => $this->hospitalId,
        ]);

        $this->resetInput();
        Flux::modal('add-pharmacist-user')->close();
        $this->dispatch('toast', type: 'success', message: 'Pharmacist created successfully!');
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

        Flux::modal('edit-pharmacist-user')->show();
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
            'role' => 'pharmacist',
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

        $user->syncRoles(['pharmacist']);
        $user->save();

        PharmacistCredential::updateOrCreate(
            ['hip_user_id' => $user->id],
            ['organization_id' => $this->orgId, 'hospital_id' => $this->hospitalId]
        );

        $this->resetInput();
        Flux::modal('edit-pharmacist-user')->close();
        $this->dispatch('toast', type: 'success', message: 'Pharmacist updated successfully!');
    }

    public function delete($userId): void
    {
        $this->deleteUserId = $userId;
        Flux::modal('delete-pharmacist')->show();
    }

    public function destroy(): void
    {
        $user = HIPUser::find($this->deleteUserId);
        if (! $user) {
            return;
        }

        PharmacistCredential::where('hip_user_id', $user->id)->delete();
        $user->removeRole('pharmacist');
        $user->delete();

        $this->deleteUserId = null;
        Flux::modal('delete-pharmacist')->close();
        $this->dispatch('toast', type: 'success', message: 'Pharmacist deleted successfully!');
    }
}
