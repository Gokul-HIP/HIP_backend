<?php

namespace App\Http\Controllers\Hms;

use App\Enums\MembershipPaymentType;
use App\Http\Controllers\Controller;
use App\Models\FamilyPackage;
use App\Services\Hms\HmsResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipPlansController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->attributes->set('hms_event_type', 'membership.plans.read');

        $integration = $request->attributes->get('hms_integration');
        $hospitalIds = $integration instanceof \App\Models\HmsIntegration
            ? array_values(array_filter(array_map('intval', $integration->hospital_ids ?? [])))
            : [];

        $plans = FamilyPackage::active()
            ->with('paymentDiscounts')
            ->get()
            ->filter(function (FamilyPackage $package) use ($hospitalIds) {
                if (empty($hospitalIds) || empty($package->branch_ids)) {
                    return true;
                }

                $branchIds = array_map('intval', $package->branch_ids);

                return array_intersect($branchIds, $hospitalIds) !== [];
            })
            ->map(function (FamilyPackage $package) {
                $saved = $package->paymentDiscounts->keyBy('payment_type');
                $discounts = [];

                foreach (MembershipPaymentType::values() as $type) {
                    if (! $saved->has($type)) {
                        continue;
                    }

                    $discounts[$type] = [
                        'percentage' => (float) $saved[$type]->percentage,
                    ];
                }

                return [
                    'id' => (int) $package->id,
                    'name' => (string) $package->name,
                    'price' => (float) $package->price,
                    'duration_days' => (int) $package->duration_days,
                    'max_members' => (int) $package->max_members,
                    'discounts' => $discounts,
                ];
            })
            ->values()
            ->all();

        return HmsResponse::ok($request, [
            'status' => true,
            'plans' => $plans,
        ]);
    }
}
