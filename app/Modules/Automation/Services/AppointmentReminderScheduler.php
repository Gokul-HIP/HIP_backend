<?php

namespace App\Modules\Automation\Services;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\SecondOpinion;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Models\AppointmentReminderSchedule;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class AppointmentReminderScheduler
{
    private const TIMINGS = [
        'before_24h' => 24 * 60, 'before_12h' => 12 * 60, 'before_6h' => 6 * 60,
        'before_1h' => 60, 'on_time' => 0, 'at_time' => 0,
        '24 hours before' => 24 * 60, '24h' => 24 * 60, '24_hours' => 24 * 60,
        '12 hours before' => 12 * 60, '12h' => 12 * 60, '12_hours' => 12 * 60,
        '6 hours before' => 6 * 60, '6h' => 6 * 60, '6_hours' => 6 * 60,
        '1 hour before' => 60, '1h' => 60, '1_hour' => 60,
        'at appointment time' => 0, 'at_appointment_time' => 0, 'appointment_time' => 0,
    ];

    public function synchronizeAndDispatch(): int
    {
        $count = 0;
        foreach ($this->upcomingBookings() as [$type, $booking, $hospitalId, $date, $time]) {
            $hospital = Hospital::query()->with('organization')->find($hospitalId);
            if (! $hospital || ! $date || ! $time) continue;
            $timezone = $hospital->timezone ?? config('app.timezone');
            $time = preg_split('/\s+-\s+/', trim((string) $time))[0] ?? $time;
            try { $appointmentAt = CarbonImmutable::parse($date.' '.$time, $timezone); }
            catch (\Throwable) { continue; }
            if ($appointmentAt->lessThanOrEqualTo(now($timezone))) continue;
            $appointmentAt = $appointmentAt->utc();

            $workflows = Workflow::query()->where('hospital_id', $hospitalId)
                ->where('trigger_type', 'appointmentReminder')->where('status', 'active')
                ->where(function ($query) use ($hospital) {
                    $query->whereNull('organization_id')->orWhere('organization_id', $hospital->organization_id);
                })
                ->where(function ($query) {
                    $query->whereNull('source_type')->orWhere('source_type', '!=', 'automation_test');
                })
                ->whereNotNull('current_version_id')->with('currentVersion')->get();
            $expected = [];
            foreach ($workflows as $workflow) {
                $timing = $this->timing($workflow->currentVersion?->definition ?? []);
                if ($timing === null) continue;
                $primary = $appointmentAt->subMinutes($timing);
                $stages = [['primary', $primary]];
                if ($timing !== 60) $stages[] = ['one_hour_before', $appointmentAt->subHour()];
                foreach ($stages as [$stage, $when]) {
                    $identity = implode(':', [$type, $booking->id, $workflow->id, $workflow->current_version_id, $appointmentAt->timestamp, $stage]);
                    $existing = AppointmentReminderSchedule::query()->where('identity', $identity)->first();
                    // Do not create missed stages retroactively for a late booking. Keep an
                    // occurrence that was already scheduled pending so scheduler jitter does
                    // not cancel it before the due-dispatch query can claim it.
                    if ($when->lessThan(now($timezone)) && (! $existing || $existing->status !== 'pending')) continue;
                    $expected[] = $identity;
                    AppointmentReminderSchedule::query()->firstOrCreate(['identity' => $identity], [
                        'booking_type' => $type, 'booking_id' => $booking->id, 'workflow_id' => $workflow->id,
                        'workflow_version_id' => $workflow->current_version_id, 'hospital_id' => $hospitalId,
                        'organization_id' => $hospital->organization_id, 'appointment_at' => $appointmentAt,
                        'timezone' => $timezone,
                        'configured_timing' => $this->timingName($timing), 'stage' => $stage,
                        'scheduled_at' => $when, 'status' => 'pending',
                    ]);
                }
            }
            $stale = AppointmentReminderSchedule::query()->where('booking_type', $type)->where('booking_id', $booking->id)
                ->where('status', 'pending');
            if ($expected !== []) $stale->whereNotIn('identity', $expected);
            foreach ($stale->get() as $schedule) {
                $reason = $this->staleReason($schedule, $appointmentAt, $workflows, $timezone);
                $replacementAt = $reason === 'reminder_due_time_passed' ? null : $this->replacementTime($schedule, $workflows, $appointmentAt);
                $this->cancelSchedule($schedule, $reason, $replacementAt);
            }
        }

        $this->cancelIneligiblePending();

        // Recover claims abandoned by a crashed scheduler process. Normal
        // executions leave processing immediately after the workflow returns.
        AppointmentReminderSchedule::query()->where('status', 'processing')
            ->where('updated_at', '<=', now()->subMinutes(10))->update(['status' => 'pending']);

        $due = AppointmentReminderSchedule::query()->where('status', 'pending')->where('scheduled_at', '<=', now('UTC'))
            ->orderBy('scheduled_at')->orderBy('id')->limit(500)->get();
        foreach ($due as $schedule) {
            $claimed = AppointmentReminderSchedule::query()->whereKey($schedule->id)->where('status', 'pending')
                ->update(['status' => 'processing']);
            if (! $claimed) continue;
            $schedule->status = 'processing';
            try {
                $booking = match ($schedule->booking_type) {
                    'doctor' => DoctorBooking::query()->find($schedule->booking_id),
                    'second_opinion' => SecondOpinion::query()->find($schedule->booking_id),
                    'diagnostic_package' => DiagnosticTestBooking::query()->find($schedule->booking_id),
                    default => null,
                };
                if (! $booking || ! $this->eligible($booking) || ! $this->bookingMatchesSchedule($booking, $schedule)) {
                    $this->cancelSchedule($schedule, ! $booking ? 'booking_missing' : (! $this->eligible($booking) ? 'booking_ineligible' : 'appointment_time_or_hospital_changed'));
                    continue;
                }
                $workflow = Workflow::query()->with('currentVersion')->find($schedule->workflow_id);
                if (! $workflow || $workflow->status !== 'active' || $workflow->current_version_id !== $schedule->workflow_version_id
                    || (int) $workflow->hospital_id !== (int) $schedule->hospital_id) {
                    $this->cancelSchedule($schedule, ! $workflow ? 'workflow_missing_or_unpublished' : ($workflow->current_version_id !== $schedule->workflow_version_id ? 'workflow_version_changed' : 'workflow_hospital_changed'));
                    continue;
                }
                $context = match ($schedule->booking_type) {
                    'doctor' => app(AutomationContextBuilder::class)->fromAppointment($booking),
                    'second_opinion' => app(AutomationContextBuilder::class)->fromSecondOpinion($booking),
                    default => app(AutomationContextBuilder::class)->fromDiagnosticOrder($booking),
                };
                $context = $this->formatAppointmentTime($context, $schedule->timezone ?: config('app.timezone'));
                $context['event_occurrence_id'] = 'appointment-reminder:'.$schedule->identity;
                $context['reminder_stage'] = $schedule->stage;
                $context['configured_trigger_timing'] = $schedule->configured_timing;
                $context['hospital_id'] = $schedule->hospital_id;
                $execution = app(AutomationEngine::class)->executeWorkflow($workflow, 'appointmentReminder', $context);
                if (! $execution || $execution->status === WorkflowExecutionStatus::Failed->value) {
                    AppointmentReminderSchedule::query()->whereKey($schedule->id)
                        ->where('status', 'processing')->update(['status' => 'pending']);
                } else {
                    $schedule->update(['status' => 'dispatched']);
                    $count++;
                }
            } catch (\Throwable $e) {
                report($e);
                AppointmentReminderSchedule::query()->whereKey($schedule->id)
                    ->where('status', 'processing')->update(['status' => 'pending']);
            }
        }
        return $count;
    }

    private function timing(array $definition): ?int
    {
        foreach ($definition['nodes'] ?? [] as $node) {
            $data = $node['data'] ?? [];
            if (\App\Modules\Workflow\Support\NodeTypeNormalizer::normalize((string) ($data['nodeType'] ?? $node['type'] ?? '')) !== 'appointmentReminder') continue;
            $value = $data['triggerTiming'] ?? $data['timing'] ?? $data['reminderTiming'] ?? $data['configuration']['triggerTiming'] ?? null;
            return self::TIMINGS[strtolower(trim((string) $value))] ?? null;
        }
        return null;
    }

    private function timingName(int $minutes): string
    {
        return match ($minutes) {
            24 * 60 => 'before_24h',
            12 * 60 => 'before_12h',
            6 * 60 => 'before_6h',
            60 => 'before_1h',
            default => 'on_time',
        };
    }

    private function formatAppointmentTime(array $context, string $timezone): array
    {
        $time = $context['appointment_time'] ?? null;
        if (! is_string($time) || trim($time) === '') return $context;

        $time = preg_split('/\s+-\s+/', trim($time))[0] ?? $time;
        try {
            // Booking slots are hospital-local wall times; format in that zone without
            // converting the appointment instant or changing its date.
            $context['appointment_time'] = CarbonImmutable::parse($time, $timezone)->format('g:i A');
        } catch (\Throwable) {
            // Preserve the original value if a legacy slot cannot be parsed.
        }

        return $context;
    }

    private function eligible(object $booking): bool
    {
        $status = strtolower((string) ($booking->status ?? ''));
        if (in_array($status, ['cancelled', 'completed', 'complete', 'missed', 'rejected'], true)) return false;
        return ! ($booking instanceof DoctorBooking && in_array(strtolower((string) $booking->appointment_status), ['cancelled', 'completed'], true));
    }

    private function cancelIneligiblePending(): void
    {
        AppointmentReminderSchedule::query()->where('status', 'pending')->orderBy('id')->chunkById(250, function ($schedules): void {
            foreach ($schedules as $schedule) {
                $booking = match ($schedule->booking_type) {
                    'doctor' => DoctorBooking::query()->find($schedule->booking_id),
                    'second_opinion' => SecondOpinion::query()->find($schedule->booking_id),
                    'diagnostic_package' => DiagnosticTestBooking::query()->find($schedule->booking_id),
                    default => null,
                };
                if (! $booking || ! $this->eligible($booking) || ! $this->bookingMatchesSchedule($booking, $schedule)) {
                    $this->cancelSchedule($schedule, ! $booking ? 'booking_missing' : (! $this->eligible($booking) ? 'booking_ineligible' : 'appointment_time_or_hospital_changed'));
                }
            }
        });
    }

    private function staleReason(AppointmentReminderSchedule $schedule, CarbonImmutable $appointmentAt, $workflows, string $timezone): string
    {
        if (! $schedule->appointment_at?->equalTo($appointmentAt)) return 'appointment_time_changed';
        $workflow = $workflows->firstWhere('id', $schedule->workflow_id);
        if (! $workflow) return 'workflow_unpublished_or_out_of_scope';
        if ((int) $workflow->current_version_id !== (int) $schedule->workflow_version_id) return 'workflow_configuration_changed';
        if ($schedule->scheduled_at?->lessThan(now($timezone))) return 'reminder_due_time_passed';
        return 'workflow_configuration_invalid_or_stage_removed';
    }

    private function replacementTime(AppointmentReminderSchedule $schedule, $workflows, CarbonImmutable $appointmentAt): ?string
    {
        $workflow = $workflows->firstWhere('id', $schedule->workflow_id);
        if (! $workflow) return null;
        $timing = $this->timing($workflow->currentVersion?->definition ?? []);
        if ($timing === null) return null;
        $when = $schedule->stage === 'one_hour_before' ? $appointmentAt->subHour() : $appointmentAt->subMinutes($timing);
        return $when->utc()->toIso8601String();
    }

    private function cancelSchedule(AppointmentReminderSchedule $schedule, string $reason, ?string $newScheduledAt = null): void
    {
        $changed = AppointmentReminderSchedule::query()->whereKey($schedule->id)
            ->whereIn('status', ['pending', 'processing'])->update(['status' => 'cancelled']);
        if (! $changed) return;
        Log::info('Appointment reminder schedule cancelled', [
            'schedule_id' => $schedule->id,
            'appointment_id' => $schedule->booking_id,
            'booking_type' => $schedule->booking_type,
            'workflow_id' => $schedule->workflow_id,
            'reminder_type' => $schedule->stage,
            'old_scheduled_at' => $schedule->scheduled_at?->utc()->toIso8601String(),
            'new_scheduled_at' => $newScheduledAt,
            'old_status' => $schedule->status,
            'new_status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }

    private function bookingMatchesSchedule(object $booking, AppointmentReminderSchedule $schedule): bool
    {
        $hospitalId = $booking instanceof DoctorBooking ? $booking->hospital_id : $booking->branch_id;
        if ((int) $hospitalId !== (int) $schedule->hospital_id) return false;
        $date = $booking instanceof DoctorBooking || $booking instanceof DiagnosticTestBooking
            ? $booking->booking_date : $booking->preferred_date;
        $slots = $booking instanceof DoctorBooking || $booking instanceof DiagnosticTestBooking
            ? $booking->required_time_slots : $booking->preferred_time_slots;
        $time = is_array($slots) ? ($slots[0] ?? null) : null;
        if (! $date || ! $time) return false;
        $time = preg_split('/\s+-\s+/', trim((string) $time))[0] ?? $time;
        $timezone = Hospital::query()->find($hospitalId)?->timezone ?? config('app.timezone');
        try {
            return CarbonImmutable::parse($date->format('Y-m-d').' '.$time, $timezone)->utc()->equalTo($schedule->appointment_at);
        } catch (\Throwable) {
            return false;
        }
    }

    private function upcomingBookings(): iterable
    {
        foreach (DoctorBooking::query()->whereIn('status', ['pending', 'confirmed'])->whereNotNull('hospital_id')->whereDate('booking_date', '>=', today()->subDay())->get() as $b)
            yield ['doctor', $b, $b->hospital_id, $b->booking_date?->format('Y-m-d'), $b->required_time_slots[0] ?? null];
        foreach (SecondOpinion::query()->whereIn('status', ['pending', 'confirmed'])->whereNotNull('branch_id')->whereDate('preferred_date', '>=', today()->subDay())->get() as $b)
            yield ['second_opinion', $b, $b->branch_id, $b->preferred_date?->format('Y-m-d'), $b->preferred_time_slots[0] ?? null];
        foreach (DiagnosticTestBooking::query()->whereIn('status', ['pending', 'confirmed'])->whereNotNull('branch_id')->whereNotNull('package_id')->whereDate('booking_date', '>=', today()->subDay())->get() as $b)
            yield ['diagnostic_package', $b, $b->branch_id, $b->booking_date?->format('Y-m-d'), $b->required_time_slots[0] ?? null];
    }
}
