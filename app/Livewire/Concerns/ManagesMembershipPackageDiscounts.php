<?php

namespace App\Livewire\Concerns;

use App\Enums\MembershipPaymentType;
use App\Models\FamilyPackage;
use App\Models\MembershipPackageDiscount;

trait ManagesMembershipPackageDiscounts
{
    /** @var array<string, float|int|string> */
    public array $payment_discounts = [];

    public function initializeManagesMembershipPackageDiscounts(): void
    {
        if ($this->payment_discounts === []) {
            $this->payment_discounts = MembershipPaymentType::blankPercentages();
        }
    }

    /**
     * @return array<string, list<string>>
     */
    protected function paymentDiscountValidationRules(): array
    {
        $rules = [];

        foreach (MembershipPaymentType::values() as $type) {
            $rules['payment_discounts.'.$type] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        return $rules;
    }

    protected function loadPaymentDiscounts(FamilyPackage $package): void
    {
        $saved = $package->paymentDiscounts()->pluck('percentage', 'payment_type');
        $this->payment_discounts = [];

        foreach (MembershipPaymentType::values() as $type) {
            $this->payment_discounts[$type] = $saved->has($type) ? (float) $saved[$type] : 0;
        }
    }

    protected function savePaymentDiscounts(FamilyPackage $package, array $percentages): void
    {
        foreach (MembershipPaymentType::values() as $type) {
            MembershipPackageDiscount::query()->updateOrCreate(
                [
                    'family_package_id' => $package->id,
                    'payment_type' => $type,
                ],
                [
                    'percentage' => round((float) ($percentages[$type] ?? 0), 2),
                ]
            );
        }
    }

    protected function resetPaymentDiscounts(): void
    {
        $this->payment_discounts = MembershipPaymentType::blankPercentages();
    }
}
