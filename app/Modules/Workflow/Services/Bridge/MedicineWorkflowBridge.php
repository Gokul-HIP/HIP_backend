<?php

namespace App\Modules\Workflow\Services\Bridge;

use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Automation\Support\TriggerCatalog;

/**
 * Resolves Medicine Reminder workflows from the generic workflows table.
 * Replaces the legacy medicine_workflows sync bridge.
 */
class MedicineWorkflowBridge
{
    public const TRIGGER_TYPE = 'prescriptionAdded';

    /**
     * Active pharmacy / prescriptionAdded workflow for scheduling.
     */
    public function resolveActivePharmacyWorkflow(?int $organizationId = null): ?Workflow
    {
        $base = Workflow::query()
            ->where('status', WorkflowStatus::Active->value)
            ->where('trigger_type', self::TRIGGER_TYPE)
            ->whereHas('currentVersion')
            ->with('currentVersion');

        if ($organizationId) {
            $orgWorkflow = (clone $base)
                ->where('organization_id', $organizationId)
                ->latest('id')
                ->first();

            if ($orgWorkflow) {
                return $orgWorkflow;
            }
        }

        return (clone $base)
            ->whereNull('organization_id')
            ->latest('id')
            ->first()
            ?? $base->latest('id')->first();
    }

    public function resolvePublishedVersion(Workflow $workflow): ?WorkflowVersion
    {
        $workflow->loadMissing('currentVersion');

        $version = $workflow->currentVersion;

        if (! $version || $version->status !== 'published') {
            return null;
        }

        return $version;
    }

    /**
     * @return array<string, mixed>
     */
    public function definitionFor(Workflow $workflow): array
    {
        $version = $this->resolvePublishedVersion($workflow);

        return is_array($version?->definition) ? $version->definition : [];
    }

    public function isPharmacyModule(Workflow $workflow): bool
    {
        if (! $workflow->trigger_type) {
            return false;
        }

        return TriggerCatalog::moduleFor($workflow->trigger_type) === 'pharmacy';
    }
}
