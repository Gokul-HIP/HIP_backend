<?php

namespace App\Livewire\HospitalAdmin\Users;

use App\Models\HIPUser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MemberProfile extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $organizationId = (int) (Auth::user()->organization_id ?? 0);

        $membersQuery = HIPUser::query()
            ->select(['id', 'hip_id', 'first_name', 'last_name', 'email', 'mobile_num', 'profile_image', 'organization_id'])
            ->where('organization_id', $organizationId);

        $search = trim($this->search);
        if ($search !== '') {
            $membersQuery->where(function (Builder $q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%'])
                    ->orWhere('mobile_num', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('hip_id', 'like', '%' . $search . '%');
            });
        }

        $members = $membersQuery
            ->latest('created_at')
            ->paginate(10, ['*'], 'membersPage');

        return view('livewire.hospital-admin.users.member-profile', [
            'members' => $members,
        ]);
    }
}

