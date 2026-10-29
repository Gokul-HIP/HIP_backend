<?php

namespace App\Models;

use App\Exceptions\HmsClientException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HmsBooking extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending_payment';

    public const STATUS_HELD = 'held_mismatch';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const TYPE_DOCTOR = 'doctor';

    public const TYPE_SECOND_OPINION = 'second_opinion';

    public const TYPE_PACKAGE = 'package';

    public const REFUND_DUE = 'refund_due';

    /**
     * A booking this integration created. An unknown HIP id is not found, including an app booking or another integration's booking.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function findForClient(int|string $integrationId, array $payload, bool $lock = false): self
    {
        $hmsId = trim((string) ($payload['hms_booking_id'] ?? ''));
        $type = trim((string) ($payload['booking_type'] ?? ''));
        $localRaw = $payload['booking_id'] ?? null;
        $hasHms = $hmsId !== '';
        $hasLocal = $type !== '' || ($localRaw !== null && $localRaw !== '');

        if (! $hasHms && ! $hasLocal) {
            throw new HmsClientException('validation_error', 'hms_booking_id or booking_type and booking_id is required.', 422);
        }
        if ($hasLocal && ($type === '' || $localRaw === null || $localRaw === '' || (string) (int) $localRaw !== (string) $localRaw)) {
            throw new HmsClientException('validation_error', 'booking_type and booking_id are required together.', 422);
        }
        if ($hasLocal && ! in_array($type, [self::TYPE_DOCTOR, self::TYPE_SECOND_OPINION, self::TYPE_PACKAGE], true)) {
            throw new HmsClientException('validation_error', 'booking_type must be doctor, second_opinion, or package.', 422);
        }

        $base = static::query()->where('integration_id', $integrationId);
        $byHms = $hasHms ? (clone $base)->where('hms_booking_id', $hmsId)->first() : null;
        $byLocal = $hasLocal
            ? (clone $base)->where('booking_type', $type)->where('booking_id', (int) $localRaw)->first()
            : null;

        if ($hasHms && $hasLocal) {
            if ($byHms && $byLocal && (int) $byHms->getKey() === (int) $byLocal->getKey()) {
                $chosen = $byHms;
            } elseif ($byHms || $byLocal) {
                throw new HmsClientException('validation_error', 'hms_booking_id and booking_id do not refer to the same booking.', 422);
            } else {
                throw new HmsClientException('booking_not_found', 'Booking was not found.', 404);
            }
        } else {
            $chosen = $hasHms ? $byHms : $byLocal;
        }

        if (! $chosen) {
            throw new HmsClientException('booking_not_found', 'Booking was not found.', 404);
        }
        if (! $lock) {
            return $chosen;
        }

        $locked = static::query()->whereKey($chosen->getKey())->lockForUpdate()->first();
        if (! $locked) {
            throw new HmsClientException('booking_not_found', 'Booking was not found.', 404);
        }

        return $locked;
    }

    /**
     * @return array{status: string, amount: float}|null
     */
    public function refundPayload(): ?array
    {
        if ($this->refund_status === null) {
            return null;
        }

        return [
            'status' => (string) $this->refund_status,
            'amount' => round((float) $this->refund_amount, 2),
        ];
    }

    /** HMS-facing state. Internal held_mismatch stays on the row and is reported as pending_payment. */
    public function clientState(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => self::STATUS_CONFIRMED,
            self::STATUS_EXPIRED => self::STATUS_EXPIRED,
            self::STATUS_CANCELLED => self::STATUS_CANCELLED,
            default => self::STATUS_PENDING,
        };
    }

    protected $fillable = [
        'integration_id',
        'hms_booking_id',
        'booking_type',
        'booking_id',
        'hip_user_id',
        'person_id',
        'hospital_id',
        'status',
        'expected_amount',
        'hms_payable_amount',
        'price_mismatch',
        'pricing_snapshot',
        'request_hash',
        'invoice_id',
        'transaction_id',
        'hold_expires_at',
        'confirmed_at',
        'cancelled_at',
        'cancel_reason',
        'refund_status',
        'refund_amount',
        'coins_reversed',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'hms_payable_amount' => 'decimal:2',
        'price_mismatch' => 'boolean',
        'pricing_snapshot' => 'array',
        'hold_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'coins_reversed' => 'integer',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(HmsIntegration::class, 'integration_id');
    }
}
