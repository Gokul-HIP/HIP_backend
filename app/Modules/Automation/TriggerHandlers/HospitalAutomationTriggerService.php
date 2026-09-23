<?php

namespace App\Modules\Automation\TriggerHandlers;

use App\Models\DoctorBooking;
use App\Models\Prescription;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Workflow\Support\NodeTypeNormalizer;

/**
 * Single trigger-handler for hospital automation.
 *
 * Normalizes the trigger, copies organization/hospital from the event's own
 * appointment or prescription when those ids are missing, then calls
 * AutomationEngine::handle() once. Workflow lookup and fan-out stay on the engine.
 */
class HospitalAutomationTriggerService
{
    public function __construct(
        protected AutomationEngine $automationEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $triggerType, array $payload = []): void
    {
        $canonical = NodeTypeNormalizer::normalize($triggerType);
        $payload = $this->preserveScopeFromPayload($payload);

        $this->automationEngine->handle($canonical, $payload);
    }

    /**
     * Copy org/hospital from the event's appointment or prescription only.
     * Does not query an unrelated or latest booking.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function preserveScopeFromPayload(array $payload): array
    {
        $appointment = $payload['appointment'] ?? null;

        if ($appointment instanceof DoctorBooking) {
            if ($this->missingId($payload, 'hospital_id') && $appointment->hospital_id) {
                $payload['hospital_id'] = (int) $appointment->hospital_id;
            }

            if ($this->missingId($payload, 'organization_id')) {
                $orgId = $this->organizationIdFromLoadedHospital($appointment);
                if ($orgId !== null) {
                    $payload['organization_id'] = $orgId;
                }
            }

            return $payload;
        }

        $prescription = $payload['prescription'] ?? null;

        if ($prescription instanceof Prescription) {
            if ($this->missingId($payload, 'hospital_id') && $prescription->hospital_id) {
                $payload['hospital_id'] = (int) $prescription->hospital_id;
            }

            if ($this->missingId($payload, 'organization_id')) {
                $orgId = $this->organizationIdFromLoadedHospital($prescription);
                if ($orgId !== null) {
                    $payload['organization_id'] = $orgId;
                }
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function missingId(array $payload, string $key): bool
    {
        return ! isset($payload[$key]) || $payload[$key] === null || $payload[$key] === '';
    }

    protected function organizationIdFromLoadedHospital(object $model): ?int
    {
        if (! method_exists($model, 'relationLoaded') || ! $model->relationLoaded('hospital')) {
            return null;
        }

        $hospital = $model->getRelation('hospital');
        $orgId = is_object($hospital) ? ($hospital->organization_id ?? null) : null;

        return $orgId ? (int) $orgId : null;
    }
}
