<?php

namespace App\Livewire\Admin\MemberProfile;

use Livewire\Component;
use App\Models\HIPUser;
use Livewire\WithPagination;

class MemberIndex extends Component
{
    use WithPagination;
    public string $search = '';
    public string $gender = 'all';
    public string $profileUpdate = 'all';
    protected $paginationTheme = 'tailwind';

     public function updatingSearch()
     {
         $this->resetPage();
     }
 
     public function updatingGender()
     {
         $this->resetPage();
     }
 
     public function updatingProfileUpdate()
     {
         $this->resetPage();
     }

    public function render()
    {
        $members = HIPUser::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_num', 'like', '%' . $this->search . '%')
                        ->orWhere('gender', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->gender !== 'all', function ($q) {
                $q->where('gender', $this->gender);
            })
            ->when($this->profileUpdate !== 'all', function ($q) {
                $q->where('profile_update',$this->profileUpdate === 'updated' ? 1 : 0
                );
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        // Get available genders from members table
        $availableGenders = HIPUser::distinct()->whereNotNull('gender')->pluck('gender')->filter()->sort()->values();

        return view('livewire.admin.member-profile.member-index', [
            'members' => $members,
            'availableGenders' => $availableGenders
        ]);
        
    }

    public function manageDependents($id)
    {
        $this->dispatch('view-member-profile', $id);
    }

}
