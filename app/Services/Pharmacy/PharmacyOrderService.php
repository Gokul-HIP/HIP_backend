<?php

namespace App\Services\Pharmacy;

use App\Enums\PharmacyOrderStatus;
use App\Enums\PharmacyPaymentStatus;
use App\Models\Document;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Pharmacy;
use App\Models\PharmacyOrder;
use App\Models\PharmacyOrderComment;
use App\Models\PharmacyOrderItem;
use App\Models\PharmacyOrderStatusLog;
use App\Models\PharmacyProducts;
use App\Models\RazorpayPayment;
use App\Models\Transactions;
use App\Events\Pharmacy\PharmacyOrderCommentAdded;
use App\Events\Pharmacy\PharmacyOrderCreated;
use App\Events\Pharmacy\PharmacyOrderUpdated;
use App\Policies\PharmacyOrderPolicy;
use App\Services\Api\PaymentApiService;
use App\Support\PharmacyMedicineOptions;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PharmacyOrderService
{
    /**
     * Set only by markPaid(). transition() rejects processing without it.
     */
    private bool $allowProcessingTransition = false;

    /**
     * Line unit_price is pharmacy_products.selling_price.
     * pharmacy_products.discount is ignored. Order discount is a manual amount.
     *
     * pending_payment -> processing is allowed only while markPaid() holds the internal flag.
     * requested -> pending_payment is not allowed; review comes first.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        PharmacyOrderStatus::Requested->value => [
            PharmacyOrderStatus::InReview->value,
            PharmacyOrderStatus::Cancelled->value,
            PharmacyOrderStatus::Rejected->value,
        ],
        PharmacyOrderStatus::InReview->value => [
            PharmacyOrderStatus::PendingPayment->value,
            PharmacyOrderStatus::Cancelled->value,
            PharmacyOrderStatus::Rejected->value,
        ],
        PharmacyOrderStatus::PendingPayment->value => [
            PharmacyOrderStatus::PendingPayment->value,
            PharmacyOrderStatus::Cancelled->value,
            PharmacyOrderStatus::Rejected->value,
            PharmacyOrderStatus::Processing->value,
        ],
        PharmacyOrderStatus::Processing->value => [
            PharmacyOrderStatus::ReadyForDelivery->value,
        ],
        PharmacyOrderStatus::ReadyForDelivery->value => [
            PharmacyOrderStatus::Dispatched->value,
        ],
        PharmacyOrderStatus::Dispatched->value => [
            PharmacyOrderStatus::OutForDelivery->value,
            PharmacyOrderStatus::Delivered->value,
        ],
        PharmacyOrderStatus::OutForDelivery->value => [
            PharmacyOrderStatus::Delivered->value,
        ],
        PharmacyOrderStatus::Delivered->value => [],
        PharmacyOrderStatus::Cancelled->value => [],
        PharmacyOrderStatus::Rejected->value => [],
    ];

    /**
     * @var array<string, string>
     */
    private const STAGE_COLUMNS = [
        PharmacyOrderStatus::InReview->value => 'in_review_at',
        PharmacyOrderStatus::Processing->value => 'processing_at',
        PharmacyOrderStatus::ReadyForDelivery->value => 'ready_at',
        PharmacyOrderStatus::Dispatched->value => 'dispatched_at',
        PharmacyOrderStatus::OutForDelivery->value => 'out_for_delivery_at',
        PharmacyOrderStatus::Delivered->value => 'delivered_at',
        PharmacyOrderStatus::Cancelled->value => 'cancelled_at',
    ];

    public function generateOrderNumber(): string
    {
        $prefix = 'PH-'.now()->format('ymd').'-';

        $allocate = function () use ($prefix): string {
            $latest = PharmacyOrder::query()
                ->where('order_number', 'like', $prefix.'%')
                ->orderByDesc('order_number')
                ->value('order_number');

            $sequence = 1;
            if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            do {
                $number = $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
                $sequence++;
            } while (PharmacyOrder::query()->where('order_number', $number)->exists());

            return $number;
        };

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return $allocate();
        }

        $lockName = 'pharmacy_order_numbers_'.$prefix;
        $locked = DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockName]);

        if ((int) ($locked->acquired ?? 0) !== 1) {
            throw new DomainException('Could not allocate a pharmacy order number.');
        }

        try {
            return $allocate();
        } finally {
            DB::select('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    public function transition(
        PharmacyOrder $order,
        PharmacyOrderStatus|string $toStatus,
        ?HIPUser $actor = null,
        ?string $note = null
    ): PharmacyOrder {
        $to = $this->status($toStatus);

        if ($to === PharmacyOrderStatus::Processing && ! $this->allowProcessingTransition) {
            throw new DomainException('Cannot move a pharmacy order to processing outside markPaid().');
        }

        return DB::transaction(function () use ($order, $to, $actor, $note) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $this->status($locked->status);
            $allowed = self::ALLOWED[$from->value] ?? [];

            if (! in_array($to->value, $allowed, true)) {
                throw new DomainException(
                    "Cannot move pharmacy order {$locked->order_number} from {$from->value} to {$to->value}."
                );
            }

            $locked->status = $to;

            if ($column = self::STAGE_COLUMNS[$to->value] ?? null) {
                $locked->{$column} = now();
            }

            if ($to === PharmacyOrderStatus::Cancelled) {
                if ($actor) {
                    $locked->cancelled_by = $actor->id;
                }
                if (filled($note)) {
                    $locked->cancel_reason = $note;
                }
            }

            $locked->save();

            PharmacyOrderStatusLog::query()->create([
                'order_id' => $locked->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_type' => $this->actorType($actor),
                'actor_id' => $actor?->id,
                'note' => $note,
                'created_at' => now(),
            ]);

            // Phase 5: dispatch pharmacy order status notification here.
            $orderId = (int) $locked->id;
            $statusChanged = $from->value !== $to->value;
            $this->afterCommitOrder($orderId, function (PharmacyOrder $fresh) use ($statusChanged, $to) {
                $this->broadcastOrderUpdated($fresh);
                if ($statusChanged) {
                    app(PharmacyNotificationService::class)->statusChanged($fresh, $to);
                }
            });

            return $locked->fresh();
        });
    }

    public function markPaid(PharmacyOrder $order, Invoice $invoice): PharmacyOrder
    {
        return DB::transaction(function () use ($order, $invoice) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === PharmacyPaymentStatus::Paid) {
                return $locked;
            }

            $this->decrementCatalogStock($locked);

            $locked->payment_status = PharmacyPaymentStatus::Paid;
            $locked->paid_at = now();
            $locked->invoice_id = $invoice->id;
            $this->storeCoinPayment($locked, $invoice);
            $locked->save();

            $paidOrderId = (int) $locked->id;
            $this->afterCommitOrder($paidOrderId, function (PharmacyOrder $fresh) {
                app(PharmacyNotificationService::class)->paymentReceived($fresh);
            });

            $this->allowProcessingTransition = true;

            try {
                return $this->transition($locked, PharmacyOrderStatus::Processing, null);
            } finally {
                $this->allowProcessingTransition = false;
            }
        });
    }

    /**
     * Copy the completed payment onto the order. coins_applied comes from the invoice.
     * coins_discount and amount_paid come from the completed transaction.
     */
    private function storeCoinPayment(PharmacyOrder $order, Invoice $invoice): void
    {
        if (! Schema::hasColumn('pharmacy_orders', 'coins_applied')
            || ! Schema::hasColumn('pharmacy_orders', 'coins_discount')
            || ! Schema::hasColumn('pharmacy_orders', 'amount_paid')) {
            return;
        }

        $transaction = Transactions::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        $order->coins_applied = (int) ($invoice->coins_applied ?? 0);
        $order->coins_discount = round((float) ($transaction->discount_amount ?? 0), 2);
        $order->amount_paid = round((float) ($transaction->total_amount ?? 0), 2);
    }

    public function markPaymentFailed(PharmacyOrder $order): PharmacyOrder
    {
        return DB::transaction(function () use ($order) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->payment_status = PharmacyPaymentStatus::Failed;
            $locked->save();

            // Phase 5: dispatch pharmacy order payment-failed notification here.
            $failedOrderId = (int) $locked->id;
            $this->afterCommitOrder($failedOrderId, function (PharmacyOrder $fresh) {
                $this->broadcastOrderUpdated($fresh);
                app(PharmacyNotificationService::class)->paymentFailed($fresh);
            });

            return $locked->fresh();
        });
    }

    public function sendPaymentRequest(PharmacyOrder $order, HIPUser $pharmacist, bool $billSaved = true): PharmacyOrder
    {
        if (! $billSaved) {
            throw new DomainException('Save the bill before sending a payment request.');
        }

        return DB::transaction(function () use ($order, $pharmacist) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! app(PharmacyOrderPolicy::class)->pharmacistCanManage($pharmacist, $locked)) {
                throw new AuthorizationException('You cannot update this order.');
            }

            $status = $this->status($locked->status);
            $activeInvoice = $this->activeInvoice($locked);

            if ($activeInvoice) {
                throw new DomainException('This order already has an open payment request.');
            }

            $canSend = $status === PharmacyOrderStatus::InReview
                || ($status === PharmacyOrderStatus::PendingPayment && $locked->invoice_id === null);

            if (! $canSend) {
                throw new DomainException('A payment request can be sent only while the order is in review.');
            }

            if ($locked->items()->count() < 1) {
                throw new DomainException('Add at least one medicine before sending a payment request.');
            }

            if ((float) $locked->total <= 0) {
                throw new DomainException('The bill total must be greater than zero.');
            }

            $person = Persons::query()->findOrFail((string) $locked->person_id);
            $requestedPrimaryId = (string) ($person->parent_id ?: $person->id);
            $requestedPrimary = Persons::query()->find($requestedPrimaryId) ?: $person;
            $primaryPerson = ! empty($requestedPrimary->parent_id)
                ? (Persons::query()->find((string) $requestedPrimary->parent_id) ?: $requestedPrimary)
                : $requestedPrimary;

            $base = round(max(0, (float) $locked->subtotal - (float) $locked->discount + (float) $locked->delivery_fee), 2);
            $locked->load('items');

            $created = app(PaymentApiService::class)->createInvoiceForPayment([
                'person_id' => (string) $locked->person_id,
                'primary_person_id' => (string) $primaryPerson->id,
                'service_types' => ['pharmacy'],
                'subtotal' => $base,
                'total_gst' => (float) $locked->tax,
                'service_charges' => (float) $locked->service_charge,
                'payment_gateway_charges' => (float) $locked->gateway_charge,
                'total_amount' => (float) $locked->total,
                'discount_price' => 0,
                'is_in_patient' => false,
                'suppress_payment_push' => true,
                'invoice_details' => [
                    'pharmacy' => [
                        'amount' => (string) $base,
                        'order_id' => (int) $locked->id,
                        'order_number' => (string) $locked->order_number,
                        'subtotal' => (float) $locked->subtotal,
                        'discount' => (float) $locked->discount,
                        'delivery_fee' => (float) $locked->delivery_fee,
                        'items' => $locked->items->map(function (PharmacyOrderItem $item) {
                            return [
                                'name' => $item->name,
                                'strength' => $item->strength,
                                'dosage_form' => $item->dosage_form,
                                'dosage' => $item->dosage,
                                'frequency' => $item->frequency,
                                'duration' => $item->duration,
                                'when_to_take' => $item->when_to_take,
                                'qty' => (int) $item->qty,
                                'unit_price' => (float) $item->unit_price,
                                'line_total' => (float) $item->line_total,
                            ];
                        })->values()->all(),
                    ],
                ],
            ]);

            $invoice = $created['invoice'];
            $invoice->pharmacy_order_id = $locked->id;
            $invoice->save();

            $locked->invoice_id = $invoice->id;
            $locked->save();

            return $this->transition(
                $locked,
                PharmacyOrderStatus::PendingPayment,
                $pharmacist,
                'Payment request sent: INV#'.$invoice->id
            );
        });
    }

    public function reviseBill(PharmacyOrder $order, HIPUser $pharmacist): PharmacyOrder
    {
        return DB::transaction(function () use ($order, $pharmacist) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! app(PharmacyOrderPolicy::class)->pharmacistCanManage($pharmacist, $locked)) {
                throw new AuthorizationException('You cannot update this order.');
            }

            if ($this->status($locked->status) !== PharmacyOrderStatus::PendingPayment) {
                throw new DomainException('The bill can be revised only while payment is pending.');
            }

            $invoice = $locked->invoice_id
                ? Invoice::query()->whereKey($locked->invoice_id)->lockForUpdate()->first()
                : null;

            if (! $invoice || (string) $invoice->status !== 'pending') {
                throw new DomainException('There is no pending payment request to cancel.');
            }

            $captured = RazorpayPayment::query()
                ->where('invoice_id', $invoice->id)
                ->whereIn('payment_status', ['captured', 'paid'])
                ->exists();

            if ($captured) {
                throw new DomainException('This payment has already been captured and the bill cannot be revised.');
            }

            $invoice->status = 'cancelled';
            $invoice->save();

            // Existing invoice cancellation (cancelByPatient, family subscriptions) sets the
            // invoice status only. razorpay_payments and transactions have no shared cancel path.

            $locked->invoice_id = null;
            $locked->payment_status = PharmacyPaymentStatus::Unpaid;
            $locked->save();

            return $this->transition(
                $locked,
                PharmacyOrderStatus::PendingPayment,
                $pharmacist,
                'Bill revised — payment request cancelled'
            );
        });
    }

    public function recalcTotals(PharmacyOrder $order): PharmacyOrder
    {
        $subtotal = round((float) $order->items()->sum('line_total'), 2);
        $bill = app(PharmacyBillCalculator::class)->calculate(
            $subtotal,
            (float) $order->discount,
            (float) $order->delivery_fee
        );

        $order->subtotal = $bill['subtotal'];
        $order->tax = $bill['tax'];
        $order->service_charge = $bill['service_charge'];
        $order->gateway_charge = $bill['gateway_charge'];
        $order->total = $bill['total'];
        $order->save();

        return $order->fresh();
    }

    public function openForReview(PharmacyOrder $order, HIPUser $pharmacistUser): PharmacyOrder
    {
        return DB::transaction(function () use ($order, $pharmacistUser) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $status = $this->status($locked->status);

            if ($status === PharmacyOrderStatus::InReview) {
                return $locked;
            }

            if ($status !== PharmacyOrderStatus::Requested) {
                throw new DomainException(
                    "Cannot open pharmacy order {$locked->order_number} for review from {$status->value}."
                );
            }

            $locked->reviewed_by = $pharmacistUser->id;
            if ($locked->assigned_pharmacist_id === null) {
                $locked->assigned_pharmacist_id = $pharmacistUser->id;
            }
            $locked->save();

            return $this->transition($locked, PharmacyOrderStatus::InReview, $pharmacistUser);
        });
    }

    public function markDispatched(
        PharmacyOrder $order,
        ?HIPUser $actor,
        ?string $partner,
        ?string $rider,
        ?string $trackingRef,
        mixed $expectedAt
    ): PharmacyOrder {
        return DB::transaction(function () use ($order, $actor, $partner, $rider, $trackingRef, $expectedAt) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->delivery_partner = $partner;
            $locked->rider_name = $rider;
            $locked->tracking_ref = $trackingRef;
            $locked->expected_delivery_at = filled($expectedAt) ? $expectedAt : null;
            $locked->save();

            return $this->transition($locked, PharmacyOrderStatus::Dispatched, $actor);
        });
    }

    public function softDelete(PharmacyOrder $order, ?HIPUser $actor): PharmacyOrder
    {
        return DB::transaction(function () use ($order, $actor) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $status = $this->status($locked->status);
            $deletable = [
                PharmacyOrderStatus::Requested,
                PharmacyOrderStatus::InReview,
                PharmacyOrderStatus::Cancelled,
                PharmacyOrderStatus::Rejected,
            ];

            if ($locked->payment_status !== PharmacyPaymentStatus::Unpaid || ! in_array($status, $deletable, true)) {
                throw new DomainException('A pharmacy order can only be deleted when it is unpaid and still requested, in review, cancelled, or rejected.');
            }

            $locked->deleted_by = $actor?->id;
            $locked->save();
            $locked->delete();

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveItem(PharmacyOrder $order, array $attributes): PharmacyOrderItem
    {
        $isCustom = (bool) ($attributes['is_custom'] ?? false);
        $productId = isset($attributes['pharmacy_product_id']) ? (int) $attributes['pharmacy_product_id'] : 0;

        if ($isCustom || $productId <= 0) {
            $isCustom = true;
            $productId = null;
            if (! array_key_exists('unit_price', $attributes)) {
                throw ValidationException::withMessages([
                    'unit_price' => ['A custom medicine needs a unit price.'],
                ]);
            }
            $unitPrice = round((float) $attributes['unit_price'], 2);
            $medicineMasterId = $attributes['medicine_master_id'] ?? null;
        } else {
            $product = PharmacyProducts::query()->find($productId);
            if (! $product) {
                throw ValidationException::withMessages([
                    'pharmacy_product_id' => ['The selected pharmacy product was not found.'],
                ]);
            }
            $isCustom = false;
            $unitPrice = round((float) $product->selling_price, 2);
            $medicineMasterId = $attributes['medicine_master_id'] ?? $product->medicine_master_id;
            $attributes['name'] = $attributes['name'] ?? $product->product_name;
            $attributes['dosage_form'] = $attributes['dosage_form'] ?? $product->dosage_form;
            $attributes['strength'] = $attributes['strength'] ?? $product->strength;
        }

        $qty = (int) ($attributes['qty'] ?? 0);
        if ($qty < 1) {
            throw ValidationException::withMessages([
                'qty' => ['Quantity must be at least 1.'],
            ]);
        }

        $item = new PharmacyOrderItem();
        if (! empty($attributes['id'])) {
            $item = PharmacyOrderItem::query()
                ->where('order_id', $order->id)
                ->findOrFail($attributes['id']);
        }

        $item->fill([
            'order_id' => $order->id,
            'pharmacy_product_id' => $productId,
            'medicine_master_id' => $medicineMasterId,
            'name' => $attributes['name'] ?? null,
            'dosage_form' => $attributes['dosage_form'] ?? null,
            'strength' => $attributes['strength'] ?? null,
            'dosage' => PharmacyMedicineOptions::assertAllowed($attributes['dosage'] ?? null, PharmacyMedicineOptions::DOSAGES, 'dosage'),
            'frequency' => PharmacyMedicineOptions::assertAllowed($attributes['frequency'] ?? null, PharmacyMedicineOptions::FREQUENCIES, 'frequency'),
            'duration' => PharmacyMedicineOptions::assertAllowed($attributes['duration'] ?? null, PharmacyMedicineOptions::DURATIONS, 'duration'),
            'when_to_take' => PharmacyMedicineOptions::joinWhenToTake($attributes['when_to_take'] ?? null),
            'special_instruction' => filled($attributes['special_instruction'] ?? null)
                ? (string) $attributes['special_instruction']
                : null,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => round($qty * $unitPrice, 2),
            'is_custom' => $isCustom,
        ]);
        $item->save();

        $this->recalcTotals($order);

        return $item->fresh();
    }

    /**
     * @return array<int, string>
     */
    public function familyPersonIds(HIPUser $user): array
    {
        $primary = Persons::query()
            ->where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->first()
            ?? Persons::query()->where('hip_user_id', $user->id)->first();

        if (! $primary) {
            return [];
        }

        $familyRootId = (string) ($primary->parent_id ?? $primary->id);

        return Persons::query()
            ->where(function ($query) use ($primary, $familyRootId, $user) {
                $query->where('id', $primary->id)
                    ->orWhere('parent_id', $familyRootId)
                    ->orWhere('hip_user_id', $user->id);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function resolveActivePharmacyId(int $hospitalId, ?int $pharmacyId): int
    {
        $hospital = Hospital::query()->find($hospitalId);

        if (! $hospital) {
            throw ValidationException::withMessages([
                'hospital_id' => ['Hospital not found.'],
            ]);
        }

        $linkedIds = collect($hospital->pharmacy_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $activeIds = $linkedIds->isEmpty()
            ? []
            : Pharmacy::query()
                ->whereIn('id', $linkedIds->all())
                ->where('status', 'active')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $activeLookup = array_flip($activeIds);
        // Previous selection kept hospitals.pharmacy_ids order and used the first active id in that list.
        $orderedActive = $linkedIds
            ->filter(fn (int $id) => isset($activeLookup[$id]))
            ->sort()
            ->values();

        if ($orderedActive->isEmpty()) {
            throw ValidationException::withMessages([
                // 'pharmacy_id' => ['This hospital has no active pharmacy.'],
                'pharmacy_id' => ['No pharmacy is linked to this hospital.'],
            ]);
        }

        if ($pharmacyId === null) {
            return (int) $orderedActive->first();
        }

        if (! in_array($pharmacyId, $orderedActive->all(), true)) {
            throw ValidationException::withMessages([
                'pharmacy_id' => ['The selected pharmacy is not assigned to this hospital.'],
            ]);
        }

        return $pharmacyId;
    }

    public function resolveOrderPerson(HIPUser $user, mixed $patientId): Persons
    {
        $patientId = trim((string) ($patientId ?? ''));
        $familyIds = $this->callerFamilyPersonIds($user);

        if ($patientId === '') {
            return $this->callerPrimaryPerson($user);
        }

        if (in_array($patientId, $familyIds, true)) {
            $person = Persons::query()->find($patientId);

            if ($person) {
                return $person;
            }
        }

        if ($patientId === (string) $user->id) {
            return $this->callerPrimaryPerson($user);
        }

        // Previous resolution matched persons.id first, then persons.hip_user_id, then PharmacyOrderPolicy::canUsePerson.
        // A uuid that exists in both persons and healthinpocket_users was able to select the person row
        // even when that row was not in the caller's family.
        // if ($patientId === '') {
        //     $matches = Persons::query()->where('hip_user_id', $user->id)->get();
        //     $person = $this->pickLinkedPerson($matches);
        // } else {
        //     $byId = Persons::query()->find($patientId);
        //     if ($byId) {
        //         $person = $byId;
        //     } else {
        //         $person = $this->pickLinkedPerson(
        //             Persons::query()->where('hip_user_id', $patientId)->get()
        //         );
        //     }
        // }
        // if (! app(PharmacyOrderPolicy::class)->canUsePerson($user, (string) $person->id)) {
        //     throw new AuthorizationException('You can only order for yourself or your dependents.');
        // }
        // return $person;

        throw new AuthorizationException('Patient not found in your family');
    }

    /**
     * Persons linked to the caller, plus dependents reached by parent_id.
     * A row whose parent_id is itself is visited once and is not expanded again.
     *
     * @return array<int, string>
     */
    public function callerFamilyPersonIds(HIPUser $user): array
    {
        $visited = [];
        $frontier = Persons::query()
            ->where('hip_user_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
        $depth = 0;

        while ($frontier !== [] && $depth < 5) {
            $added = [];

            foreach ($frontier as $id) {
                if (isset($visited[$id])) {
                    continue;
                }

                $visited[$id] = true;
                $added[] = $id;
            }

            $depth++;

            if ($added === []) {
                break;
            }

            $frontier = Persons::query()
                ->whereIn('parent_id', $added)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->reject(fn (string $id) => isset($visited[$id]))
                ->unique()
                ->values()
                ->all();
        }

        return array_keys($visited);
    }

    private function callerPrimaryPerson(HIPUser $user): Persons
    {
        return $this->pickLinkedPerson(
            Persons::query()->where('hip_user_id', $user->id)->get()
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function resolveDeliveryDetails(Persons $person, mixed $name, mixed $phone, mixed $address): array
    {
        $name = trim((string) ($name ?? ''));
        $phone = trim((string) ($phone ?? ''));
        $address = trim((string) ($address ?? ''));
        $root = $this->familyRootPerson($person);

        if ($name === '') {
            $name = trim((string) (($person->first_name ?? '').' '.($person->last_name ?? '')));
        }

        if ($phone === '') {
            $phone = trim((string) ($person->mobile ?? ''));
            if ($phone === '' && (string) $root->id !== (string) $person->id) {
                $phone = trim((string) ($root->mobile ?? ''));
            }
            if ($phone === '') {
                $phone = $this->accountMobile($person);
            }
            if ($phone === '' && (string) $root->id !== (string) $person->id) {
                $phone = $this->accountMobile($root);
            }
        }

        if ($address === '') {
            $address = trim((string) ($person->address ?? ''));
        }

        if ($address === '') {
            $address = $this->userProfileAddress($person);
        }

        $errors = [];

        if ($name === '') {
            $errors['delivery_name'] = ['Please add a delivery name to this patient\'s profile or send delivery_name.'];
        }

        if ($phone === '') {
            // $errors['delivery_phone'] = ['Please add a delivery phone to this patient\'s profile or send delivery_phone.'];
            $errors['delivery_phone'] = ['No phone number on file for this patient'];
        }

        // An empty address does not block the order. The response sets delivery.address null and delivery.address_missing true.
        // if ($address === '') {
        //     $errors['delivery_address'] = ['Please add a delivery address to this patient\'s profile or send delivery_address.'];
        // }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [$name, $phone, $address];
    }

    private function pickLinkedPerson(Collection $matches): Persons
    {
        if ($matches->isEmpty()) {
            throw ValidationException::withMessages([
                'patient_id' => ['Patient was not found. Send the person id.'],
            ]);
        }

        $primaries = $matches->filter(fn (Persons $person) => (bool) $person->is_primary)->values();

        if ($primaries->count() === 1) {
            return $primaries->first();
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        throw ValidationException::withMessages([
            'patient_id' => ['Several profiles match this account. Send the person id.'],
        ]);
    }

    private function userProfileAddress(Persons $person): string
    {
        $userId = trim((string) ($person->hip_user_id ?? ''));

        if ($userId === '') {
            return '';
        }

        $user = HIPUser::query()->find($userId);

        if (! $user) {
            return '';
        }

        $parts = array_values(array_filter(
            array_map(
                fn ($value) => trim((string) $value),
                [$user->house_number, $user->street, $user->city, $user->state, $user->zip_code]
            ),
            fn (string $value) => $value !== ''
        ));

        return implode(', ', $parts);
    }

    private function accountMobile(Persons $person): string
    {
        $userId = trim((string) ($person->hip_user_id ?? ''));

        if ($userId === '') {
            return '';
        }

        $user = HIPUser::query()->find($userId);

        return trim((string) ($user->mobile_num ?? ''));
    }

    private function familyRootPerson(Persons $person): Persons
    {
        $rootId = trim((string) ($person->parent_id ?? ''));

        if ($rootId === '' || $rootId === (string) $person->id) {
            return $person;
        }

        return Persons::query()->find($rootId) ?? $person;
    }

    public function submitOrder(
        HIPUser $user,
        int $hospitalId,
        int $pharmacyId,
        string $personId,
        ?string $note,
        string $deliveryName,
        string $deliveryPhone,
        string $deliveryAddress,
        UploadedFile $file
    ): PharmacyOrder {
        $detected = is_file($file->getRealPath() ?: '')
            ? (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath())
            : false;
        $mime = is_string($detected) ? $detected : '';
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => null,
        };

        if ($extension === null) {
            throw ValidationException::withMessages([
                'prescription' => ['The prescription must be a JPG, PNG, WEBP, or PDF file.'],
            ]);
        }

        $directory = 'pharmacy-prescriptions/'.now()->format('Y/m');
        $storedPath = $file->storeAs($directory, (string) Str::uuid().'.'.$extension, 'local');

        if (! $storedPath) {
            throw new \RuntimeException('Could not store the prescription.');
        }

        try {
            return DB::transaction(function () use (
                $user,
                $hospitalId,
                $pharmacyId,
                $personId,
                $note,
                $deliveryName,
                $deliveryPhone,
                $deliveryAddress,
                $file,
                $storedPath
            ) {
                $document = new Document();
                $document->member_id = null;
                $document->patient_id = null;
                $document->document_name = $file->getClientOriginalName() ?: 'prescription';
                $document->document_path = $storedPath;
                $document->document_type = PharmacyOrder::PRESCRIPTION_DOC_TYPE;
                $document->document_size = (string) $file->getSize();
                $document->created_by = $user->id;
                $document->updated_by = $user->id;
                $document->save();

                $order = PharmacyOrder::query()->create([
                    'order_number' => $this->generateOrderNumber(),
                    'hip_user_id' => $user->id,
                    'person_id' => $personId,
                    'hospital_id' => $hospitalId,
                    'pharmacy_id' => $pharmacyId,
                    'prescription_document_id' => $document->id,
                    'status' => PharmacyOrderStatus::Requested,
                    'payment_status' => PharmacyPaymentStatus::Unpaid,
                    'delivery_name' => $deliveryName,
                    'delivery_phone' => $deliveryPhone,
                    // 'delivery_address' => $deliveryAddress,
                    'delivery_address' => $deliveryAddress !== '' ? $deliveryAddress : null,
                ]);

                $trimmedNote = trim((string) $note);
                if ($trimmedNote !== '') {
                    $this->addComment($order, 'patient', $user->id, $trimmedNote);
                }

                $this->writeStatusLog($order, null, PharmacyOrderStatus::Requested->value, $user, null);

                // Phase 5: dispatch pharmacy order submitted notification here.
                $createdId = (int) $order->id;
                $this->afterCommitOrder($createdId, function (PharmacyOrder $fresh) {
                    $this->safeBroadcast(new PharmacyOrderCreated(
                        (int) $fresh->id,
                        (string) $fresh->order_number,
                        $fresh->status instanceof PharmacyOrderStatus ? $fresh->status->value : (string) $fresh->status,
                        $fresh->payment_status instanceof PharmacyPaymentStatus ? $fresh->payment_status->value : (string) $fresh->payment_status,
                        (int) $fresh->pharmacy_id,
                    ));
                });

                return $order->fresh();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPath);

            throw $exception;
        }
    }

    public function addComment(PharmacyOrder $order, string $authorType, ?string $authorId, string $body): PharmacyOrderComment
    {
        $status = $order->status instanceof PharmacyOrderStatus
            ? $order->status
            : PharmacyOrderStatus::tryFrom((string) $order->status);

        if (in_array($status, [
            PharmacyOrderStatus::Delivered,
            PharmacyOrderStatus::Cancelled,
            PharmacyOrderStatus::Rejected,
        ], true)) {
            throw ValidationException::withMessages([
                'body' => ['Comments are closed for this order.'],
            ]);
        }

        $comment = PharmacyOrderComment::query()->create([
            'order_id' => $order->id,
            'author_type' => $authorType,
            'author_id' => $authorId,
            'body' => $body,
        ]);

        $commentId = (int) $comment->id;
        $notifyPatient = $authorType === 'pharmacist';
        DB::afterCommit(function () use ($commentId, $notifyPatient) {
            try {
                $freshComment = PharmacyOrderComment::query()->find($commentId);
                $freshOrder = $freshComment
                    ? PharmacyOrder::query()->find($freshComment->order_id)
                    : null;
                if (! $freshComment || ! $freshOrder) {
                    return;
                }

                $this->safeBroadcast(new PharmacyOrderCommentAdded(
                    (int) $freshOrder->id,
                    (int) $freshComment->id,
                    (string) $freshComment->body,
                    (string) $freshComment->author_type,
                    $freshComment->created_at?->toIso8601String(),
                ));

                if ($notifyPatient) {
                    app(PharmacyNotificationService::class)->pharmacistComment($freshOrder);
                }
            } catch (\Throwable $exception) {
                Log::warning('Pharmacy comment hook failed', [
                    'comment_id' => $commentId,
                    'error' => $exception->getMessage(),
                ]);
            }
        });

        return $comment;
    }

    public function cancelByPatient(PharmacyOrder $order, HIPUser $user, ?string $reason): PharmacyOrder
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $status = $locked->status instanceof PharmacyOrderStatus
                ? $locked->status
                : PharmacyOrderStatus::from((string) $locked->status);

            if (! in_array($status, [
                PharmacyOrderStatus::Requested,
                PharmacyOrderStatus::InReview,
                PharmacyOrderStatus::PendingPayment,
            ], true)
                || $locked->payment_status === PharmacyPaymentStatus::Paid) {
                throw new DomainException('This order can no longer be cancelled.');
            }

            $cancelled = $this->transition($locked, PharmacyOrderStatus::Cancelled, $user, $reason);

            if ($cancelled->invoice_id) {
                $invoice = Invoice::query()->whereKey($cancelled->invoice_id)->lockForUpdate()->first();

                if ($invoice && (string) $invoice->status === 'pending') {
                    $invoice->status = 'cancelled';
                    $invoice->save();
                }
            }

            // Phase 5: dispatch pharmacy order cancelled notification here.

            return $cancelled->fresh(['invoice']);
        });
    }

    private function activeInvoice(PharmacyOrder $order): ?Invoice
    {
        if (! $order->invoice_id) {
            return null;
        }

        $invoice = Invoice::query()->find($order->invoice_id);
        if (! $invoice) {
            return null;
        }

        if (in_array((string) $invoice->status, ['cancelled', 'refunded', 'failed'], true)) {
            return null;
        }

        return $invoice;
    }

    private function decrementCatalogStock(PharmacyOrder $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->pharmacy_product_id) {
                continue;
            }

            $product = PharmacyProducts::query()->whereKey($item->pharmacy_product_id)->lockForUpdate()->first();
            if (! $product || ! is_numeric($product->stock_quantity)) {
                continue;
            }

            $product->stock_quantity = max(0, (int) $product->stock_quantity - (int) $item->qty);
            $product->save();
        }
    }

    private function writeStatusLog(
        PharmacyOrder $order,
        ?string $fromStatus,
        string $toStatus,
        ?HIPUser $actor,
        ?string $note
    ): void {
        PharmacyOrderStatusLog::query()->create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_type' => $this->actorType($actor),
            'actor_id' => $actor?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function status(PharmacyOrderStatus|string $status): PharmacyOrderStatus
    {
        if ($status instanceof PharmacyOrderStatus) {
            return $status;
        }

        $resolved = PharmacyOrderStatus::tryFrom($status);

        if (! $resolved) {
            throw new DomainException("Unknown pharmacy order status [{$status}].");
        }

        return $resolved;
    }

    private function actorType(?HIPUser $actor): string
    {
        if (! $actor) {
            return 'system';
        }

        return $actor->hasRole('pharmacist') ? 'pharmacist' : 'patient';
    }

    /**
     * Replace the bill lines and totals in one transaction.
     * Editable only while the order is in review.
     * $expectedUpdatedAt is the updated_at the pharmacist loaded.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function saveBill(
        PharmacyOrder $order,
        array $items,
        float $discount,
        float $deliveryFee,
        HIPUser $actor,
        mixed $expectedUpdatedAt
    ): PharmacyOrder {
        return DB::transaction(function () use ($order, $items, $discount, $deliveryFee, $actor, $expectedUpdatedAt) {
            $locked = PharmacyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! app(PharmacyOrderPolicy::class)->pharmacistCanManage($actor, $locked)) {
                throw new AuthorizationException('You cannot update this order.');
            }

            $current = $locked->updated_at?->format('Y-m-d H:i:s');
            $expected = '';
            if ($expectedUpdatedAt !== null && $expectedUpdatedAt !== '') {
                try {
                    $expected = \Carbon\Carbon::parse((string) $expectedUpdatedAt)->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    $expected = '';
                }
            }

            if ($current === null || $expected === '' || $current !== $expected) {
                throw new DomainException('This order was updated by someone else — reload');
            }

            if ($this->status($locked->status) !== PharmacyOrderStatus::InReview) {
                throw new DomainException('This bill can only be saved while the order is in review.');
            }

            $discount = round($discount, 2);
            $deliveryFee = round($deliveryFee, 2);

            if ($discount < 0) {
                throw ValidationException::withMessages([
                    'discount' => ['Discount must be zero or more.'],
                ]);
            }

            if ($deliveryFee < 0) {
                throw ValidationException::withMessages([
                    'delivery_fee' => ['Delivery fee must be zero or more.'],
                ]);
            }

            $prepared = [];
            foreach (array_values($items) as $index => $attributes) {
                if (! is_array($attributes)) {
                    throw ValidationException::withMessages([
                        'items' => ['Each bill line must be a medicine.'],
                    ]);
                }
                $prepared[] = $this->prepareBillLine($locked, $attributes, $index);
            }

            $subtotal = round(array_sum(array_column($prepared, 'line_total')), 2);
            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => ['Discount cannot exceed the subtotal.'],
                ]);
            }

            PharmacyOrderItem::query()->where('order_id', $locked->id)->delete();
            foreach ($prepared as $row) {
                PharmacyOrderItem::query()->create($row);
            }

            $locked->discount = $discount;
            $locked->delivery_fee = $deliveryFee;
            $locked->save();

            return $this->recalcTotals($locked);
        });
    }

    public function addPharmacistComment(PharmacyOrder $order, HIPUser $pharmacist, string $body): PharmacyOrderComment
    {
        if (! app(PharmacyOrderPolicy::class)->pharmacistCanManage($pharmacist, $order)) {
            throw new AuthorizationException('You cannot update this order.');
        }

        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => ['Enter a message.'],
            ]);
        }

        if (mb_strlen($body) > 1000) {
            throw ValidationException::withMessages([
                'body' => ['A message can be at most 1000 characters.'],
            ]);
        }

        // Phase 5: broadcast this pharmacist comment and notify the patient.
        return $this->addComment($order, 'pharmacist', $pharmacist->id, $body);
    }

    public function markPatientCommentsRead(PharmacyOrder $order): int
    {
        return PharmacyOrderComment::query()
            ->where('order_id', $order->id)
            ->where('author_type', 'patient')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareBillLine(PharmacyOrder $order, array $attributes, int $index): array
    {
        $label = 'Line '.($index + 1);
        $isCustom = (bool) ($attributes['is_custom'] ?? false);
        $productId = isset($attributes['pharmacy_product_id']) ? (int) $attributes['pharmacy_product_id'] : 0;

        if ($isCustom || $productId <= 0) {
            $isCustom = true;
            $productId = null;
            $name = trim((string) ($attributes['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => [$label.': a medicine name is required.'],
                ]);
            }
            if (! array_key_exists('unit_price', $attributes) || $attributes['unit_price'] === '' || $attributes['unit_price'] === null) {
                throw ValidationException::withMessages([
                    'unit_price' => [$label.': a unit price is required.'],
                ]);
            }
            if (! is_numeric($attributes['unit_price']) || (float) $attributes['unit_price'] < 0) {
                throw ValidationException::withMessages([
                    'unit_price' => [$label.': unit price must be zero or more.'],
                ]);
            }
            $unitPrice = round((float) $attributes['unit_price'], 2);
            $medicineMasterId = $attributes['medicine_master_id'] ?? null;
            $dosageForm = $attributes['dosage_form'] ?? null;
            $strength = $attributes['strength'] ?? null;
        } else {
            $product = PharmacyProducts::query()->find($productId);
            if (! $product || (int) $product->pharmacy_id !== (int) $order->pharmacy_id) {
                throw ValidationException::withMessages([
                    'pharmacy_product_id' => [$label.': the selected pharmacy product was not found.'],
                ]);
            }
            if ((string) $product->product_status !== 'active') {
                throw ValidationException::withMessages([
                    'pharmacy_product_id' => [$label.': that medicine is not active.'],
                ]);
            }
            $isCustom = false;
            $unitPrice = round((float) $product->selling_price, 2);
            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    'unit_price' => [$label.': unit price must be zero or more.'],
                ]);
            }
            $medicineMasterId = $attributes['medicine_master_id'] ?? $product->medicine_master_id;
            $name = (string) ($attributes['name'] ?? $product->product_name);
            $dosageForm = $attributes['dosage_form'] ?? $product->dosage_form;
            $strength = $attributes['strength'] ?? $product->strength;
        }

        if (! is_numeric($attributes['qty'] ?? null) || (int) $attributes['qty'] < 1 || (string) (int) $attributes['qty'] !== (string) (0 + $attributes['qty'])) {
            throw ValidationException::withMessages([
                'qty' => [$label.': quantity must be at least 1.'],
            ]);
        }
        $qty = (int) $attributes['qty'];

        return [
            'order_id' => $order->id,
            'pharmacy_product_id' => $productId,
            'medicine_master_id' => $medicineMasterId ?: null,
            'name' => $name,
            'dosage_form' => filled($dosageForm) ? (string) $dosageForm : null,
            'strength' => filled($strength) ? (string) $strength : null,
            'dosage' => PharmacyMedicineOptions::assertAllowed($attributes['dosage'] ?? null, PharmacyMedicineOptions::DOSAGES, 'dosage'),
            'frequency' => PharmacyMedicineOptions::assertAllowed($attributes['frequency'] ?? null, PharmacyMedicineOptions::FREQUENCIES, 'frequency'),
            'duration' => PharmacyMedicineOptions::assertAllowed($attributes['duration'] ?? null, PharmacyMedicineOptions::DURATIONS, 'duration'),
            'when_to_take' => PharmacyMedicineOptions::joinWhenToTake($attributes['when_to_take'] ?? null),
            'special_instruction' => filled($attributes['special_instruction'] ?? null)
                ? (string) $attributes['special_instruction']
                : null,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => round($qty * $unitPrice, 2),
            'is_custom' => $isCustom,
        ];
    }

    private function afterCommitOrder(int $orderId, callable $callback): void
    {
        DB::afterCommit(function () use ($orderId, $callback) {
            try {
                $fresh = PharmacyOrder::query()->find($orderId);
                if (! $fresh) {
                    return;
                }

                $callback($fresh);
            } catch (\Throwable $exception) {
                Log::warning('Pharmacy realtime hook failed', [
                    'order_id' => $orderId,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function broadcastOrderUpdated(PharmacyOrder $order): void
    {
        $this->safeBroadcast(new PharmacyOrderUpdated(
            (int) $order->id,
            (string) $order->order_number,
            $order->status instanceof PharmacyOrderStatus ? $order->status->value : (string) $order->status,
            $order->payment_status instanceof PharmacyPaymentStatus ? $order->payment_status->value : (string) $order->payment_status,
            (int) $order->pharmacy_id,
        ));
    }

    private function safeBroadcast(object $event): void
    {
        try {
            event($event);
        } catch (\Throwable $exception) {
            Log::warning('Pharmacy broadcast failed', [
                'event' => $event::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
