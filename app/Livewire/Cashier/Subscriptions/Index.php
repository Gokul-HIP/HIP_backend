<?php

namespace App\Livewire\Cashier\Subscriptions;

use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $paymentModeFilter = 'all';

    public string $invoiceStatusFilter = 'all';

    public string $subscriptionStatusFilter = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $selectedSubscriptionId = null;

    public bool $showDetailsModal = false;

    public bool $showRenewModal = false;

    public ?int $renewSubscriptionId = null;

    protected FamilyPackageService $familyPackageService;

    public function boot(FamilyPackageService $familyPackageService): void
    {
        $this->familyPackageService = $familyPackageService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentModeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingInvoiceStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSubscriptionStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'paymentModeFilter',
            'invoiceStatusFilter',
            'subscriptionStatusFilter',
            'dateFrom',
            'dateTo',
        ]);
        $this->resetPage();
    }

    public function viewDetails(int $id): void
    {
        $this->selectedSubscriptionId = $id;
        $this->showDetailsModal = true;
    }

    public function closeDetails(): void
    {
        $this->showDetailsModal = false;
        $this->selectedSubscriptionId = null;
    }

    public function openRenew(int $subscriptionId): void
    {
        $this->renewSubscriptionId = $subscriptionId;
        $this->showRenewModal = true;
    }

    #[On('closeRenewModal')]
    public function closeRenew(): void
    {
        $this->showRenewModal = false;
        $this->renewSubscriptionId = null;
    }

    public function markPaid(int $subscriptionId): void
    {
        try {
            $this->familyPackageService->markSubscriptionInvoicePaid($subscriptionId);
            $this->dispatch('toast', type: 'success', message: 'Subscription marked as paid and activated.');
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function cancelSubscription(int $subscriptionId): void
    {
        try {
            $subscription = UserFamilySubscription::with('invoice')->findOrFail($subscriptionId);

            if ($subscription->status === 'cancelled') {
                throw new \InvalidArgumentException('Subscription is already cancelled.');
            }

            $this->familyPackageService->cancelSubscription($subscriptionId);

            if ($subscription->invoice && $subscription->invoice->status === 'pending') {
                $subscription->invoice->update(['status' => 'cancelled']);
            }

            $this->dispatch('toast', type: 'success', message: 'Subscription cancelled.');
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $this->familyPackageService->expireStaleSubscriptions();

        $query = UserFamilySubscription::query()
            ->with(['familyPackage', 'member', 'invoice'])
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->whereHas('member', function ($member) use ($like) {
                        $member->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('mobile_num', 'like', $like);
                    })->orWhereHas('familyPackage', fn ($pkg) => $pkg->where('name', 'like', $like));
                });
            })
            ->when($this->paymentModeFilter !== 'all', fn ($q) => $q->where('payment_mode', $this->paymentModeFilter))
            ->when($this->subscriptionStatusFilter !== 'all', fn ($q) => $q->where('status', $this->subscriptionStatusFilter))
            ->when($this->invoiceStatusFilter !== 'all', function ($q) {
                $q->whereHas('invoice', fn ($invoice) => $invoice->where('status', $this->invoiceStatusFilter));
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at');

        $subscriptions = $query->paginate(10)->withPath(route('cashier.manage-subscriptions.index'));

        $subscriptions->getCollection()->transform(function (UserFamilySubscription $subscription) {
            $subscription->setAttribute(
                'covered_members_display',
                $this->familyPackageService->resolveCoveredMembersDetails($subscription)
            );
            $subscription->setAttribute('can_renew', $this->familyPackageService->subscriptionCanRenew($subscription));

            return $subscription;
        });

        $selectedSubscription = $this->selectedSubscriptionId
            ? UserFamilySubscription::with(['familyPackage', 'member', 'invoice', 'usageLogs'])->find($this->selectedSubscriptionId)
            : null;

        $coveredMembers = $selectedSubscription
            ? $this->familyPackageService->resolveCoveredMembersDetails($selectedSubscription)
            : [];

        return view('livewire.cashier.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'selectedSubscription' => $selectedSubscription,
            'coveredMembers' => $coveredMembers,
        ]);
    }
}
