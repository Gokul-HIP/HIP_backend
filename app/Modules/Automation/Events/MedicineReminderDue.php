<?php

namespace App\Modules\Automation\Events;

use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicineReminderDue implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MedicineReminderSchedule $schedule,
        public ?string $occurrenceId = null,
    ) {
        $this->occurrenceId = $occurrenceId ?: self::occurrenceIdFor($schedule);
    }

    public static function occurrenceIdFor(MedicineReminderSchedule $schedule): string
    {
        return 'medicine-reminder-due:schedule:'.$schedule->id;
    }

    public function triggerType(): string
    {
        return 'medicineReminderDue';
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $schedule = $this->schedule;
        $schedule->loadMissing([
            'patient',
            'prescription.doctor',
            'prescription.hospital.organization',
            'prescription.member',
        ]);

        $prescription = $schedule->prescription;
        $medications = array_values($prescription?->medications ?? []);
        $itemId = (int) ($schedule->prescription_item_id ?? 0);
        $medication = self::medicationForItem($medications, $itemId);

        return [
            'schedule' => $schedule,
            'schedule_id' => $schedule->id,
            'scheduled_at' => $schedule->scheduled_at,
            'prescription' => $prescription,
            'prescription_id' => $schedule->prescription_id,
            'prescription_item_id' => $schedule->prescription_item_id,
            'medicine_id' => $schedule->medicine_id ?? ($medication['medicine_id'] ?? null),
            'medicine_name' => $medication['name'] ?? $medication['medicine_name'] ?? null,
            'dosage' => $medication['dosage'] ?? null,
            'frequency' => $medication['frequency'] ?? null,
            'duration' => $medication['duration'] ?? null,
            'when_to_take' => $medication['when_to_take'] ?? null,
            'medication' => $medication,
            'patient' => $schedule->patient ?? $prescription?->patient,
            'patient_id' => $schedule->patient_id ?? $prescription?->patient_id,
            'member_id' => $prescription?->member_id,
            'doctor_id' => $prescription?->doctor_id,
            'hospital' => $prescription?->hospital,
            'hospital_id' => $prescription?->hospital_id,
            'organization' => $prescription?->hospital?->organization,
            'organization_id' => $prescription?->hospital?->organization_id,
            'patient_mobile' => $schedule->patient?->mobile ?? $prescription?->patient?->mobile,
            'patient_email' => $schedule->patient?->email
                ?? $prescription?->patient?->email
                ?? $prescription?->member?->email,
            'event_occurrence_id' => $this->occurrenceId,
            'meta' => [
                'schedule_id' => (string) $schedule->id,
                'prescription_id' => (string) ($schedule->prescription_id ?? ''),
            ],
        ];
    }

    /**
     * @param  list<mixed>  $medications
     * @return array<string, mixed>
     */
    public static function medicationForItem(array $medications, int $itemId): array
    {
        foreach (array_values($medications) as $index => $medication) {
            if (! is_array($medication)) {
                continue;
            }

            $id = isset($medication['prescription_item_id']) && is_numeric($medication['prescription_item_id'])
                ? (int) $medication['prescription_item_id']
                : $index;

            if ($id === $itemId) {
                return $medication;
            }
        }

        $fallback = $medications[$itemId] ?? [];

        return is_array($fallback) ? $fallback : [];
    }
}
