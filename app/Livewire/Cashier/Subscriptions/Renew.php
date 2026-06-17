<?php

namespace App\Livewire\Cashier\Subscriptions;

use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
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

    /** @var array<int, string> */
    public array $selectedMemberIds = [];

    /** @var array<int, array{id: string, name: string, relationship: string}> */
    public array $familyMembers = [];

    protected FamilyPackageService $familyPackageService;

    public function boot(FamilyPackageService $familyPackageService): void
    {
        $this->familyPackageService = $familyPackageService;
    }

    public function mount(?int $subscriptionId = null): void
    {
        if ($subscriptionId) {
            $this->loadRenew($subscriptionId);
        }
    }

    #[On('loadRenewSubscription')]
    public function loadRenew(int $subscriptionId): void
    {
        $this->reset(['packageId', 'paymentMode', 'amount', 'selectedMemberIds', 'familyMembers']);
        $this->subscriptionId = $subscriptionId;

        $this->previousSubscription = UserFamilySubscription::with(['familyPackage', 'member'])
            ->find($subscriptionId);

        if (! $this->previousSubscription) {
            return;
        }

        $this->selectedUser = $this->previousSubscription->member;
        $this->packageId = (string) ($this->previousSubscription->family_package_id ?? '');
        $this->amount = (string) ($this->previousSubscription->familyPackage?->price ?? '');

        if ($this->selectedUser) {
            $this->familyMembers = $this->familyPackageService->getFamilyMembersForUser($this->selectedUser->id);
            $this->selectedMemberIds = array_map('strval', $this->previousSubscription->covered_member_ids ?? []);
            $this->applyDefaultMemberSelection();
        }
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

        $this->applyDefaultMemberSelection();
    }

    public function updatedSelectedMemberIds(): void
    {
        $allowed = collect($this->familyMembers)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $maxMembers = $this->maxMembers;

        $normalized = collect($this->selectedMemberIds)
            ->map(fn ($id) => (string) $id)
            ->filter(fn ($id) => in_array($id, $allowed, true))
            ->unique()
            ->values()
            ->all();

        if (count($normalized) > $maxMembers) {
            $normalized = array_slice($normalized, 0, $maxMembers);
            $this->dispatch('toast', type: 'warning', message: "You can select at most {$maxMembers} member(s) for this package.");
        }

        $this->selectedMemberIds = $normalized;
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'packageId' => 'required|integer|exists:family_packages,id',
            'paymentMode' => 'required|in:online,cash',
            'amount' => 'required|numeric|min:0',
            'selectedMemberIds' => 'required|array|min:1',
            'selectedMemberIds.*' => 'uuid',
        ], [
            'selectedMemberIds.required' => 'Select at least one covered member.',
            'selectedMemberIds.min' => 'Select at least one covered member.',
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
                $validated['selectedMemberIds'],
            );

            $this->close();
            $this->dispatch('$refresh')->to(Index::class);
            $this->dispatch('toast', type: 'success', message: 'Subscription renewed and activated successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function getMaxMembersProperty(): int
    {
        if (! $this->packageId) {
            return 1;
        }

        $package = FamilyPackage::active()->find($this->packageId);

        return max(1, (int) ($package?->max_members ?? 1));
    }

    private function applyDefaultMemberSelection(): void
    {
        if (! $this->selectedUser || ! $this->packageId || $this->familyMembers === []) {
            return;
        }

        $max = $this->maxMembers;

        if ($this->selectedMemberIds === [] && isset($this->familyMembers[0]['id'])) {
            $this->selectedMemberIds = [(string) $this->familyMembers[0]['id']];
        }

        $this->selectedMemberIds = array_map('strval', array_slice($this->selectedMemberIds, 0, $max));
    }

    public function close(): void
    {
        $this->dispatch('closeRenewModal')->to(Index::class);
        $this->reset([
            'subscriptionId', 'selectedUser', 'previousSubscription',
            'packageId', 'paymentMode', 'amount', 'selectedMemberIds', 'familyMembers',
        ]);
    }

    public function render()
    {
        return view('livewire.cashier.subscriptions.renew', [
            'packages' => $this->familyPackageService->getAllPackages(),
        ]);
    }
}
