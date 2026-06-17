<?php

namespace App\Livewire\Cashier\Subscriptions;

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
    }

    public function selectUser(string $userId): void
    {
        $this->selectedUser = HIPUser::find($userId);
        $this->searchResults = collect();
        $this->phoneSearch = $this->selectedUser?->mobile_num ?? $this->phoneSearch;
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'packageId' => 'required|integer|exists:family_packages,id',
            'paymentMode' => 'required|in:online,cash',
            'amount' => 'required|numeric|min:0',
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
            );

            $message = $validated['paymentMode'] === 'cash'
                ? 'Subscription created and activated successfully.'
                : 'Payment request sent. Subscription will activate after payment.';

            $this->dispatch('toast', type: 'success', message: $message);
            $this->redirectRoute('cashier.manage-subscriptions.index', navigate: true);
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.cashier.subscriptions.create', [
            'packages' => $this->familyPackageService->getAllPackages(),
        ]);
    }
}
