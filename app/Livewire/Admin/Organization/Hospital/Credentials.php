<?php

namespace App\Livewire\Admin\Organization\Hospital;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use Flux\Flux;
use App\Models\HIPUser;
use App\Models\Hospital;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;

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
    public $hospitalId;
    public $search = '';
    public $editingUserId = null;
    public $deleteUserId = null;

    /** Selected role name for add/edit (hospital_admin or cashier_admin) */
    public $selected_role = 'hospital_admin';

    public function mount($orgId = null)
    {
        // Get hospital ID from route parameter
        $this->hospitalId = request()->route('id');
        
        // Get organization ID from hospital if not provided
        if ($orgId) {
            $hospital = Hospital::find($this->hospitalId);
            $this->orgId = $hospital->organization_id;
        } else {
            $hospital = Hospital::find($this->hospitalId);
            $this->orgId = $hospital->organization_id;
        }
    }

    public function render()
    {
        $query = HIPUser::where('hospital_id', $this->hospitalId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['hospital_admin', 'cashier_admin']);
            });

        if ($this->search) {
            $query->where('email', 'like', '%' . $this->search . '%');
        }

        $hospitals = $query->orderBy('created_at', 'desc')->get();

        $roles = Role::where('guard_name', 'filament')
            ->whereIn('name', ['hospital_admin', 'cashier_admin'])
            ->orderBy('name')
            ->get();

        return view('livewire.admin.organization.hospital.credentials', [
            'hospitals' => $hospitals,
            'roles' => $roles,
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
        $this->selected_role = 'hospital_admin';
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
            'selected_role' => 'required|string|in:hospital_admin,cashier_admin',
        ]);

        $userData = [
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'organization_id' => $this->orgId,
            'hospital_id' => $this->hospitalId,
            'role' => $this->selected_role,
            'first_name' => !empty(trim($this->first_name ?? '')) ? trim($this->first_name) : null,
            'last_name' => !empty(trim($this->last_name ?? '')) ? trim($this->last_name) : null,
            'mobile_num' => !empty(trim($this->mobile_number ?? '')) ? trim($this->mobile_number) : null,
            'gender' => !empty(trim($this->gender ?? '')) ? trim($this->gender) : null,
            'dob' => !empty(trim($this->dob ?? '')) ? trim($this->dob) : null,
        ];

        // Handle profile image upload
        if ($this->profile_image) {
            $extension = $this->profile_image->getClientOriginalExtension();
            $filename = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $this->profile_image->storeAs('users', $filename, 'public');
            $userData['profile_image'] = $filename;
        }

        $user = HIPUser::create($userData);
        $user->assignRole($this->selected_role);

        $this->resetInput();
        Flux::modal('add-admin-user')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Admin user created successfully!'
        );
    }

    public function edit($userId)
    {
        $user = HIPUser::find($userId);
        if ($user) {
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

            $role = $user->roles()->whereIn('name', ['hospital_admin', 'cashier_admin'])->first();
            $this->selected_role = $role ? $role->name : 'hospital_admin';

            Flux::modal('edit-admin-user')->show();
        }
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
            'selected_role' => 'required|string|in:hospital_admin,cashier_admin',
        ];

        // Only validate password if it's provided
        if (!empty($this->password)) {
            $rules['password'] = 'required|min:8';
            $rules['password_confirmation'] = 'required|min:8|same:password';
        }

        $this->validate($rules);

        $user = HIPUser::find($this->editingUserId);
        if ($user) {
            $user->email = $this->email;
            $user->first_name = !empty(trim($this->first_name ?? '')) ? trim($this->first_name) : null;
            $user->last_name = !empty(trim($this->last_name ?? '')) ? trim($this->last_name) : null;
            $user->mobile_num = !empty(trim($this->mobile_number ?? '')) ? trim($this->mobile_number) : null;
            $user->gender = !empty(trim($this->gender ?? '')) ? trim($this->gender) : null;
            $user->dob = !empty(trim($this->dob ?? '')) ? trim($this->dob) : null;

            if (in_array($this->selected_role, ['hospital_admin', 'cashier_admin'])) {
                $user->syncRoles([$this->selected_role]);
            }

            if (!empty($this->password)) {
                $user->password = Hash::make($this->password);
            }

            // Handle profile image upload
            if ($this->profile_image) {
                // Delete old image if exists
                if ($user->profile_image && Storage::disk('public')->exists('users/' . $user->profile_image)) {
                    Storage::disk('public')->delete('users/' . $user->profile_image);
                }

                $extension = $this->profile_image->getClientOriginalExtension();
                $filename = Str::uuid() . '_' . hash('sha256', $user->id . time()) . '.' . $extension;
                $this->profile_image->storeAs('users', $filename, 'public');
                $user->profile_image = $filename;
            } elseif ($this->old_profile_image === null && $user->profile_image) {
                // If old image was removed and no new image uploaded, delete the existing image
                if (Storage::disk('public')->exists('users/' . $user->profile_image)) {
                    Storage::disk('public')->delete('users/' . $user->profile_image);
                }
                $user->profile_image = null;
            }
            
            $user->save();

            $this->resetInput();
            Flux::modal('edit-admin-user')->close();
            $this->dispatch(
                'toast',
                type: 'success',
                message: 'Admin user updated successfully!'
            );
        }
    }

    public function delete($userId)
    {
        $this->deleteUserId = $userId;
        Flux::modal('delete-hos')->show();
    }

    public function destroy()
    {
        $user = HIPUser::find($this->deleteUserId);
        if ($user) {
            foreach (['hospital_admin', 'cashier_admin'] as $roleName) {
                if ($user->hasRole($roleName)) {
                    $user->removeRole($roleName);
                    break;
                }
            }
            $user->delete();

            $this->deleteUserId = null;
            Flux::modal('delete-hos')->close();
            $this->dispatch(
                'toast',
                type: 'success',
                message: 'Admin user deleted successfully!'
            );
        }
    }

    protected function messages()
    {
        return [
            'email.required' => 'Email is required.',
            'email.email' => 'Email is not valid.',
            'email.unique' => 'Email already exists.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.min' => 'Password confirmation must be at least 8 characters.',
            'password_confirmation.same' => 'Password confirmation must match password.',
            'first_name.max' => 'First name must not exceed 255 characters.',
            'last_name.max' => 'Last name must not exceed 255 characters.',
            'mobile_number.max' => 'Mobile number must not exceed 20 characters.',
            'gender.in' => 'Gender must be male, female, or other.',
            'dob.date' => 'Date of birth must be a valid date.',
            'profile_image.image' => 'Profile image must be an image file.',
            'profile_image.mimes' => 'Profile image must be jpeg, jpg, or png.',
            'profile_image.max' => 'Profile image must not exceed 2MB.',
            'selected_role.required' => 'Please select a role.',
            'selected_role.in' => 'Selected role must be Hospital Admin or Cashier Admin.',
        ];
    }
}
