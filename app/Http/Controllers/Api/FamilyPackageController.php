<?php

namespace App\Http\Controllers\Api;

use App\Enums\MembershipPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\UserReward;
use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                'benefits' => $package->benefitsForApi(),
                'terms_conditions' => $package->terms_conditions ?? [],
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

        $allowUnverifiedSelfSubscribe = (bool) config('memberships.allow_unverified_self_subscribe', false);
        $options = [];

        if ($allowUnverifiedSelfSubscribe) {
            $options = $request->validate([
                'payment_status' => 'nullable|in:paid,pending,free',
                'amount_paid' => 'nullable|numeric|min:0',
                'auto_renew' => 'nullable|boolean',
                'invoice_id' => 'nullable|integer|exists:invoices,id',
            ]);

            Log::warning('Unverified family package self-subscribe used.', [
                'user_id' => (string) $user->id,
                'package_id' => $id,
            ]);
            $validated = null;
        } else {
            $validated = $request->validate([
                'auto_renew' => 'nullable|boolean',
            ]);
        }

        try {
            if ($allowUnverifiedSelfSubscribe) {
                $subscription = $this->familyPackageService->subscribeUser(
                    (string) $user->id,
                    $id,
                    $options
                );
            } else {
                $subscription = DB::transaction(function () use ($request, $user, $id, $validated) {
                    $invoice = $this->verifiedMembershipInvoice(
                        $request->input('invoice_id'),
                        (string) $user->id,
                        $id,
                    );

                    if (! $invoice) {
                        return null;
                    }

                    return $this->familyPackageService->subscribeUser(
                        (string) $user->id,
                        $id,
                        [
                            'invoice_id' => (int) $invoice->id,
                            'payment_status' => 'paid',
                            'amount_paid' => (float) $invoice->total_amount,
                            'auto_renew' => (bool) ($validated['auto_renew'] ?? false),
                        ]
                    );
                });

                if (! $subscription) {
                    return response()->json([
                        'status' => false,
                        'message' => 'A verified paid invoice is required to activate a membership.',
                    ], 403);
                }
            }

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

    private function verifiedMembershipInvoice(mixed $invoiceId, string $hipUserId, int $packageId): ?Invoice
    {
        if (! is_scalar($invoiceId) || filter_var($invoiceId, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        $invoice = Invoice::query()->lockForUpdate()->find((int) $invoiceId);
        if (! $invoice
            || $invoice->status !== 'completed'
            || ! is_numeric($invoice->total_amount)
            || (float) $invoice->total_amount < 0
            || ! in_array('family_package', (array) $invoice->service_types, true)
            || UserFamilySubscription::query()->where('invoice_id', $invoice->id)->exists()) {
            return null;
        }

        $linkedPersons = Persons::query()
            ->where('hip_user_id', $hipUserId)
            ->get(['id', 'parent_id']);

        if ($linkedPersons->isEmpty()) {
            return null;
        }

        $familyRoots = $linkedPersons->pluck('parent_id')
            ->filter()
            ->merge($linkedPersons->pluck('id'))
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
        $familyPersonIds = Persons::query()
            ->whereIn('id', $familyRoots)
            ->orWhereIn('parent_id', $familyRoots)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->merge($familyRoots)
            ->unique()
            ->values();

        if (! $familyPersonIds->contains((string) $invoice->primary_person_id)
            && ! $familyPersonIds->contains((string) $invoice->person_id)) {
            return null;
        }

        $details = (array) $invoice->invoice_details;
        $packageRows = $details['family_package'] ?? [];
        if (is_array($packageRows) && array_key_exists('package_id', $packageRows)) {
            $packageRows = [$packageRows];
        }

        $invoiceMatchesPackage = is_array($packageRows)
            && collect($packageRows)->contains(fn ($row) => is_array($row)
                && (int) ($row['package_id'] ?? 0) === $packageId);

        return $invoiceMatchesPackage ? $invoice : null;
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

        $benefits = [];
        if ($package) {
            $discounts = ($package->relationLoaded('paymentDiscounts')
                ? $package->paymentDiscounts
                : $package->paymentDiscounts()->get()
            )->keyBy('payment_type');

            foreach (MembershipPaymentType::cases() as $type) {
                $row = $discounts->get($type->value);
                if (! $row) {
                    continue;
                }

                $pct = (float) $row->percentage;
                if ($pct <= 0) {
                    continue;
                }

                $percentage = ($pct == floor($pct)) ? (int) $pct : $pct;
                $title = $type->label();

                $benefits[] = [
                    'payment_type' => $type->value,
                    'title' => $title,
                    'percentage' => $percentage,
                    'label' => "{$title}: {$percentage}% off",
                ];
            }
        }

        return [
            'subscription_id' => $subscription->id,
            'package_name' => $package?->name,
            'status' => $subscription->status,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'renewal_date' => $end->toDateString(),
            'validity' => $start->format("j M y").' - '.$end->format("j M y"),
            'covered_members' => $this->familyPackageService->resolveCoveredMembersDetails($subscription),
            'covered_members_limit' => (int) ($package?->max_members ?? 0),
            'branch_access' => $branchNames,
            'benefits' => $benefits,
            'terms_conditions' => $package?->terms_conditions ?? [],
            'is_reward_applied' => (bool) ($reward?->is_applied ?? false),
        ];
    }
}
