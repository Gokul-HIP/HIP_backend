<?php

namespace Tests\Unit;

use App\Modules\Workflow\NodeProcessors\AiPromptNodeProcessor;
use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\NodeProcessors\CreateRecordNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendSMSNodeProcessor;
use App\Modules\Workflow\NodeProcessors\UpdateRecordNodeProcessor;
use App\Modules\Workflow\NodeProcessors\DelayNodeProcessor;
use App\Modules\Workflow\Services\Builder\WorkflowBuilderService;
use App\Modules\Workflow\Services\Builder\WorkflowPublishService;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Tests\TestCase;

class NodeTypeNormalizerTest extends TestCase
{
    public function test_frontend_trigger_aliases_normalize_to_canonical_ids(): void
    {
        $this->assertSame('messageReceived', NodeTypeNormalizer::normalize('onChatMessage'));
        $this->assertSame('labReportReady', NodeTypeNormalizer::normalize('labReportNotification'));
        $this->assertSame('medicineRefillDue', NodeTypeNormalizer::normalize('pharmacyRefillDue'));
        $this->assertSame('rewardPointsUpdated', NodeTypeNormalizer::normalize('rewardUpdated'));
        $this->assertSame('rewardTierUpgraded', NodeTypeNormalizer::normalize('rewardsTierUpgraded'));
        $this->assertSame('anniversaryReached', NodeTypeNormalizer::normalize('anniversary'));
        $this->assertSame('medicineReminderDue', NodeTypeNormalizer::normalize('medicineReminder'));
    }

    public function test_frontend_action_aliases_normalize_to_canonical_ids(): void
    {
        $this->assertSame('delay', NodeTypeNormalizer::normalize('wait'));
        $this->assertSame('sendSMS', NodeTypeNormalizer::normalize('sendSms'));
        $this->assertSame('createRecord', NodeTypeNormalizer::normalize('dbCreate'));
        $this->assertSame('databaseUpdate', NodeTypeNormalizer::normalize('dbUpdate'));
        $this->assertSame('aiPrompt', NodeTypeNormalizer::normalize('ai'));
        $this->assertSame('webhook', NodeTypeNormalizer::normalize('httpRequest'));
        $this->assertSame('databaseUpdate', NodeTypeNormalizer::normalize('updateAppointment'));
        $this->assertSame('databaseUpdate', NodeTypeNormalizer::normalize('updatePrescription'));
        $this->assertSame('databaseUpdate', NodeTypeNormalizer::normalize('updateMembership'));
    }

    public function test_canonical_ids_are_idempotent(): void
    {
        $this->assertSame('messageReceived', NodeTypeNormalizer::normalize('messageReceived'));
        $this->assertSame(
            'messageReceived',
            NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize('onChatMessage'))
        );

        $this->assertSame('delay', NodeTypeNormalizer::normalize('delay'));
        $this->assertSame('sendSMS', NodeTypeNormalizer::normalize('sendSMS'));
        $this->assertSame('createRecord', NodeTypeNormalizer::normalize('createRecord'));
        $this->assertSame('databaseUpdate', NodeTypeNormalizer::normalize('databaseUpdate'));
        $this->assertSame('aiPrompt', NodeTypeNormalizer::normalize('aiPrompt'));

        $this->assertSame('delay', NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize('wait')));
        $this->assertSame('sendSMS', NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize('sendSms')));
        $this->assertSame('createRecord', NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize('dbCreate')));
        $this->assertSame('databaseUpdate', NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize('dbUpdate')));
        $this->assertSame('aiPrompt', NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize('ai')));
    }

    public function test_execution_node_normalizes_frontend_action_ids(): void
    {
        $cases = [
            'wait' => 'delay',
            'sendSms' => 'sendSMS',
            'dbCreate' => 'createRecord',
            'dbUpdate' => 'databaseUpdate',
            'ai' => 'aiPrompt',
        ];

        foreach ($cases as $frontendId => $canonical) {
            $node = ExecutionNode::fromReactFlowNode([
                'id' => "n_{$frontendId}",
                'type' => 'workflow',
                'data' => ['nodeType' => $frontendId],
            ]);

            $this->assertSame($canonical, $node->nodeType, "ExecutionNode failed for {$frontendId}");
        }
    }

    public function test_registry_resolves_frontend_aliases_to_same_executor_as_canonical(): void
    {
        $registry = $this->app->make(NodeProcessorRegistry::class);

        $pairs = [
            'wait' => ['delay', DelayNodeProcessor::class],
            'sendSms' => ['sendSMS', SendSMSNodeProcessor::class],
            'dbCreate' => ['createRecord', CreateRecordNodeProcessor::class],
            'dbUpdate' => ['databaseUpdate', UpdateRecordNodeProcessor::class],
            'ai' => ['aiPrompt', AiPromptNodeProcessor::class],
            'httpRequest' => ['webhook', \App\Modules\Workflow\NodeProcessors\WebhookNodeProcessor::class],
            'updateAppointment' => ['databaseUpdate', UpdateRecordNodeProcessor::class],
            'updatePrescription' => ['databaseUpdate', UpdateRecordNodeProcessor::class],
            'updateMembership' => ['databaseUpdate', UpdateRecordNodeProcessor::class],
        ];

        foreach ($pairs as $frontendId => [$canonical, $executorClass]) {
            $this->assertTrue($registry->has($frontendId), "Registry missing FE id {$frontendId}");
            $this->assertTrue($registry->has($canonical), "Registry missing canonical {$canonical}");

            $fromFe = $registry->get($frontendId);
            $fromCanonical = $registry->get($canonical);

            $this->assertSame($fromCanonical, $fromFe, "FE and canonical must resolve to same instance for {$frontendId}");
            $this->assertInstanceOf($executorClass, $fromFe);
            $this->assertSame($canonical, $fromFe->type());
        }

        $this->assertTrue($registry->has('dbQuery'));
        $this->assertInstanceOf(
            \App\Modules\Workflow\NodeProcessors\DbQueryNodeProcessor::class,
            $registry->get('dbQuery')
        );
        $this->assertFalse($registry->has('sendIvr'));
        $this->assertFalse($registry->has('start'));
        $this->assertTrue(NodeTypeNormalizer::isFrontendOnly('start'));
    }

    public function test_frontend_aliases_are_recognized_as_triggers(): void
    {
        $this->assertTrue(NodeTypeNormalizer::isTrigger('onChatMessage'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('labReportNotification'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('pharmacyRefillDue'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('paymentReceived'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('webhookEvent'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('apiEvent'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('userPlanExpiry'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('familyPackageTierUpdated'));
        $this->assertTrue(NodeTypeNormalizer::isTrigger('appointmentReminder'));
        $this->assertFalse(NodeTypeNormalizer::isTrigger('sendWhatsApp'));
        $this->assertFalse(NodeTypeNormalizer::isTrigger('workflow'));
    }

    public function test_detect_trigger_type_uses_normalized_frontend_id(): void
    {
        $service = $this->app->make(WorkflowBuilderService::class);

        $type = $service->detectTriggerType([
            'nodes' => [
                [
                    'id' => 'onChatMessage_0q3wx4e',
                    'type' => 'workflow',
                    'data' => [
                        'category' => 'triggers',
                        'nodeType' => 'onChatMessage',
                        'label' => 'On Message Received',
                    ],
                ],
            ],
        ]);

        $this->assertSame('messageReceived', $type);
    }

    public function test_publish_validator_accepts_on_chat_message_trigger(): void
    {
        $publisher = $this->app->make(WorkflowPublishService::class);

        $definition = [
            'nodes' => [
                [
                    'id' => 'onChatMessage_0q3wx4e',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => [
                        'category' => 'triggers',
                        'nodeType' => 'onChatMessage',
                        'label' => 'On Message Received',
                    ],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'onChatMessage_0q3wx4e', 'target' => 'end_1'],
            ],
        ];

        $validation = $publisher->validate($definition);

        $this->assertTrue($validation['valid'], json_encode($validation['errors']));
        $this->assertSame([], $validation['errors']);
    }

    public function test_compiler_resolves_on_chat_message_as_start_trigger(): void
    {
        $compiler = $this->app->make(WorkflowCompilerInterface::class);

        $compiled = $compiler->compile([
            'nodes' => [
                [
                    'id' => 'onChatMessage_0q3wx4e',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'onChatMessage', 'category' => 'triggers'],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'onChatMessage_0q3wx4e', 'target' => 'end_1'],
            ],
        ]);

        $start = $compiled->graph->nodes[$compiled->graph->startNodeId] ?? null;
        $this->assertNotNull($start);
        $this->assertSame('messageReceived', $start->nodeType);
        $this->assertSame('onChatMessage_0q3wx4e', $compiled->graph->startNodeId);
    }

    public function test_compiler_normalizes_frontend_wait_node_to_delay(): void
    {
        $compiler = $this->app->make(WorkflowCompilerInterface::class);

        $compiled = $compiler->compile([
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'appointmentBooked', 'category' => 'triggers'],
                ],
                [
                    'id' => 'w1',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'wait', 'type' => 'minutes', 'value' => 5],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'w1'],
                ['id' => 'e2', 'source' => 'w1', 'target' => 'end_1'],
            ],
        ]);

        $waitNode = $compiled->graph->nodes['w1'] ?? null;
        $this->assertNotNull($waitNode);
        $this->assertSame('delay', $waitNode->nodeType);
    }

    public function test_compiler_strips_frontend_start_node(): void
    {
        $compiler = $this->app->make(WorkflowCompilerInterface::class);

        $compiled = $compiler->compile([
            'nodes' => [
                [
                    'id' => 'n_start',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'start', 'category' => 'triggers'],
                ],
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'appointmentBooked', 'category' => 'triggers'],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'workflow',
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e0', 'source' => 'n_start', 'target' => 't1'],
                ['id' => 'e1', 'source' => 't1', 'target' => 'end_1'],
            ],
        ]);

        $this->assertArrayNotHasKey('n_start', $compiled->graph->nodes);
        $this->assertSame('t1', $compiled->graph->startNodeId);
        $this->assertSame('appointmentBooked', $compiled->graph->nodes['t1']->nodeType);
    }

    public function test_publish_rejects_send_ivr(): void
    {
        $publisher = $this->app->make(WorkflowPublishService::class);

        $validation = $publisher->validate([
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => ['nodeType' => 'appointmentBooked'],
                ],
                [
                    'id' => 'ivr1',
                    'type' => 'workflow',
                    'position' => ['x' => 100, 'y' => 0],
                    'data' => [
                        'nodeType' => 'sendIvr',
                        'templateId' => 'tpl_example',
                        'recipient' => 'patient',
                    ],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'ivr1'],
                ['id' => 'e2', 'source' => 'ivr1', 'target' => 'end_1'],
            ],
        ]);

        $this->assertFalse($validation['valid']);
        $this->assertSame('unsupported_node', $validation['errors'][0]['code'] ?? null);
        $this->assertStringContainsString('sendIvr', $validation['errors'][0]['message'] ?? '');
    }

    public function test_publish_accepts_http_request_alias(): void
    {
        $publisher = $this->app->make(WorkflowPublishService::class);

        $validation = $publisher->validate([
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => ['nodeType' => 'appointmentBooked'],
                ],
                [
                    'id' => 'h1',
                    'type' => 'workflow',
                    'position' => ['x' => 100, 'y' => 0],
                    'data' => ['nodeType' => 'httpRequest', 'url' => 'https://example.com/api'],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'h1'],
                ['id' => 'e2', 'source' => 'h1', 'target' => 'end_1'],
            ],
        ]);

        $this->assertTrue($validation['valid'], json_encode($validation['errors']));
    }
}
