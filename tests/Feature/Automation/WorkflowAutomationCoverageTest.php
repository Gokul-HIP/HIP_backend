<?php

namespace Tests\Feature\Automation;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Tests\Support\FrontendNodeCatalog;
use Tests\Support\WorkflowAutomationTestCase;

/**
 * Matrix-driven coverage: normalize → registry → compile → execute → end.
 * Does not require queue workers. External providers are mocked by the harness.
 */
class WorkflowAutomationCoverageTest extends WorkflowAutomationTestCase
{
    public function test_every_frontend_alias_normalizes_to_expected_canonical_id(): void
    {
        foreach (FrontendNodeCatalog::ALIASES as $frontendId => $canonical) {
            $this->assertSame(
                $canonical,
                NodeTypeNormalizer::normalize($frontendId),
                "Alias mismatch for {$frontendId}"
            );
            $this->assertSame(
                $canonical,
                NodeTypeNormalizer::normalize($canonical),
                "Canonical {$canonical} must be idempotent"
            );
            $this->assertSame(
                $canonical,
                NodeTypeNormalizer::normalize(NodeTypeNormalizer::normalize($frontendId)),
                "Double-normalize must stay {$canonical}"
            );
        }
    }

    public function test_all_frontend_catalog_ids_are_accounted_for(): void
    {
        $this->assertCount(46, FrontendNodeCatalog::ALL_FRONTEND_IDS);
        $this->assertSame(
            FrontendNodeCatalog::ALL_FRONTEND_IDS,
            array_values(array_unique(FrontendNodeCatalog::ALL_FRONTEND_IDS))
        );
    }

    public function test_implemented_nodes_resolve_expected_executor_classes_via_frontend_ids(): void
    {
        $registry = app(NodeProcessorRegistry::class);

        foreach (FrontendNodeCatalog::IMPLEMENTED_EXECUTORS as $frontendId => $executorClass) {
            $this->assertTrue(
                $registry->has($frontendId),
                "Registry missing FE id {$frontendId}"
            );

            $executor = $registry->get($frontendId);
            $this->assertInstanceOf($executorClass, $executor, "Wrong executor for {$frontendId}");

            $canonical = FrontendNodeCatalog::ALIASES[$frontendId] ?? $frontendId;
            $this->assertSame(
                $canonical,
                $executor->type(),
                "Executor type() must be canonical for {$frontendId}"
            );

            // FE id and canonical id must resolve to the same instance.
            $this->assertSame($executor, $registry->get($canonical));
        }
    }

    public function test_not_implemented_nodes_without_executor_are_absent_from_registry(): void
    {
        $registry = app(NodeProcessorRegistry::class);

        foreach (FrontendNodeCatalog::NOT_IMPLEMENTED_NO_EXECUTOR as $frontendId) {
            if ($frontendId === 'start') {
                $this->assertFalse($registry->has('start'));

                continue;
            }

            $this->assertFalse(
                $registry->has($frontendId),
                "Unexpected executor registered for not-implemented {$frontendId}"
            );
        }
    }

    /**
     * Partial / catalog triggers that still have Passthrough (or specialized) executors
     * must compile and complete trigger → end when started with that trigger type.
     *
     * @dataProvider passthroughTriggerProvider
     */
    public function test_passthrough_or_specialized_trigger_reaches_end(string $frontendTrigger): void
    {
        $registry = app(NodeProcessorRegistry::class);
        $this->assertTrue($registry->has($frontendTrigger), "No executor for {$frontendTrigger}");

        $canonical = NodeTypeNormalizer::normalize($frontendTrigger);
        $version = $this->publishDefinition(
            $this->triggerEndGraph($frontendTrigger),
            triggerType: $canonical,
        );

        $compiled = app(WorkflowCompilerInterface::class)->compile($version->definition);
        $start = $compiled->graph->nodes[$compiled->graph->startNodeId] ?? null;
        $this->assertNotNull($start);
        $this->assertSame($canonical, $start->nodeType);

        $execution = app(WorkflowExecutor::class)->start($version, $canonical, $this->sampleAppointmentContext());
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function passthroughTriggerProvider(): array
    {
        return [
            ['onChatMessage'],
            ['patientRegistered'],
            ['appointmentRescheduled'],
            ['appointmentCompleted'],
            ['appointmentCancelled'],
            ['appointmentMissed'],
            ['labTestOrdered'],
            ['labReportNotification'],
            ['pharmacyRefillDue'],
            ['birthday'],
            ['anniversary'],
            ['membershipExpiry'],
            ['rewardUpdated'],
            ['rewardsTierUpgraded'],
            ['invoiceGenerated'],
            ['paymentReceived'],
            ['scheduledEvent'],
            ['appointmentBooked'],
            // prescriptionAdded requires real prescription + MedicineReminderService — covered separately / skipped here
        ];
    }

    public function test_prescription_added_trigger_requires_prescription_context(): void
    {
        $version = $this->publishDefinition(
            $this->triggerEndGraph('prescriptionAdded'),
            triggerType: 'prescriptionAdded',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'prescriptionAdded',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('Prescription', (string) $execution->fresh()->failure_reason);
    }

    public function test_end_node_completes_minimal_graph(): void
    {
        $version = $this->publishDefinition($this->triggerEndGraph('appointmentBooked'));
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertNull($execution->fresh()->failure_reason);
    }

    public function test_not_implemented_action_nodes_are_explicitly_documented(): void
    {
        foreach (FrontendNodeCatalog::NOT_IMPLEMENTED_NO_EXECUTOR as $id) {
            if ($id === 'start') {
                $this->assertTrue(true);

                continue;
            }

            $this->assertContains(
                $id,
                FrontendNodeCatalog::ALL_FRONTEND_IDS,
                "{$id} missing from catalog list"
            );
        }

        foreach (FrontendNodeCatalog::NOT_IMPLEMENTED_DOMAIN as $id) {
            $this->assertContains($id, FrontendNodeCatalog::ALL_FRONTEND_IDS);
            // May have passthrough — domain incomplete, not missing registry entry.
            $this->assertTrue(app(NodeProcessorRegistry::class)->has($id));
        }
    }
}
