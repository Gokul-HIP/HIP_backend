<?php

namespace App\Livewire\HospitalAdmin\Settings;

use App\Models\DiagnosticPackage;
use App\Services\RewardTierService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RewardTiers extends Component
{
    public $tiers;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $description = null;

    public int $min_coins = 0;

    public int $sort_order = 1;

    public bool $is_active = true;

    public ?float $total_discount_percentage = null;

    public int $free_checkup_count = 0;

    public int $earned_coins_per_booking = 0;

    public array $packageDiscounts = [];

    public $allPackages;

    public ?int $confirmDeleteId = null;

    public function mount(RewardTierService $rewardTierService): void
    {
        $this->loadTiers($rewardTierService);
        $this->allPackages = $this->organizationDiagnosticPackages();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        Flux::modal('reward-tier-modal')->show();
    }

    public function openEdit(int $id, RewardTierService $rewardTierService): void
    {
        $tier = $rewardTierService->getAllTiers()->firstWhere('id', $id);

        if (! $tier) {
            return;
        }

        $this->resetValidation();
        $this->editingId = $tier->id;
        $this->name = $tier->name;
        $this->description = $tier->description;
        $this->min_coins = (int) $tier->min_coins;
        $this->sort_order = (int) $tier->sort_order;
        $this->is_active = (bool) $tier->is_active;
        $this->total_discount_percentage = $tier->config?->total_discount_percentage;
        $this->free_checkup_count = (int) ($tier->config?->free_checkup_count ?? 0);
        $this->earned_coins_per_booking = (int) ($tier->config?->earned_coins_per_booking ?? 0);
        $this->packageDiscounts = $tier->packageDiscounts->map(fn ($discount) => [
            'diagnostic_package_id' => (string) $discount->diagnostic_package_id,
            'promotion_type' => $discount->promotion_type,
            'discount_type' => $discount->discount_type,
            'discount_value' => $discount->discount_value,
        ])->values()->all();

        $this->showModal = true;
        Flux::modal('reward-tier-modal')->show();
    }

    public function save(RewardTierService $rewardTierService): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:100',
            'min_coins' => 'required|integer|min:0',
            'sort_order' => 'required|integer|min:1',
            'total_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'free_checkup_count' => 'nullable|integer|min:0',
            'earned_coins_per_booking' => 'required|integer|min:0',
            'packageDiscounts.*.diagnostic_package_id' => 'required|exists:diagnostic_packages,id',
            'packageDiscounts.*.promotion_type' => 'required|in:discount,free',
            'packageDiscounts.*.discount_type' => 'required_if:packageDiscounts.*.promotion_type,discount|nullable|in:percentage,flat',
            'packageDiscounts.*.discount_value' => 'required_if:packageDiscounts.*.promotion_type,discount|nullable|numeric|min:0',
        ]);

        $tierData = [
            'name' => $validated['name'],
            'description' => $this->description,
            'min_coins' => $validated['min_coins'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $this->is_active,
        ];

        $configData = [
            'total_discount_percentage' => $validated['total_discount_percentage'] ?? null,
            'free_checkup_count' => (int) ($validated['free_checkup_count'] ?? 0),
            'earned_coins_per_booking' => $validated['earned_coins_per_booking'],
        ];

        $packageRows = collect($this->packageDiscounts)->map(function (array $row) {
            $promotionType = $row['promotion_type'] ?? 'discount';

            return [
                'diagnostic_package_id' => (int) $row['diagnostic_package_id'],
                'promotion_type' => $promotionType,
                'discount_type' => $promotionType === 'discount' ? ($row['discount_type'] ?? null) : null,
                'discount_value' => $promotionType === 'discount' ? ($row['discount_value'] ?? null) : null,
            ];
        })->all();

        if ($this->editingId) {
            $rewardTierService->updateTier($this->editingId, $tierData, $configData, $packageRows);
            $message = 'Reward tier updated successfully.';
        } else {
            $rewardTierService->createTier($tierData, $configData, $packageRows);
            $message = 'Reward tier created successfully.';
        }

        $this->loadTiers($rewardTierService);
        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
        Flux::modal('delete-reward-tier')->show();
    }

    public function deleteTier(RewardTierService $rewardTierService): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $rewardTierService->deleteTier($this->confirmDeleteId);
        $this->loadTiers($rewardTierService);
        Flux::modal('delete-reward-tier')->close();
        $this->confirmDeleteId = null;
        $this->dispatch('toast', type: 'success', message: 'Reward tier deleted successfully.');
    }

    public function addPackageRow(): void
    {
        $this->packageDiscounts[] = [
            'diagnostic_package_id' => '',
            'promotion_type' => 'discount',
            'discount_type' => 'percentage',
            'discount_value' => null,
        ];
    }

    public function removePackageRow(int $index): void
    {
        unset($this->packageDiscounts[$index]);
        $this->packageDiscounts = array_values($this->packageDiscounts);
    }

    public function closeModal(): void
    {
        Flux::modal('reward-tier-modal')->close();
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDelete(): void
    {
        Flux::modal('delete-reward-tier')->close();
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        return view('livewire.hospital-admin.settings.reward-tiers')
            ->extends('hospital-admin.layout.hospitaladmin')
            ->section('content');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'name',
            'description',
            'min_coins',
            'sort_order',
            'is_active',
            'total_discount_percentage',
            'free_checkup_count',
            'earned_coins_per_booking',
            'packageDiscounts',
        ]);
        $this->min_coins = 0;
        $this->sort_order = 1;
        $this->is_active = true;
        $this->free_checkup_count = 0;
        $this->earned_coins_per_booking = 0;
        $this->packageDiscounts = [];
        $this->resetValidation();
    }

    private function loadTiers(RewardTierService $rewardTierService): void
    {
        $this->tiers = $rewardTierService->getAllTiers();
    }

    private function organizationDiagnosticPackages()
    {
        $organizationId = Auth::guard('filament')->user()?->organization_id;

        return DiagnosticPackage::query()
            ->active()
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->orderBy('name')
            ->get();
    }
}
