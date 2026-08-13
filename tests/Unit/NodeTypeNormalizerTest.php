<?php

namespace Tests\Unit;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Services\Builder\WorkflowBuilderService;
use App\Modules\Workflow\Services\Builder\WorkflowPublishService;
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

    public function test_canonical_ids_are_idempotent(): void
    {
        $this->assertSame('messageReceived', NodeTypeNormalizer::normalize('messageReceived'));
        $this->assertSame(
            'messageReceived',
            NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize('onChatMessage'))
        );
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
}
