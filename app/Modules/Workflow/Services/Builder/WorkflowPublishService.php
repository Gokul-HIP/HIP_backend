<?php

namespace App\Modules\Workflow\Services\Builder;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use InvalidArgumentException;

class WorkflowPublishService
{
    public function __construct(
        protected WorkflowCompilerInterface $compiler,
        protected WorkflowRepository $workflowRepository,
        protected WorkflowBuilderService $builderService,
    ) {}

    /**
     * @return array{version: WorkflowVersion, validation: array{valid: bool, errors: array<int, array{code: string, message: string}>, warnings: array<int, array{code: string, message: string}>}}
     */
    public function publish(Workflow $workflow, ?string $actorId = null, ?string $versionNotes = null): array
    {
        $workflow->loadMissing(['draftVersion', 'currentVersion']);

        $definition = $workflow->draftVersion?->definition;

        if (! is_array($definition) || $definition === []) {
            throw new InvalidArgumentException('Cannot publish workflow without a saved draft configuration.');
        }

        if ($workflow->hospital_id) {
            $hospitalExists = \App\Models\Hospital::query()->whereKey($workflow->hospital_id)->exists();
            if (! $hospitalExists) {
                throw new InvalidArgumentException('Cannot publish workflow: hospital is invalid.');
            }
        }

        $validation = $this->validate($definition);

        if (! $validation['valid']) {
            throw new InvalidArgumentException(
                'Workflow validation failed: '.collect($validation['errors'])->pluck('message')->implode('; ')
            );
        }

        $version = $this->workflowRepository->publishVersion(
            $workflow,
            $definition,
            $actorId,
            $versionNotes
        );

        return [
            'version' => $version->fresh(),
            'validation' => $validation,
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{valid: bool, errors: array<int, array{code: string, message: string}>, warnings: array<int, array{code: string, message: string}>}
     */
    public function validate(array $definition): array
    {
        $errors = [];
        $warnings = [];

        try {
            $compiled = $this->compiler->compile($definition);
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'errors' => [['code' => 'compile_failed', 'message' => $e->getMessage()]],
                'warnings' => [],
            ];
        }

        $triggerCount = 0;
        $endCount = 0;

        foreach ($definition['nodes'] ?? [] as $node) {
            if (! is_array($node)) {
                continue;
            }

            $data = is_array($node['data'] ?? null) ? $node['data'] : [];
            $nodeType = NodeTypeNormalizer::normalize((string) ($data['nodeType'] ?? $node['type'] ?? ''));

            if (NodeTypeNormalizer::isTrigger($nodeType)) {
                $triggerCount++;
            }

            if (NodeTypeNormalizer::isEnd($nodeType)) {
                $endCount++;
            }
        }

        if ($triggerCount === 0) {
            $errors[] = ['code' => 'missing_trigger', 'message' => 'Workflow must contain exactly one trigger node.'];
        } elseif ($triggerCount > 1) {
            $errors[] = ['code' => 'multiple_triggers', 'message' => 'Workflow must contain only one trigger node.'];
        }

        if ($endCount === 0) {
            $errors[] = ['code' => 'missing_end', 'message' => 'Workflow must contain at least one end node.'];
        }

        if ($compiled->graph->hasLoops) {
            $warnings[] = ['code' => 'infinite_loops', 'message' => 'Workflow graph contains loops.'];
        }

        if (($definition['edges'] ?? []) === []) {
            $errors[] = ['code' => 'disconnected_graph', 'message' => 'Workflow must have connected edges.'];
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
