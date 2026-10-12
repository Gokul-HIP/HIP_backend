<?php

namespace App\Livewire\PharmacistAdmin\PrescriptionOrders\Concerns;

use App\Enums\PharmacyOrderStatus;
use App\Models\HIPUser;
use App\Models\PharmacyOrder;
use App\Services\Pharmacy\PharmacyOrderService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Validation\ValidationException;

trait HandlesStatusChange
{
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

    public string $notice = '';

    abstract protected function findScoped(int $id): PharmacyOrder;

    abstract protected function pharmacist(): HIPUser;

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
        $this->afterStatusChanged();
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

    protected function afterStatusChanged(): void
    {
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
}
