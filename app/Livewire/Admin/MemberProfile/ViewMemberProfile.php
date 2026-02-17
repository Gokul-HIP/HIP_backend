<?php

namespace App\Livewire\Admin\MemberProfile;

use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\HIPUser;

class ViewMemberProfile extends Component
{
    public int $memberId;
    public $member;
    public $tab = 'profile';
    #[On('view-member-profile')]
    public function viewMemberProfile($id)
    {
        $this->memberId = $id;
        $this->member = HIPUser::find($id);
        $this->tab = 'profile';
        Flux::modal('view-member-profile')->show();
    }

    public function closeModal()
    {
        Flux::modal('view-member-profile')->close();
    }

    public function render()
    {
        return view('livewire.admin.member-profile.view-member-profile');
    }
}
