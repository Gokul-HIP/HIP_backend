<?php

namespace App\Modules\Automation\Services;

use App\Models\DoctorBooking;
use App\Modules\Automation\Events\AppointmentMissed;
use App\Modules\Automation\Support\AfterCommit;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MissedAppointmentDetector
{
    public const CHUNK_SIZE = 100;

    /**
     * @return array{marked: int, skipped: int}
     */
    public function detectAndDispatch(?Carbon $now = null): array
    {
        $now = ($now ?? now())->timezone((string) config('app.timezone'));
        $marked = 0;
        $skipped = 0;

        $this->candidateQuery($now)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($bookings) use ($now, &$marked, &$skipped): void {
                foreach ($bookings as $booking) {
                    $result = $this->processBooking($booking, $now);

                    if ($result === 'marked') {
                        $marked++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    }
                }
            });

        return ['marked' => $marked, 'skipped' => $skipped];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<DoctorBooking>
     */
    protected function candidateQuery(Carbon $now)
    {
        return DoctorBooking::query()
            ->where('status', DoctorBooking::STATUS_CONFIRMED)
            ->where(function ($query) {
                $query->whereNull('appointment_status')
                    ->orWhereNotIn('appointment_status', [
                        DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
                        DoctorBooking::APPOINTMENT_STATUS_COMPLETED,
                        DoctorBooking::APPOINTMENT_STATUS_CANCELLED,
                    ]);
            })
            ->whereNotNull('booking_date')
            ->whereDate('booking_date', '<=', $now->toDateString());
    }

    /**
     * @return 'marked'|'skipped'|'ineligible'
     */
    public function processBooking(DoctorBooking $booking, Carbon $now): string
    {
        $scheduled = $this->resolveScheduledAt($booking);

        if ($scheduled === null) {
            return 'skipped';
        }

        if (! $scheduled->lt($now)) {
            return 'ineligible';
        }

        return DB::transaction(function () use ($booking, $scheduled) {
            $locked = DoctorBooking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || ! $this->isEligible($locked)) {
                return 'ineligible';
            }

            $fromStatus = (string) $locked->status;
            $locked->status = DoctorBooking::STATUS_MISSED;
            $locked->save();

            AfterCommit::run(function () use ($locked, $scheduled, $fromStatus): void {
                Log::info('[appointment-missed] Booking marked missed; dispatching', [
                    'appointment_id' => $locked->id,
                    'hospital_id' => $locked->hospital_id,
                    'appointment_datetime' => $scheduled->toDateTimeString(),
                    'from_status' => $fromStatus,
                    'status' => $locked->status,
                ]);

                AppointmentMissed::dispatch($locked);
            });

            return 'marked';
        });
    }

    public function isEligible(DoctorBooking $booking): bool
    {
        if ($booking->status !== DoctorBooking::STATUS_CONFIRMED) {
            return false;
        }

        $appointmentStatus = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;

        if (in_array($appointmentStatus, [
            DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
            DoctorBooking::APPOINTMENT_STATUS_COMPLETED,
            DoctorBooking::APPOINTMENT_STATUS_CANCELLED,
        ], true)) {
            return false;
        }

        return true;
    }

    public function resolveScheduledAt(DoctorBooking $booking): ?Carbon
    {
        if ($booking->booking_date === null) {
            $this->logSkip($booking, 'missing_booking_date');

            return null;
        }

        $slots = $booking->required_time_slots;

        if (! is_array($slots) || $slots === [] || ! isset($slots[0]) || trim((string) $slots[0]) === '') {
            $this->logSkip($booking, 'missing_appointment_time');

            return null;
        }

        $date = $booking->booking_date->format('Y-m-d');
        $time = trim((string) $slots[0]);
        $timezone = (string) config('app.timezone');
        $scheduled = $this->parseDateAndTime($date, $time, $timezone);

        if ($scheduled === null) {
            $this->logSkip($booking, 'unparseable_appointment_time', $time);

            return null;
        }

        return $scheduled;
    }

    protected function parseDateAndTime(string $date, string $time, string $timezone): ?Carbon
    {
        $time = preg_replace('/\s+/', ' ', trim($time)) ?? trim($time);
        $formats = ['g:i A', 'g:iA', 'h:i A', 'h:iA', 'G:i', 'H:i', 'H:i:s', 'g:i:s A'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat('Y-m-d '.$format, $date.' '.$time, $timezone);
            } catch (InvalidFormatException|Throwable) {
                continue;
            }

            if ($parsed instanceof Carbon && $parsed->format('Y-m-d') === $date) {
                return $parsed;
            }
        }

        return null;
    }

    protected function logSkip(
        DoctorBooking $booking,
        string $reason,
        ?string $time = null,
        ?string $error = null
    ): void {
        Log::warning('[appointment-missed] Skipping booking with invalid schedule', array_filter([
            'appointment_id' => $booking->id,
            'hospital_id' => $booking->hospital_id,
            'reason' => $reason,
            'time' => $time,
            'error' => $error,
        ], fn ($value) => $value !== null && $value !== ''));
    }
}
