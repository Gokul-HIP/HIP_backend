<?php

namespace App\Services;

use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\SubscriptionUsageLog;
use App\Models\Transactions;
use App\Models\UserFamilySubscription;
use App\Models\UserReward;
use App\Services\Api\PaymentApiService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FamilyPackageService
{
    public function getAllPackages(): Collection
    {
        return FamilyPackage::active()->get();
    }

    public function createPackage(array $data): FamilyPackage
    {
        return FamilyPackage::create($data);
    }

    public function updatePackage(int $id, array $data): FamilyPackage
    {
        $package = FamilyPackage::findOrFail($id);
        $package->update($data);

        return $package->fresh();
    }

    public function deletePackage(int $id): void
    {
        FamilyPackage::findOrFail($id)->delete();
    }

    public function subscribeUser(string $hipUserId, int $packageId, array $options = []): UserFamilySubscription
    {
        $package = FamilyPackage::active()->find($packageId);

        if (! $package) {
            throw new InvalidArgumentException('Family package not found or inactive.');
        }

        if ($this->userHasBlockingSubscription($hipUserId)) {
            throw new InvalidArgumentException('User already has an active or pending family subscription.');
        }

        return DB::transaction(function () use ($hipUserId, $package, $options) {
            $startDate = Carbon::today();
            $endDate = $startDate->copy()->addDays((int) $package->duration_days);

            $subscription = UserFamilySubscription::create([
                'hip_user_id' => $hipUserId,
                'family_package_id' => $package->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'payment_status' => $options['payment_status'] ?? 'paid',
                'invoice_id' => $options['invoice_id'] ?? null,
                'amount_paid' => $options['amount_paid'] ?? $package->price,
                'auto_renew' => (bool) ($options['auto_renew'] ?? false),
            ]);

            UserReward::create([
                'entity_id' => $subscription->id,
                'entity_type' => UserReward::ENTITY_TYPE_FAMILY_PACKAGE,
                'hip_user_id' => $hipUserId,
                'is_applied' => false,
            ]);

            return $subscription->load(['familyPackage', 'userRewards', 'usageLogs']);
        });
    }

    public function getUserActiveSubscription(string $hipUserId): ?UserFamilySubscription
    {
        return UserFamilySubscription::query()
            ->where('hip_user_id', $hipUserId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->with(['familyPackage', 'usageLogs', 'userRewards'])
            ->latest()
            ->first();
    }

    public function logUsage(
        int $subscriptionId,
        string $usageType,
        ?int $bookingId = null,
        ?string $bookingType = null,
        ?string $notes = null
    ): SubscriptionUsageLog {
        $subscription = UserFamilySubscription::with('familyPackage')->findOrFail($subscriptionId);

        if (! $subscription->isActive()) {
            throw new InvalidArgumentException('Subscription is not active.');
        }

        $package = $subscription->familyPackage;

        if ($usageType === 'consultation' && (int) $package->max_consultations > 0) {
            $used = $subscription->usageLogs()->where('usage_type', 'consultation')->count();
            if ($used >= (int) $package->max_consultations) {
                throw new InvalidArgumentException('Consultation limit reached for your package.');
            }
        }

        if ($usageType === 'lab_test' && (int) $package->max_lab_tests > 0) {
            $used = $subscription->usageLogs()->where('usage_type', 'lab_test')->count();
            if ($used >= (int) $package->max_lab_tests) {
                throw new InvalidArgumentException('Lab test limit reached for your package.');
            }
        }

        return SubscriptionUsageLog::create([
            'user_family_subscription_id' => $subscription->id,
            'hip_user_id' => $subscription->hip_user_id,
            'usage_type' => $usageType,
            'booking_id' => $bookingId,
            'booking_type' => $bookingType,
            'used_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function tryLogBookingUsage(
        string $hipUserId,
        string $usageType,
        int $bookingId,
        string $bookingType
    ): void {
        $subscription = $this->getUserActiveSubscription($hipUserId);

        if (! $subscription) {
            return;
        }

        $this->logUsage($subscription->id, $usageType, $bookingId, $bookingType);
    }

    public function getUsageSummary(int $subscriptionId): array
    {
        $subscription = UserFamilySubscription::with('familyPackage')->findOrFail($subscriptionId);
        $package = $subscription->familyPackage;

        return [
            'consultation_used' => $subscription->usageLogs()->where('usage_type', 'consultation')->count(),
            'consultation_limit' => (int) ($package->max_consultations ?? 0),
            'lab_test_used' => $subscription->usageLogs()->where('usage_type', 'lab_test')->count(),
            'lab_test_limit' => (int) ($package->max_lab_tests ?? 0),
            'checkup_used' => $subscription->usageLogs()->where('usage_type', 'checkup')->count(),
        ];
    }

    public function getUserSubscriptionHistory(string $hipUserId): Collection
    {
        return UserFamilySubscription::query()
            ->where('hip_user_id', $hipUserId)
            ->with('familyPackage')
            ->orderByDesc('created_at')
            ->get();
    }

    public function cancelSubscription(int $subscriptionId): void
    {
        $subscription = UserFamilySubscription::findOrFail($subscriptionId);
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function expireStaleSubscriptions(): int
    {
        return UserFamilySubscription::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', now()->toDateString())
            ->update(['status' => 'expired']);
    }

    /**
     * Cashier flow: create a family package subscription (cash activates immediately, online stays pending).
     */
    public function createCashierSubscription(
        string $hipUserId,
        int $packageId,
        string $paymentMode,
        float $amount,
        ?string $createdBy = null,
        array $coveredMemberIds = [],
    ): UserFamilySubscription {
        $package = FamilyPackage::active()->find($packageId);

        if (! $package) {
            throw new InvalidArgumentException('Family package not found or inactive.');
        }

        if (! in_array($paymentMode, ['cash', 'online'], true)) {
            throw new InvalidArgumentException('Invalid payment mode.');
        }

        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be zero or greater.');
        }

        $coveredMemberIds = $this->validateCoveredMembers($hipUserId, $package, $coveredMemberIds);

        if ($this->userHasBlockingSubscription($hipUserId)) {
            throw new InvalidArgumentException('User already has an active or pending family subscription.');
        }

        $person = $this->resolvePersonForHipUser($hipUserId);

        if (! $person) {
            throw new InvalidArgumentException('No person profile found for this user.');
        }

        return DB::transaction(function () use ($hipUserId, $package, $paymentMode, $amount, $createdBy, $person, $coveredMemberIds) {
            $dates = $this->calculateSubscriptionDates($package);
            $isCash = $paymentMode === 'cash';

            $invoice = $this->createFamilyPackageInvoice(
                $person,
                $package,
                $amount,
                $isCash ? 'completed' : 'pending',
                $isCash ? 'cash' : null,
            );

            if ($isCash) {
                Transactions::create([
                    'invoice_id' => $invoice->id,
                    'service_types' => ['family_package'],
                    'invoice_details' => $invoice->invoice_details,
                    'transaction_amount' => $amount,
                    'total_amount' => $amount,
                    'status' => 'completed',
                    'payment_method' => 'cash',
                ]);
            }

            $subscription = UserFamilySubscription::create([
                'hip_user_id' => $hipUserId,
                'family_package_id' => $package->id,
                'covered_member_ids' => $coveredMemberIds,
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
                'status' => $isCash ? 'active' : 'pending',
                'payment_status' => $isCash ? 'paid' : 'pending',
                'payment_mode' => $paymentMode,
                'activated_at' => $isCash ? now() : null,
                'created_by' => $createdBy,
                'invoice_id' => $invoice->id,
                'amount_paid' => $isCash ? $amount : 0,
            ]);

            if ($isCash) {
                $this->createSubscriptionReward($subscription);
            } else {
                app(PaymentApiService::class)->sendInvoiceNotification($invoice, false);
            }

            return $subscription->load(['familyPackage', 'invoice', 'member']);
        });
    }

    /**
     * Cashier renew flow — only for expired/cancelled subscriptions.
     * Overlap policy: block if user already has active/pending; renew creates a fresh term from today.
     */
    public function renewCashierSubscription(
        int $previousSubscriptionId,
        int $packageId,
        string $paymentMode,
        float $amount,
        ?string $createdBy = null,
        array $coveredMemberIds = [],
    ): UserFamilySubscription {
        $previous = UserFamilySubscription::with('familyPackage')->findOrFail($previousSubscriptionId);

        if (! in_array($previous->status, ['expired', 'cancelled'], true)) {
            throw new InvalidArgumentException('Only expired or cancelled subscriptions can be renewed.');
        }

        if ($this->userHasBlockingSubscription($previous->hip_user_id)) {
            throw new InvalidArgumentException('User already has an active or pending family subscription.');
        }

        if ($coveredMemberIds === [] && ! empty($previous->covered_member_ids)) {
            $coveredMemberIds = $previous->covered_member_ids;
        }

        return $this->createCashierSubscription(
            $previous->hip_user_id,
            $packageId,
            $paymentMode,
            $amount,
            $createdBy,
            $coveredMemberIds,
        );
    }

    /**
     * Activate a pending subscription after invoice payment is confirmed (online/Razorpay or manual mark paid).
     */
    public function activateSubscriptionFromInvoice(Invoice $invoice): ?UserFamilySubscription
    {
        $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];

        if (! in_array('family_package', $serviceTypes, true)) {
            return null;
        }

        $subscription = UserFamilySubscription::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->first();

        if (! $subscription) {
            return null;
        }

        $package = FamilyPackage::find($subscription->family_package_id);

        if (! $package) {
            throw new InvalidArgumentException('Family package not found for subscription activation.');
        }

        if ($this->getUserActiveSubscription($subscription->hip_user_id)) {
            throw new InvalidArgumentException('User already has an active family subscription.');
        }

        $dates = $this->calculateSubscriptionDates($package);
        $amount = (float) ($invoice->total_amount ?? $subscription->amount_paid ?? $package->price);

        $subscription->update([
            'start_date' => $dates['start_date'],
            'end_date' => $dates['end_date'],
            'status' => 'active',
            'payment_status' => 'paid',
            'activated_at' => now(),
            'amount_paid' => $amount,
        ]);

        if (! $subscription->userRewards()->exists()) {
            $this->createSubscriptionReward($subscription);
        }

        $this->notifySubscriptionActivated($subscription);

        return $subscription->fresh(['familyPackage', 'invoice', 'member']);
    }

    /**
     * Cashier manual mark-paid for online pending invoices.
     */
    public function markSubscriptionInvoicePaid(int $subscriptionId, string $paymentMethod = 'cash'): UserFamilySubscription
    {
        $subscription = UserFamilySubscription::with('invoice')->findOrFail($subscriptionId);

        if ($subscription->status !== 'pending') {
            throw new InvalidArgumentException('Subscription is not pending payment.');
        }

        $invoice = $subscription->invoice;

        if (! $invoice || $invoice->status !== 'pending') {
            throw new InvalidArgumentException('No pending invoice found for this subscription.');
        }

        return DB::transaction(function () use ($subscription, $invoice, $paymentMethod) {
            $amount = (float) ($invoice->total_amount ?? $subscription->amount_paid ?? 0);

            Transactions::create([
                'invoice_id' => $invoice->id,
                'service_types' => ['family_package'],
                'invoice_details' => $invoice->invoice_details,
                'transaction_amount' => $amount,
                'total_amount' => $amount,
                'status' => 'completed',
                'payment_method' => $paymentMethod,
            ]);

            $invoice->update([
                'status' => 'completed',
                'payment_method' => $paymentMethod,
            ]);

            return $this->activateSubscriptionFromInvoice($invoice->fresh());
        });
    }

    public function userHasBlockingSubscription(string $hipUserId): bool
    {
        return UserFamilySubscription::query()
            ->where('hip_user_id', $hipUserId)
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(function ($active) {
                        $active->where('status', 'active')
                            ->whereDate('end_date', '>=', now()->toDateString());
                    });
            })
            ->exists();
    }

    /**
     * @return array<int, array{id: string, name: string, relationship: string}>
     */
    public function getFamilyMembersForUser(string $hipUserId): array
    {
        $user = HIPUser::find($hipUserId);

        if (! $user) {
            return [];
        }

        $primaryPerson = Persons::query()
            ->where('hip_user_id', $hipUserId)
            ->orderByDesc('is_primary')
            ->first();

        if (! $primaryPerson) {
            return [];
        }

        $selfName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($selfName === '') {
            $selfName = trim(($primaryPerson->first_name ?? '').' '.($primaryPerson->last_name ?? ''));
        }

        $members = [[
            'id' => (string) $primaryPerson->id,
            'name' => $selfName !== '' ? $selfName : 'Self',
            'relationship' => 'Self',
        ]];

        $dependents = Persons::query()
            ->where('parent_id', $primaryPerson->id)
            ->where('id', '!=', $primaryPerson->id)
            ->orderBy('first_name')
            ->get();

        foreach ($dependents as $dependent) {
            $members[] = [
                'id' => (string) $dependent->id,
                'name' => trim(($dependent->first_name ?? '').' '.($dependent->last_name ?? '')),
                'relationship' => $dependent->relationship
                    ?: ($dependent->gender === 'Female' ? 'Mother' : 'Father'),
            ];
        }

        return $members;
    }

    /**
     * @return array<int, string>
     */
    public function validateCoveredMembers(string $hipUserId, FamilyPackage $package, array $coveredMemberIds): array
    {
        $allowedMembers = $this->getFamilyMembersForUser($hipUserId);
        $allowedIds = collect($allowedMembers)->pluck('id')->all();
        $maxMembers = max(1, (int) $package->max_members);

        $coveredMemberIds = array_values(array_unique(array_filter(array_map('strval', $coveredMemberIds))));

        if ($coveredMemberIds === []) {
            throw new InvalidArgumentException('Select at least one covered member.');
        }

        if (count($coveredMemberIds) > $maxMembers) {
            throw new InvalidArgumentException("You can select at most {$maxMembers} member(s) for this package.");
        }

        foreach ($coveredMemberIds as $memberId) {
            if (! in_array($memberId, $allowedIds, true)) {
                throw new InvalidArgumentException('One or more selected members do not belong to this user.');
            }
        }

        return $coveredMemberIds;
    }

    /**
     * @return array<int, array{id: string, name: string, relationship: string}>
     */
    public function resolveCoveredMembersDetails(UserFamilySubscription $subscription): array
    {
        $ids = $subscription->covered_member_ids ?? [];

        if ($ids === []) {
            return [];
        }

        $persons = Persons::query()->whereIn('id', $ids)->get()->keyBy('id');
        $user = $subscription->member;
        $ownerUserId = (string) $subscription->hip_user_id;

        return collect($ids)->map(function (string $id) use ($persons, $user, $ownerUserId) {
            $person = $persons->get($id);

            if (! $person) {
                return null;
            }

            // Self = person linked to the subscription account holder, not merely is_primary
            // (dependents may incorrectly have is_primary set in persons table).
            $isSelf = (string) $person->hip_user_id === $ownerUserId;
            $name = trim(($person->first_name ?? '').' '.($person->last_name ?? ''));

            if ($isSelf && $user) {
                $userName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                if ($userName !== '') {
                    $name = $userName;
                }
            }

            $image = $person->image;

            $relationship = $isSelf
                ? 'Self'
                : ucfirst(strtolower((string) ($person->relationship ?: 'Dependent')));

            return [
                'id' => (string) $person->id,
                'name' => $name !== '' ? $name : 'Member',
                'relationship' => $relationship,
                'image' => $image ? asset('storage/users/' . $image) : null,
            ];
        })->filter()->values()->all();
    }

    private function resolvePersonForHipUser(string $hipUserId): ?Persons
    {
        return Persons::query()
            ->where('hip_user_id', $hipUserId)
            ->orderByDesc('is_primary')
            ->first();
    }

    private function resolvePrimaryPerson(Persons $person): Persons
    {
        if (! empty($person->parent_id)) {
            return Persons::find((string) $person->parent_id) ?: $person;
        }

        return $person;
    }

    /**
     * @return array{start_date: Carbon, end_date: Carbon}
     */
    private function calculateSubscriptionDates(FamilyPackage $package): array
    {
        $startDate = Carbon::today();

        return [
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays((int) $package->duration_days),
        ];
    }

    private function createFamilyPackageInvoice(
        Persons $person,
        FamilyPackage $package,
        float $amount,
        string $status,
        ?string $paymentMethod,
    ): Invoice {
        $primaryPerson = $this->resolvePrimaryPerson($person);

        return Invoice::create([
            'primary_person_id' => $primaryPerson->id,
            'person_id' => $person->id,
            'service_types' => ['family_package'],
            'invoice_details' => [
                'family_package' => [[
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'duration_days' => (int) $package->duration_days,
                    'amount' => $amount,
                ]],
            ],
            'total_amount' => $amount,
            'amount' => $amount,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'coins_earned' => $status === 'completed' ? (int) round($amount * 0.01) : 0,
        ]);
    }

    private function createSubscriptionReward(UserFamilySubscription $subscription): UserReward
    {
        return UserReward::create([
            'entity_id' => $subscription->id,
            'entity_type' => UserReward::ENTITY_TYPE_FAMILY_PACKAGE,
            'hip_user_id' => $subscription->hip_user_id,
            'is_applied' => false,
        ]);
    }

    private function notifySubscriptionActivated(UserFamilySubscription $subscription): void
    {
        $packageName = $subscription->familyPackage?->name ?? 'Family Package';

        app(NotificationService::class)->notifyUser(
            $subscription->hip_user_id,
            'Subscription Activated',
            "Your {$packageName} subscription is now active.",
            [
                'type' => 'family_package_subscription',
                'subscription_id' => (string) $subscription->id,
                'screen' => 'subscription',
            ],
        );
    }
}
