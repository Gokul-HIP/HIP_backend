<?php

namespace App\Modules\Automation\Support;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\Persons;
use App\Models\SecondOpinion;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital and organization for a patient-scoped automation event that is not
 * raised by a single booking.
 *
 * Most recent valid booking wins. The timestamp is the booking row's created_at
 * (when the booking was recorded), compared across:
 * - doctor_bookings.hospital_id (branch_id when hospital_id is empty)
 * - diagnostic_test_bookings.branch_id
 * - second_opinions.branch_id
 *
 * Cancelled rows are skipped. Organization id is hospitals.organization_id for
 * the resolved hospital. When nothing resolves, the caller must not invent a
 * hospital: the workflow lookup then follows the existing unscoped rule
 * (workflows whose hospital_id is null only).
 */
final class PatientBookingScope
{
    /**
     * @return array{
     *     hospital_id: int,
     *     organization_id: int|null,
     *     source: string,
     *     booking_id: int|string,
     *     booked_at: string
     * }|null
     */
    public static function forHipUser(string $hipUserId): ?array
    {
        if ($hipUserId === '') {
            return null;
        }

        $personIds = Schema::hasTable('persons')
            ? Persons::query()->where('hip_user_id', $hipUserId)->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];

        $candidates = array_values(array_filter([
            self::latestDoctor($hipUserId, $personIds),
            self::latestDiagnostic($hipUserId, $personIds),
            self::latestSecondOpinion($hipUserId, $personIds),
        ]));

        if ($candidates === []) {
            return null;
        }

        usort($candidates, function (array $a, array $b): int {
            $byTime = strcmp($b['booked_at'], $a['booked_at']);
            if ($byTime !== 0) {
                return $byTime;
            }

            return strcmp((string) $b['booking_id'], (string) $a['booking_id']);
        });

        $winner = $candidates[0];
        $organizationId = null;
        if (Schema::hasTable('hospitals')) {
            $organizationId = Hospital::query()->whereKey($winner['hospital_id'])->value('organization_id');
            $organizationId = is_numeric($organizationId) ? (int) $organizationId : null;
        }

        return [
            'hospital_id' => (int) $winner['hospital_id'],
            'organization_id' => $organizationId,
            'source' => $winner['source'],
            'booking_id' => $winner['booking_id'],
            'booked_at' => $winner['booked_at'],
        ];
    }

    /**
     * @param  list<string>  $personIds
     * @return array{hospital_id: int, source: string, booking_id: int|string, booked_at: string}|null
     */
    private static function latestDoctor(string $hipUserId, array $personIds): ?array
    {
        if (! Schema::hasTable('doctor_bookings')) {
            return null;
        }

        $row = self::latestRow(DoctorBooking::query(), $hipUserId, $personIds);
        if ($row === null) {
            return null;
        }

        $hospitalId = $row->hospital_id ?: $row->branch_id;
        if (! $hospitalId || $row->created_at === null) {
            return null;
        }

        return self::candidate('doctor_booking', $row->id, (int) $hospitalId, (string) $row->created_at);
    }

    /**
     * @param  list<string>  $personIds
     * @return array{hospital_id: int, source: string, booking_id: int|string, booked_at: string}|null
     */
    private static function latestDiagnostic(string $hipUserId, array $personIds): ?array
    {
        if (! Schema::hasTable('diagnostic_test_bookings')) {
            return null;
        }

        $row = self::latestRow(DiagnosticTestBooking::query(), $hipUserId, $personIds);
        if ($row === null || ! $row->branch_id || $row->created_at === null) {
            return null;
        }

        return self::candidate('diagnostic_test_booking', $row->id, (int) $row->branch_id, (string) $row->created_at);
    }

    /**
     * @param  list<string>  $personIds
     * @return array{hospital_id: int, source: string, booking_id: int|string, booked_at: string}|null
     */
    private static function latestSecondOpinion(string $hipUserId, array $personIds): ?array
    {
        if (! Schema::hasTable('second_opinions')) {
            return null;
        }

        $row = self::latestRow(SecondOpinion::query(), $hipUserId, $personIds);
        if ($row === null || ! $row->branch_id || $row->created_at === null) {
            return null;
        }

        return self::candidate('second_opinion', $row->id, (int) $row->branch_id, (string) $row->created_at);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $personIds
     */
    private static function latestRow($query, string $hipUserId, array $personIds)
    {
        return $query
            ->where(function ($inner) use ($hipUserId, $personIds): void {
                $inner->where('member_id', $hipUserId);
                if ($personIds !== []) {
                    $inner->orWhereIn('patient_id', $personIds);
                }
            })
            ->where(function ($inner): void {
                $inner->whereNull('status')
                    ->orWhereRaw('LOWER(status) <> ?', ['cancelled']);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{hospital_id: int, source: string, booking_id: int|string, booked_at: string}
     */
    private static function candidate(string $source, mixed $bookingId, int $hospitalId, string $bookedAt): array
    {
        return [
            'hospital_id' => $hospitalId,
            'source' => $source,
            'booking_id' => $bookingId,
            'booked_at' => $bookedAt,
        ];
    }
}
