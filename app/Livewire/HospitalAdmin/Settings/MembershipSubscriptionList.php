<?php

namespace App\Livewire\HospitalAdmin\Settings;

use App\Models\Hospital;
use App\Models\UserFamilySubscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MembershipSubscriptionList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $statusFilter = 'all';

    public $search = '';

    public ?int $selectedSubscriptionId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function viewDetails(int $id): void
    {
        $this->selectedSubscriptionId = $id;
    }

    public function closeDetails(): void
    {
        $this->selectedSubscriptionId = null;
    }

    public function render()
    {
        $organizationHospitalIds = $this->organizationHospitalIds();

        $query = UserFamilySubscription::query()
            ->with(['familyPackage', 'member'])
            ->withCount([
                'usageLogs as consultations_used' => fn ($q) => $q->where('usage_type', 'consultation'),
                'usageLogs as lab_tests_used' => fn ($q) => $q->where('usage_type', 'lab_test'),
            ])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($organizationHospitalIds !== [], function ($q) use ($organizationHospitalIds) {
                $q->whereHas('familyPackage', function ($packageQuery) use ($organizationHospitalIds) {
                    $packageQuery->where(function ($inner) use ($organizationHospitalIds) {
                        $inner->whereNull('branch_ids')
                            ->orWhereJsonLength('branch_ids', 0);

                        foreach ($organizationHospitalIds as $hospitalId) {
                            $inner->orWhereJsonContains('branch_ids', $hospitalId);
                        }
                    });
                });
            })
            ->when($this->search, function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->whereHas('member', function ($member) use ($like) {
                        $member->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('mobile_num', 'like', $like);
                    })->orWhereHas('familyPackage', fn ($pkg) => $pkg->where('name', 'like', $like));
                });
            })
            ->orderByDesc('created_at');

        $subscriptions = $query->paginate(10);

        $selectedSubscription = $this->selectedSubscriptionId
            ? UserFamilySubscription::with(['familyPackage', 'member', 'usageLogs'])->find($this->selectedSubscriptionId)
            : null;

        $usageLogs = $selectedSubscription
            ? $selectedSubscription->usageLogs()->orderByDesc('used_at')->get()
            : collect();

        return view('livewire.hospital-admin.settings.membership-subscription-list', [
            'subscriptions' => $subscriptions,
            'selectedSubscription' => $selectedSubscription,
            'usageLogs' => $usageLogs,
        ]);
    }

    /**
     * @return list<int>
     */
    private function organizationHospitalIds(): array
    {
        $organizationId = Auth::guard('filament')->user()?->organization_id;

        if (! $organizationId) {
            return [];
        }

        return Hospital::query()
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
