<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\UserReward;
use App\Services\FamilyPackageService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FamilyPackageController extends Controller
{
    public function __construct(
        protected FamilyPackageService $familyPackageService
    ) {}

    public function index()
    {
        $packages = $this->familyPackageService->getAllPackages();

        return response()->json([
            'status' => 200,
            'message' => 'Family packages fetched successfully',
            'data' => $packages->map(fn ($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'price' => (float) $package->price,
                'duration_days' => (int) $package->duration_days,
                'max_members' => (int) $package->max_members,
                'max_consultations' => (int) $package->max_consultations,
                'max_lab_tests' => (int) $package->max_lab_tests,
                'max_hip_coins' => (int) $package->max_hip_coins,
                'branch_ids' => $package->branch_ids ?? [],
                'benefits' => $package->benefits ?? [],
            ])->values(),
        ]);
    }

    public function subscribe(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $validated = $request->validate([
            'payment_status' => 'nullable|in:paid,pending,free',
            'amount_paid' => 'nullable|numeric|min:0',
            'auto_renew' => 'nullable|boolean',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
        ]);

        try {
            $subscription = $this->familyPackageService->subscribeUser(
                (string) $user->id,
                $id,
                $validated
            );

            return response()->json([
                'status' => 200,
                'message' => 'Subscription created successfully',
                'data' => $this->formatSubscription($subscription),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 422,
                'message' => $e->getMessage(),
                'data' => [],
            ], 422);
        }
    }

    public function activeSubscription(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $subscription = $this->familyPackageService->getUserActiveSubscription((string) $user->id);

        if (! $subscription) {
            return response()->json([
                'status' => 200,
                'message' => 'No active subscription found',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Active subscription fetched successfully',
            'data' => $this->formatSubscriptionDetails($subscription),
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $history = $this->familyPackageService->getUserSubscriptionHistory((string) $user->id);

        return response()->json([
            'status' => 200,
            'message' => 'Subscription history fetched successfully',
            'data' => $history->map(fn ($subscription) => $this->formatSubscription($subscription))->values(),
        ]);
    }

    public function cancel(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $subscription = $this->familyPackageService->getUserActiveSubscription((string) $user->id);

        if (! $subscription) {
            return response()->json([
                'status' => 404,
                'message' => 'No active subscription found',
                'data' => [],
            ], 404);
        }

        $this->familyPackageService->cancelSubscription($subscription->id);

        return response()->json([
            'status' => 200,
            'message' => 'Subscription cancelled successfully',
            'data' => [],
        ]);
    }

    private function formatSubscription($subscription): array
    {
        $package = $subscription->familyPackage;

        return [
            'subscription_id' => $subscription->id,
            'package_id' => $package?->id,
            'package_name' => $package?->name,
            'status' => $subscription->status,
            'payment_status' => $subscription->payment_status,
            'start_date' => optional($subscription->start_date)->toDateString(),
            'end_date' => optional($subscription->end_date)->toDateString(),
            'amount_paid' => (float) $subscription->amount_paid,
            'auto_renew' => (bool) $subscription->auto_renew,
        ];
    }

    private function formatSubscriptionDetails($subscription): array
    {
        $package = $subscription->familyPackage;
        $usage = $this->familyPackageService->getUsageSummary($subscription->id);
        $reward = $subscription->userRewards
            ->where('entity_type', UserReward::ENTITY_TYPE_FAMILY_PACKAGE)
            ->first();

        $branchNames = [];
        if (! empty($package?->branch_ids)) {
            $branchNames = Hospital::query()
                ->whereIn('id', $package->branch_ids)
                ->pluck('name')
                ->values()
                ->all();
        }

        $start = Carbon::parse($subscription->start_date);
        $end = Carbon::parse($subscription->end_date);

        return [
            'subscription_id' => $subscription->id,
            'package_name' => $package?->name,
            'status' => $subscription->status,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'renewal_date' => $end->toDateString(),
            'validity' => $start->format("j M 'y").' - '.$end->format("j M 'y"),
            'covered_members' => (int) ($package?->max_members ?? 0),
            'branch_access' => $branchNames,
            'usage_summary' => [
                'consultation_used' => $usage['consultation_used'],
                'consultation_limit' => $usage['consultation_limit'],
                'lab_test_used' => $usage['lab_test_used'],
                'lab_test_limit' => $usage['lab_test_limit'],
                'checkup_used' => $usage['checkup_used'],
                'hip_coins_balance' => (int) ($package?->max_hip_coins ?? 0),
            ],
            'benefits' => $package?->benefits ?? [],
            'is_reward_applied' => (bool) ($reward?->is_applied ?? false),
        ];
    }
}
