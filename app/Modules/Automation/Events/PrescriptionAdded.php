<?php

namespace App\Modules\Automation\Events;

use App\Models\Prescription;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionAdded implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Prescription $prescription,
        public ?string $occurrenceId = null,
    ) {
        $this->occurrenceId = $occurrenceId ?: self::occurrenceIdFor($prescription);
    }

    public static function occurrenceIdFor(Prescription $prescription): string
    {
        return 'prescription-added:prescription:'.$prescription->id;
    }

    public function triggerType(): string
    {
        return 'prescriptionAdded';
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $this->prescription->loadMissing(['hospital']);

        return [
            'prescription' => $this->prescription,
            'prescription_id' => $this->prescription->id,
            'hospital_id' => $this->prescription->hospital_id,
            'organization_id' => $this->prescription->hospital?->organization_id,
            'event_occurrence_id' => $this->occurrenceId,
        ];
    }
}
