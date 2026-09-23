<?php

namespace App\Modules\Workflow\Support;

/**
 * Allowlisted domain records for generic workflow query/update nodes.
 * Not workflow-specific. Hospital/member isolation is applied by executors.
 *
 * @phpstan-type EntitySpec array{
 *     table: string,
 *     idColumn: string,
 *     hospitalColumn: ?string,
 *     memberColumn: ?string,
 *     contextIdKeys: list<string>
 * }
 */
final class DomainRecordMap
{
    /**
     * @var array<string, EntitySpec>
     */
    private const MAP = [
        'appointment' => [
            'table' => 'doctor_bookings',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'memberColumn' => null,
            'contextIdKeys' => ['appointment_id', 'appointment.id'],
        ],
        'doctor_bookings' => [
            'table' => 'doctor_bookings',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'memberColumn' => null,
            'contextIdKeys' => ['appointment_id', 'appointment.id'],
        ],
        'updateAppointment' => [
            'table' => 'doctor_bookings',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'memberColumn' => null,
            'contextIdKeys' => ['appointment_id', 'appointment.id'],
        ],
        'prescription' => [
            'table' => 'prescriptions',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'memberColumn' => null,
            'contextIdKeys' => ['prescription_id', 'prescription.id'],
        ],
        'prescriptions' => [
            'table' => 'prescriptions',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'memberColumn' => null,
            'contextIdKeys' => ['prescription_id', 'prescription.id'],
        ],
        'updatePrescription' => [
            'table' => 'prescriptions',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'memberColumn' => null,
            'contextIdKeys' => ['prescription_id', 'prescription.id'],
        ],
        'membership' => [
            'table' => 'user_family_subscriptions',
            'idColumn' => 'id',
            'hospitalColumn' => null,
            'memberColumn' => 'hip_user_id',
            'contextIdKeys' => ['membership_id', 'subscription_id', 'member_id'],
        ],
        'user_family_subscriptions' => [
            'table' => 'user_family_subscriptions',
            'idColumn' => 'id',
            'hospitalColumn' => null,
            'memberColumn' => 'hip_user_id',
            'contextIdKeys' => ['membership_id', 'subscription_id', 'member_id'],
        ],
        'updateMembership' => [
            'table' => 'user_family_subscriptions',
            'idColumn' => 'id',
            'hospitalColumn' => null,
            'memberColumn' => 'hip_user_id',
            'contextIdKeys' => ['membership_id', 'subscription_id', 'member_id'],
        ],
        'patient' => [
            'table' => 'persons',
            'idColumn' => 'id',
            'hospitalColumn' => null,
            'memberColumn' => null,
            'contextIdKeys' => ['patient_id', 'patient.id'],
        ],
        'persons' => [
            'table' => 'persons',
            'idColumn' => 'id',
            'hospitalColumn' => null,
            'memberColumn' => null,
            'contextIdKeys' => ['patient_id', 'patient.id'],
        ],
    ];

    public static function resolveKey(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $key = trim($raw);

        if ($key === '') {
            return null;
        }

        return array_key_exists($key, self::MAP) ? $key : null;
    }

    /**
     * @return EntitySpec|null
     */
    public static function spec(?string $key): ?array
    {
        $resolved = self::resolveKey($key);

        return $resolved === null ? null : self::MAP[$resolved];
    }

    public static function isSafeIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name);
    }
}
