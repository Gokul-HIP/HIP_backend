<?php

namespace App\Livewire\Cashier\Subscriptions;

use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Renew extends Component
{
    public ?int $subscriptionId = null;

    public ?HIPUser $selectedUser = null;

    public ?UserFamilySubscription $previousSubscription = null;

    public $packageId = '';

    public string $paymentMode = 'cash';

    public $amount = '';

    protected FamilyPackageService $familyPackageService;

    public function boot(FamilyPackageService $familyPackageService): void
    {
        $this->familyPackageService = $familyPackageService;
    }

    #[On('loadRenewSubscription')]
    public function loadRenew(int $subscriptionId): void
    {
        $this->reset(['packageId', 'paymentMode', 'amount']);
        $this->subscriptionId = $subscriptionId;

        $this->previousSubscription = UserFamilySubscription::with(['familyPackage', 'member'])
            ->find($subscriptionId);

        if (! $this->previousSubscription) {
            return;
        }

        $this->selectedUser = $this->previousSubscription->member;
        $this->packageId = (string) ($this->previousSubscription->family_package_id ?? '');
        $this->amount = (string) ($this->previousSubscription->familyPackage?->price ?? '');
    }

    public function updatedPackageId($value): void
    {
        if (! $value) {
            return;
        }

        $package = FamilyPackage::active()->find($value);

        if ($package) {
            $this->amount = (string) $package->price;
        }
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'packageId' => 'required|integer|exists:family_packages,id',
            'paymentMode' => 'required|in:online,cash',
            'amount' => 'required|numeric|min:0',
        ]);

        if (! $this->subscriptionId) {
            $this->dispatch('toast', type: 'error', message: 'No subscription selected for renewal.');

            return;
        }

        try {
            $this->familyPackageService->renewCashierSubscription(
                $this->subscriptionId,
                (int) $validated['packageId'],
                $validated['paymentMode'],
                (float) $validated['amount'],
                Auth::id(),
            );

            $message = $validated['paymentMode'] === 'cash'
                ? 'Subscription renewed and activated successfully.'
                : 'Renewal payment request sent. Subscription will activate after payment.';

            $this->close();
            $this->dispatch('$refresh')->to(Index::class);
            $this->dispatch('toast', type: 'success', message: $message);
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function close(): void
    {
        Flux::modal('renew-subscription')->close();
        $this->reset(['subscriptionId', 'selectedUser', 'previousSubscription', 'packageId', 'paymentMode', 'amount']);
    }

    public function render()
    {
        return view('livewire.cashier.subscriptions.renew', [
            'packages' => $this->familyPackageService->getAllPackages(),
        ]);
    }
}
