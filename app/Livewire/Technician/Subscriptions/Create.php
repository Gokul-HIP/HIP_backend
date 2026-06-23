<?php

namespace App\Livewire\Technician\Subscriptions;

use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Services\FamilyPackageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public string $phoneSearch = '';

    /** @var Collection<int, HIPUser> */
    public Collection $searchResults;

    public ?HIPUser $selectedUser = null;

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

    public function mount(): void
    {
        $this->searchResults = collect();
    }

    public function updatedPhoneSearch(): void
    {
        $this->selectedUser = null;
        $this->selectedMemberIds = [];
        $this->familyMembers = [];
        $term = trim($this->phoneSearch);

        if (strlen($term) < 3) {
            $this->searchResults = collect();

            return;
        }

        $this->searchResults = HIPUser::query()
            ->where('mobile_num', 'like', '%'.$term.'%')
            ->orWhere('first_name', 'like', '%'.$term.'%')
            ->orWhere('last_name', 'like', '%'.$term.'%')
            ->limit(10)
            ->get();
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

    public function selectUser(string $userId): void
    {
        $this->selectedUser = HIPUser::find($userId);
        $this->searchResults = collect();
        $this->phoneSearch = $this->selectedUser?->mobile_num ?? $this->phoneSearch;
        $this->familyMembers = $this->selectedUser
            ? $this->familyPackageService->getFamilyMembersForUser($this->selectedUser->id)
            : [];
        $this->selectedMemberIds = [];
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

        if (! $this->selectedUser) {
            $this->addError('selectedUser', 'Please select a user.');

            return;
        }

        try {
            $this->familyPackageService->createCashierSubscription(
                $this->selectedUser->id,
                (int) $validated['packageId'],
                $validated['paymentMode'],
                (float) $validated['amount'],
                Auth::id(),
                $validated['selectedMemberIds'],
            );

            $message = 'Subscription created and activated successfully.';

            $this->dispatch('toast', type: 'success', message: $message);
            $this->redirectRoute('technician.manage-subscriptions.index', navigate: true);
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

    public function render()
    {
        return view('livewire.technician.subscriptions.create', [
            'packages' => $this->familyPackageService->getAllPackages(),
        ]);
    }
}
