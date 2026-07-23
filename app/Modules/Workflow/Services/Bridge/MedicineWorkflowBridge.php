<?php

namespace App\Modules\Workflow\Services\Bridge;

use App\Modules\MedicineReminder\Models\MedicineWorkflow;
use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Support\NodeTypeNormalizer;

class MedicineWorkflowBridge
{
    public const SOURCE_TYPE = 'medicine_workflows';

    public function __construct(
        protected WorkflowRepository $workflowRepository,
        protected WorkflowCompilerInterface $compiler,
    ) {}

    public function syncFromMedicineWorkflow(MedicineWorkflow $medicineWorkflow): Workflow
    {
        $workflow = Workflow::query()->firstOrCreate(
            [
                'source_type' => self::SOURCE_TYPE,
                'source_id' => $medicineWorkflow->id,
            ],
            [
                'organization_id' => $medicineWorkflow->organization_id,
                'name' => $medicineWorkflow->name,
                'status' => $this->mapStatus($medicineWorkflow->status),
                'trigger_type' => $this->detectTriggerType($medicineWorkflow->configuration ?? []),
                'created_by' => $medicineWorkflow->created_by ?? null,
            ]
        );

        $workflow->update([
            'organization_id' => $medicineWorkflow->organization_id,
            'name' => $medicineWorkflow->name,
            'status' => $this->mapStatus($medicineWorkflow->status),
            'trigger_type' => $this->detectTriggerType($medicineWorkflow->configuration ?? []),
        ]);

        $definition = is_array($medicineWorkflow->configuration) ? $medicineWorkflow->configuration : [];

        if ($definition !== []) {
            $this->workflowRepository->publishVersion($workflow, $definition);
        }

        return $workflow->fresh(['currentVersion']);
    }

    public function resolveVersionForMedicineWorkflow(MedicineWorkflow $medicineWorkflow): ?WorkflowVersion
    {
        $workflow = $this->syncFromMedicineWorkflow($medicineWorkflow);

        return $workflow->currentVersion;
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'active' => WorkflowStatus::Active->value,
            'inactive' => WorkflowStatus::Inactive->value,
            default => WorkflowStatus::Draft->value,
        };
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    protected function detectTriggerType(array $configuration): ?string
    {
        foreach ($configuration['nodes'] ?? [] as $node) {
            if (! is_array($node)) {
                continue;
            }

            $data = is_array($node['data'] ?? null) ? $node['data'] : [];
            $nodeType = NodeTypeNormalizer::normalize((string) ($data['nodeType'] ?? $node['type'] ?? ''));

            if (NodeTypeNormalizer::isTrigger($nodeType)) {
                return $nodeType === 'medicineReminder' ? 'prescriptionAdded' : $nodeType;
            }
        }

        return 'prescriptionAdded';
    }
}
