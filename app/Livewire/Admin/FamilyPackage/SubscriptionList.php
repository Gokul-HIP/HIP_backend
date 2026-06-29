<?php

namespace App\Livewire\Admin\FamilyPackage;

use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $statusFilter = 'all';

    public $search = '';

    public ?int $selectedSubscriptionId = null;

    protected FamilyPackageService $familyPackageService;

    public function boot(FamilyPackageService $familyPackageService): void
    {
        $this->familyPackageService = $familyPackageService;
    }

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
        $this->familyPackageService->expireStaleSubscriptions();

        $query = UserFamilySubscription::query()
            ->with(['familyPackage', 'member'])
            ->withCount([
                'usageLogs as consultations_used' => fn ($q) => $q->where('usage_type', 'consultation'),
                'usageLogs as lab_tests_used' => fn ($q) => $q->where('usage_type', 'lab_test'),
            ])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
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

        return view('livewire.admin.family-package.subscription-list', [
            'subscriptions' => $subscriptions,
            'selectedSubscription' => $selectedSubscription,
            'usageLogs' => $usageLogs,
        ]);
    }
}
