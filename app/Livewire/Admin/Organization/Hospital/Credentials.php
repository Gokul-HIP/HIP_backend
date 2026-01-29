<?php

namespace App\Livewire\Admin\Organization\Hospital;

use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use Flux\Flux;
use App\Models\HIPUser;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
class Credentials extends Component
{
    public $email;
    public $password;
    public $password_confirmation;
    public $orgId;
    public $hospitalId;
    public function mount($orgId)
    {
        $this->orgId = $orgId;
    }

    #[On('createUser')]
    public function hospitalId($hospitalId)
    {
        $this->hospitalId = $hospitalId;
    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.credentials');
    }

    public function closeModal()
    {
        Flux::modal('create-user')->close();
        $this->resetInput();
    }

    public function resetInput()
    {
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function createUser()
    {
        $this->validate();

        // dd($this->email, $this->password, $this->orgId, $this->hospitalId);

        $user = HIPUser::create([
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'organization_id' => $this->orgId,
            'hospital_id' => $this->hospitalId,
            'role' => 'hospital_admin',
        ]);
        $user->assignRole('hospital_admin');

        $this->resetInput();
        Flux::modal('create-user')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'User created successfully!'
        );
    }

    protected function rules()
    {
        return [
            'email' => 'required|email|unique:healthinpocket_users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8|same:password',
        ];
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
        ];
    }
}
