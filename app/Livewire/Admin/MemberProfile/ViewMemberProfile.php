<?php

namespace App\Livewire\Admin\MemberProfile;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\Transactions;
use Illuminate\Support\Collection;

class ViewMemberProfile extends Component
{
    use WithPagination;

    public int $memberId;
    public $member;
    public Collection $dependents;
    public array $personIds = [];
    public $tab = 'profile';
    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->dependents = collect();
        $this->personIds = [];
    }

    #[On('view-member-profile')]
    public function viewMemberProfile($id)
    {
        $this->memberId = (int) $id;
        $this->member = HIPUser::query()->find($this->memberId);

        $memberPersons = Persons::query()
            ->where('hip_user_id', $this->memberId)
            ->get(['id', 'parent_id']);

        $personIds = $memberPersons->pluck('id')->filter()->map(fn ($pid) => (int) $pid)->all();
        $parentIds = $memberPersons->pluck('parent_id')->filter(fn ($parentId) => $parentId !== null && $parentId !== '')->all();
        $this->personIds = $personIds;

        $this->dependents = Persons::query()
            ->when(
                !empty($parentIds),
                fn ($query) => $query->whereIn('parent_id', $parentIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->where('is_primary', false)
            ->latest('id')
            ->get();

        $this->resetPage(pageName: 'transactionsPage');

        $this->tab = 'profile';
        Flux::modal('view-member-profile')->show();
    }

    public function closeModal()
    {
        Flux::modal('view-member-profile')->close();
    }

    public function render()
    {
        $transactions = Transactions::query()
            ->with(['invoice.primaryPerson', 'invoice.person'])
            ->when(
                !empty($this->personIds),
                function ($query) {
                    $query->whereHas('invoice', function ($invoiceQuery) {
                        $invoiceQuery->where(function ($innerQuery) {
                            $innerQuery->whereIn('primary_person_id', $this->personIds)
                                ->orWhereIn('person_id', $this->personIds);
                        });
                    });
                },
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->latest('id')
            ->paginate(10, ['*'], 'transactionsPage');

        return view('livewire.admin.member-profile.view-member-profile', [
            'transactions' => $transactions,
        ]);
    }
}
