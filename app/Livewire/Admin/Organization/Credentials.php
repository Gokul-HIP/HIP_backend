<?php

namespace App\Livewire\Admin\Organization;

use App\Models\HIPUser;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Credentials extends Component
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
    public $search = '';
    public $editingUserId = null;
    public $deleteUserId = null;

    public function mount($orgId = null)
    {
        $this->orgId = $orgId ?: request()->route('id');

        abort_unless(Organization::whereKey($this->orgId)->exists(), 404);
    }

    public function render()
    {
        $query = HIPUser::where('organization_id', $this->orgId)
            ->whereNull('hospital_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'healthcare_admin');
            });

        if ($this->search) {
            $query->where(function ($subQuery) {
                $subQuery->where('email', 'like', '%' . $this->search . '%')
                    ->orWhere('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%');
            });
        }

        $admins = $query->orderBy('created_at', 'desc')->get();

        return view('livewire.admin.organization.credentials', [
            'admins' => $admins,
        ]);
    }

    public function openAddModal()
    {
        $this->resetInput();
        Flux::modal('add-admin-user')->show();
    }

    public function closeModal()
    {
        Flux::modal('add-admin-user')->close();
        Flux::modal('edit-admin-user')->close();
        Flux::modal('delete-admin-user')->close();
        $this->resetInput();
    }

    public function resetInput()
    {
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->mobile_number = '';
        $this->gender = '';
        $this->dob = '';
        $this->profile_image = null;
        $this->old_profile_image = null;
        $this->editingUserId = null;
        $this->deleteUserId = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function removeImage()
    {
        $this->profile_image = null;
        $this->dispatch('reset-file-input');
    }

    public function removeOldImage()
    {
        $this->old_profile_image = null;
    }

    public function createUser()
    {
        $this->validate([
            'email' => 'required|email|unique:healthinpocket_users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8|same:password',
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
            'hospital_id' => null,
            'role' => 'healthcare_admin',
            'first_name' => filled(trim((string) $this->first_name)) ? trim((string) $this->first_name) : null,
            'last_name' => filled(trim((string) $this->last_name)) ? trim((string) $this->last_name) : null,
            'mobile_num' => filled(trim((string) $this->mobile_number)) ? trim((string) $this->mobile_number) : null,
            'gender' => filled(trim((string) $this->gender)) ? trim((string) $this->gender) : null,
            'dob' => filled(trim((string) $this->dob)) ? trim((string) $this->dob) : null,
        ];

        if ($this->profile_image) {
            $extension = $this->profile_image->getClientOriginalExtension();
            $filename = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $this->profile_image->storeAs('users', $filename, 'public');
            $userData['profile_image'] = $filename;
        }

        $user = HIPUser::create($userData);
        $user->assignRole('healthcare_admin');

        $this->resetInput();
        Flux::modal('add-admin-user')->close();
        $this->dispatch('toast', type: 'success', message: 'Healthcare admin created successfully!');
    }

    public function edit($userId)
    {
        $user = HIPUser::where('organization_id', $this->orgId)
            ->whereNull('hospital_id')
            ->whereKey($userId)
            ->first();

        if (!$user) {
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

        Flux::modal('edit-admin-user')->show();
    }

    public function updateUser()
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

        if (!empty($this->password)) {
            $rules['password'] = 'required|min:8';
            $rules['password_confirmation'] = 'required|min:8|same:password';
        }

        $this->validate($rules);

        $user = HIPUser::where('organization_id', $this->orgId)
            ->whereNull('hospital_id')
            ->whereKey($this->editingUserId)
            ->first();

        if (!$user) {
            return;
        }

        $user->email = $this->email;
        $user->organization_id = $this->orgId;
        $user->hospital_id = null;
        $user->role = 'healthcare_admin';
        $user->first_name = filled(trim((string) $this->first_name)) ? trim((string) $this->first_name) : null;
        $user->last_name = filled(trim((string) $this->last_name)) ? trim((string) $this->last_name) : null;
        $user->mobile_num = filled(trim((string) $this->mobile_number)) ? trim((string) $this->mobile_number) : null;
        $user->gender = filled(trim((string) $this->gender)) ? trim((string) $this->gender) : null;
        $user->dob = filled(trim((string) $this->dob)) ? trim((string) $this->dob) : null;
        $user->syncRoles(['healthcare_admin']);

        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }

        if ($this->profile_image) {
            if ($user->profile_image && Storage::disk('public')->exists('users/' . $user->profile_image)) {
                Storage::disk('public')->delete('users/' . $user->profile_image);
            }

            $extension = $this->profile_image->getClientOriginalExtension();
            $filename = Str::uuid() . '_' . hash('sha256', $user->id . time()) . '.' . $extension;
            $this->profile_image->storeAs('users', $filename, 'public');
            $user->profile_image = $filename;
        } elseif ($this->old_profile_image === null && $user->profile_image) {
            if (Storage::disk('public')->exists('users/' . $user->profile_image)) {
                Storage::disk('public')->delete('users/' . $user->profile_image);
            }
            $user->profile_image = null;
        }

        $user->save();

        $this->resetInput();
        Flux::modal('edit-admin-user')->close();
        $this->dispatch('toast', type: 'success', message: 'Healthcare admin updated successfully!');
    }

    public function delete($userId)
    {
        $this->deleteUserId = $userId;
        Flux::modal('delete-admin-user')->show();
    }

    public function destroy()
    {
        $user = HIPUser::where('organization_id', $this->orgId)
            ->whereNull('hospital_id')
            ->whereKey($this->deleteUserId)
            ->first();

        if (!$user) {
            return;
        }

        if ($user->hasRole('healthcare_admin')) {
            $user->removeRole('healthcare_admin');
        }

        $user->delete();

        $this->deleteUserId = null;
        Flux::modal('delete-admin-user')->close();
        $this->dispatch('toast', type: 'success', message: 'Healthcare admin deleted successfully!');
    }
}
