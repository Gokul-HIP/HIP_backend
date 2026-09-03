<?php

namespace App\Modules\HospitalAutomation\Testing;

use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;

class AutomationTestWorkflowFactory
{
    /**
     * @param  list<string>  $channels  Normalized: push|email|sms|whatsapp
     * @return array{workflow: Workflow, version: WorkflowVersion, ephemeral: bool}
     */
    public function resolve(
        string $trigger,
        array $channels,
        AutomationTestAccount $account,
        ?int $hospitalId,
        ?int $organizationId,
        ?int $workflowId = null,
    ): array {
        if ($workflowId !== null) {
            $workflow = Workflow::query()->with('currentVersion')->find($workflowId);

            if (! $workflow || ! $workflow->currentVersion) {
                throw new AutomationTestException("Published workflow {$workflowId} was not found.");
            }

            if ($workflow->source_type !== AutomationTestContextFactory::SOURCE) {
                throw new AutomationTestException(
                    "Workflow {$workflowId} is not marked source_type=automation_test. "
                    .'Refusing to run a production workflow. Use the default ephemeral path, '
                    .'or pass a workflow that was created for automation testing.'
                );
            }

            if ($workflow->status !== WorkflowStatus::Active->value) {
                throw new AutomationTestException("Workflow {$workflowId} is not active/published.");
            }

            return [
                'workflow' => $workflow,
                'version' => $workflow->currentVersion,
                'ephemeral' => false,
            ];
        }

        if ($hospitalId === null) {
            throw new AutomationTestException(
                'AUTOMATION_TEST_HOSPITAL_ID is required when creating an ephemeral test workflow.'
            );
        }

        $definition = $this->buildLinearMessagingGraph($trigger, $channels, $account);

        $workflow = Workflow::query()->create([
            'name' => '[automation_test] '.$trigger.' '.uniqid(),
            'status' => WorkflowStatus::Active->value,
            'trigger_type' => $trigger,
            'hospital_id' => $hospitalId,
            'organization_id' => $organizationId,
            // DB marker: excluded from WorkflowRepository::findPublishedByTrigger / production handle().
            'source_type' => AutomationTestContextFactory::SOURCE,
            'source_id' => $account->id,
        ]);

        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_number' => 1,
            'definition' => $definition,
            'compiled_graph' => null,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $workflow->update(['current_version_id' => $version->id]);

        return [
            'workflow' => $workflow->fresh(['currentVersion']),
            'version' => $version->fresh(),
            'ephemeral' => true,
        ];
    }

    public function cleanup(Workflow $workflow): void
    {
        $workflow->versions()->delete();
        $workflow->delete();
    }

    /**
     * @param  list<string>  $channels
     * @return array<string, mixed>
     */
    public function buildLinearMessagingGraph(
        string $trigger,
        array $channels,
        AutomationTestAccount $account,
    ): array {
        $nodes = [
            [
                'id' => 't1',
                'type' => 'workflow',
                'position' => ['x' => 0, 'y' => 0],
                'data' => ['nodeType' => $trigger],
            ],
        ];
        $edges = [];
        $previous = 't1';
        $index = 1;

        foreach ($channels as $channel) {
            $nodeId = 'a'.$index;
            $nodes[] = [
                'id' => $nodeId,
                'type' => 'workflow',
                'position' => ['x' => $index * 200, 'y' => 0],
                'data' => $this->actionNodeData($channel, $account),
            ];
            $edges[] = [
                'id' => 'e'.$index,
                'source' => $previous,
                'target' => $nodeId,
            ];
            $previous = $nodeId;
            $index++;
        }

        $endId = 'end1';
        $nodes[] = [
            'id' => $endId,
            'type' => 'workflow',
            'position' => ['x' => $index * 200, 'y' => 0],
            'data' => ['nodeType' => 'end'],
        ];
        $edges[] = [
            'id' => 'e'.$index,
            'source' => $previous,
            'target' => $endId,
        ];

        return [
            'builderVersion' => '1',
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    /**
     * Concrete recipients on each node — never logical "patient" fallback to a real person.
     *
     * @return array<string, mixed>
     */
    private function actionNodeData(string $channel, AutomationTestAccount $account): array
    {
        $marker = '[automation_test] account='.$account->id;

        return match ($channel) {
            'push' => [
                'nodeType' => 'sendPush',
                'title' => 'Automation Test Push',
                'body' => "{$marker} push",
                'recipient' => 'member',
            ],
            'email' => [
                'nodeType' => 'sendEmail',
                'subject' => 'Automation Test Email',
                'body' => "{$marker} email",
                'recipient' => $account->email,
            ],
            'sms' => [
                'nodeType' => 'sendSms',
                'body' => "{$marker} sms",
                'recipient' => $account->phone,
            ],
            'whatsapp' => [
                'nodeType' => 'sendWhatsApp',
                'body' => "{$marker} whatsapp",
                'recipient' => $account->whatsappNumber(),
            ],
            default => throw new AutomationTestException("Unsupported channel for graph: {$channel}"),
        };
    }
}
