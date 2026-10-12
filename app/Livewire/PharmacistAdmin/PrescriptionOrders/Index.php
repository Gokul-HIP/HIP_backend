<?php

namespace App\Livewire\PharmacistAdmin\PrescriptionOrders;

use App\Enums\PharmacyOrderStatus;
use App\Enums\PharmacyPaymentStatus;
use App\Models\HIPUser;
use App\Models\PharmacyOrder;
use App\Services\PharmacistScopeService;
use App\Services\Pharmacy\PharmacyOrderService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $paymentStatus = '';

    public string $fromDate = '';

    public string $toDate = '';

    public ?int $statusOrderId = null;

    public string $statusOrderNumber = '';

    /** @var list<array{value: string, label: string}> */
    public array $statusChoices = [];

    public string $targetStatus = '';

    public string $statusReason = '';

    public string $deliveryPartner = '';

    public string $riderName = '';

    public string $trackingRef = '';

    public string $expectedDeliveryAt = '';

    public string $statusError = '';

    public ?int $deleteOrderId = null;

    public string $deleteOrderNumber = '';

    public string $deleteError = '';

    public string $notice = '';

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $user = auth()->user();
        if (! $user instanceof HIPUser) {
            return [];
        }

        $listeners = [];
        foreach (app(PharmacistScopeService::class)->pharmacyIds($user) as $pharmacyId) {
            $channel = 'pharmacy.'.$pharmacyId;
            $listeners["echo-private:{$channel},.PharmacyOrderCreated"] = 'refreshFromBroadcast';
            $listeners["echo-private:{$channel},.PharmacyOrderUpdated"] = 'refreshFromBroadcast';
        }

        return $listeners;
    }

    public function refreshFromBroadcast(): void
    {
        // Re-render the scoped list. Filters stay as they are.
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'status', 'paymentStatus', 'fromDate', 'toDate'], true)) {
            $this->resetPage();
        }
    }

    public function setKpi(string $key): void
    {
        $allowed = ['requested', 'in_review', 'pending_payment', 'processing', 'delivery_queue', 'delivered'];
        $this->status = in_array($key, $allowed, true) ? $key : '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->paymentStatus = '';
        $this->fromDate = '';
        $this->toDate = '';
        $this->resetPage();
    }

    #[On('po-open-status')]
    public function openStatus(int $id): void
    {
        $order = $this->findScoped($id);
        $this->statusOrderId = (int) $order->id;
        $this->statusOrderNumber = (string) $order->order_number;
        $this->statusChoices = self::manualMoves($order->status);
        $this->targetStatus = '';
        $this->statusReason = '';
        $this->deliveryPartner = '';
        $this->riderName = '';
        $this->trackingRef = '';
        $this->expectedDeliveryAt = '';
        $this->statusError = '';
        $this->notice = '';
    }

    public function closeStatus(): void
    {
        $this->statusOrderId = null;
        $this->statusError = '';
    }

    public function applyStatus(): void
    {
        if (! $this->statusOrderId) {
            return;
        }

        $order = $this->findScoped($this->statusOrderId);
        $target = $this->targetStatus;
        $allowed = array_column(self::manualMoves($order->status), 'value');

        if (! in_array($target, $allowed, true)) {
            $this->statusError = 'That status change is not available for this order.';

            return;
        }

        try {
            $actor = $this->pharmacist();

            if ($target === PharmacyOrderStatus::Dispatched->value) {
                $this->requireDispatchFields();
                // Phase 5: notify the patient after this status change.
                app(PharmacyOrderService::class)->markDispatched(
                    $order,
                    $actor,
                    trim($this->deliveryPartner),
                    trim($this->riderName),
                    trim($this->trackingRef),
                    Carbon::parse($this->expectedDeliveryAt)
                );
            } elseif (in_array($target, [PharmacyOrderStatus::Rejected->value, PharmacyOrderStatus::Cancelled->value], true)) {
                $reason = trim($this->statusReason);
                if ($reason === '') {
                    $this->statusError = 'A reason is required.';

                    return;
                }
                // Phase 5: notify the patient after this status change.
                app(PharmacyOrderService::class)->transition($order, $target, $actor, $reason);
            } else {
                // Phase 5: notify the patient after this status change.
                app(PharmacyOrderService::class)->transition($order, $target, $actor);
            }
        } catch (DomainException $exception) {
            $this->statusError = $exception->getMessage();

            return;
        } catch (ValidationException $exception) {
            $this->statusError = collect($exception->errors())->flatten()->first()
                ?: 'Please check the status details.';

            return;
        }

        $this->statusOrderId = null;
        $this->statusError = '';
        $this->notice = 'Status updated.';
    }

    #[On('po-confirm-delete')]
    public function confirmDelete(int $id): void
    {
        $order = $this->findScoped($id);
        $this->deleteOrderId = (int) $order->id;
        $this->deleteOrderNumber = (string) $order->order_number;
        $this->deleteError = '';
        $this->notice = '';
    }

    public function closeDelete(): void
    {
        $this->deleteOrderId = null;
        $this->deleteError = '';
    }

    public function deleteOrder(): void
    {
        if (! $this->deleteOrderId) {
            return;
        }

        $order = $this->findScoped($this->deleteOrderId);

        try {
            app(PharmacyOrderService::class)->softDelete($order, $this->pharmacist());
        } catch (DomainException $exception) {
            $this->deleteError = $exception->getMessage();

            return;
        }

        $this->deleteOrderId = null;
        $this->deleteError = '';
        $this->notice = 'Order deleted.';
    }

    /**
     * Manual moves from PharmacyOrderService's transition map.
     * pending_payment and processing are left to the payment flow.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function manualMoves(PharmacyOrderStatus|string $from): array
    {
        $status = $from instanceof PharmacyOrderStatus
            ? $from
            : PharmacyOrderStatus::tryFrom((string) $from);

        if (! $status) {
            return [];
        }

        $map = self::transitionMap();
        $excluded = [
            PharmacyOrderStatus::PendingPayment->value,
            PharmacyOrderStatus::Processing->value,
        ];
        $moves = [];

        foreach ($map[$status->value] ?? [] as $value) {
            if (in_array($value, $excluded, true)) {
                continue;
            }

            $target = PharmacyOrderStatus::tryFrom((string) $value);
            if (! $target) {
                continue;
            }

            $moves[] = [
                'value' => $target->value,
                'label' => $target->label(),
            ];
        }

        return $moves;
    }

    public function render()
    {
        $orders = $this->filteredQuery()
            ->with(['person', 'hipUser', 'pharmacy', 'hospital'])
            ->withCount([
                'comments as unread_patient_comments_count' => function (Builder $query): void {
                    $query->where('author_type', 'patient')->whereNull('read_at');
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.pharmacist-admin.prescription-orders.index', [
            'orders' => $orders,
            'kpis' => $this->kpiCards(),
            'statusOptions' => PharmacyOrderStatus::cases(),
            'paymentOptions' => PharmacyPaymentStatus::cases(),
            'filtersActive' => $this->filtersActive(),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function transitionMap(): array
    {
        $reflection = new \ReflectionClass(PharmacyOrderService::class);
        $constant = $reflection->getReflectionConstant('ALLOWED');

        if (! $constant) {
            return [];
        }

        $map = $constant->getValue();

        return is_array($map) ? $map : [];
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    private function kpiCards(): array
    {
        $base = $this->scopedQuery();

        return [
            ['key' => 'requested', 'label' => 'New', 'count' => (clone $base)->where('status', PharmacyOrderStatus::Requested->value)->count()],
            ['key' => 'in_review', 'label' => 'In review', 'count' => (clone $base)->where('status', PharmacyOrderStatus::InReview->value)->count()],
            ['key' => 'pending_payment', 'label' => 'Awaiting payment', 'count' => (clone $base)->where('status', PharmacyOrderStatus::PendingPayment->value)->count()],
            ['key' => 'processing', 'label' => 'Paid / processing', 'count' => (clone $base)->where('status', PharmacyOrderStatus::Processing->value)->count()],
            ['key' => 'delivery_queue', 'label' => 'Out for delivery', 'count' => (clone $base)->whereIn('status', [
                PharmacyOrderStatus::ReadyForDelivery->value,
                PharmacyOrderStatus::Dispatched->value,
                PharmacyOrderStatus::OutForDelivery->value,
            ])->count()],
            ['key' => 'delivered', 'label' => 'Completed', 'count' => (clone $base)->where('status', PharmacyOrderStatus::Delivered->value)->count()],
        ];
    }

    private function filteredQuery(): Builder
    {
        $query = $this->scopedQuery();
        $search = trim($this->search);

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function (Builder $inner) use ($like) {
                $inner->where('order_number', 'like', $like)
                    ->orWhere('delivery_name', 'like', $like)
                    ->orWhere('delivery_phone', 'like', $like)
                    ->orWhereHas('person', function (Builder $person) use ($like) {
                        $person->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('mobile', 'like', $like)
                            ->orWhereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?",
                                [$like]
                            );
                    })
                    ->orWhereHas('hipUser', function (Builder $user) use ($like) {
                        $user->where('hip_id', 'like', $like);
                    });
            });
        }

        if ($this->status === 'delivery_queue') {
            $query->whereIn('status', [
                PharmacyOrderStatus::ReadyForDelivery->value,
                PharmacyOrderStatus::Dispatched->value,
                PharmacyOrderStatus::OutForDelivery->value,
            ]);
        } elseif (PharmacyOrderStatus::tryFrom($this->status)) {
            $query->where('status', $this->status);
        }

        if (PharmacyPaymentStatus::tryFrom($this->paymentStatus)) {
            $query->where('payment_status', $this->paymentStatus);
        }

        if ($this->isDate($this->fromDate)) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->isDate($this->toDate)) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query;
    }

    private function scopedQuery(): Builder
    {
        $ids = app(PharmacistScopeService::class)->pharmacyIds($this->pharmacist());

        return PharmacyOrder::query()->whereIn('pharmacy_id', $ids);
    }

    private function findScoped(int $id): PharmacyOrder
    {
        $order = $this->scopedQuery()->whereKey($id)->first();

        if (! $order) {
            abort(404);
        }

        return $order;
    }

    private function pharmacist(): HIPUser
    {
        $user = auth()->user();

        if (! $user instanceof HIPUser) {
            abort(403);
        }

        return $user;
    }

    private function filtersActive(): bool
    {
        return trim($this->search) !== ''
            || $this->status !== ''
            || $this->paymentStatus !== ''
            || $this->fromDate !== ''
            || $this->toDate !== '';
    }

    private function requireDispatchFields(): void
    {
        $fields = [
            'deliveryPartner' => 'Delivery partner is required.',
            'riderName' => 'Rider name is required.',
            'trackingRef' => 'Tracking reference is required.',
            'expectedDeliveryAt' => 'Expected delivery time is required.',
        ];

        foreach ($fields as $property => $message) {
            if (trim((string) $this->{$property}) === '') {
                throw ValidationException::withMessages([
                    $property => [$message],
                ]);
            }
        }

        try {
            Carbon::parse($this->expectedDeliveryAt);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'expectedDeliveryAt' => ['Expected delivery time is not valid.'],
            ]);
        }
    }

    private function isDate(string $value): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $parsed = Carbon::createFromFormat('Y-m-d', $value);

        return $parsed instanceof Carbon && $parsed->format('Y-m-d') === $value;
    }
}
