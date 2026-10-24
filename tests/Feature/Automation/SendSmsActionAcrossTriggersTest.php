<?php

namespace Tests\Feature\Automation;

use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Tests\Support\WorkflowAutomationTestCase;

class SendSmsActionAcrossTriggersTest extends WorkflowAutomationTestCase
{
    public function test_send_sms_resolves_each_trigger_context_through_the_shared_provider(): void
    {
        $message = 'Hi {{patient_name}} ref {{detail.ref}}';
        $cases = [
            'appointmentBooked' => [
                'patient_mobile' => '9000000001',
                'patient' => ['first_name' => 'Asha', 'last_name' => 'Rao', 'mobile' => '9000000001'],
                'doctor' => ['name' => 'Dr Green'],
                'detail' => ['ref' => 'appointment-42'],
                'appointment_id' => 42,
                'member_id' => 'member-appointment',
            ],
            'paymentReceived' => [
                'patient_mobile' => '9000000002',
                'patient' => ['first_name' => 'Bala', 'last_name' => 'Nair', 'mobile' => '9000000002'],
                'payment_amount' => '444.40',
                'detail' => ['ref' => 'payment-88'],
                'invoice_id' => 88,
                'member_id' => 'member-payment',
            ],
            'rewardTierUpgraded' => [
                'patient_mobile' => '9000000003',
                'patient' => ['first_name' => 'Chitra', 'last_name' => 'Das', 'mobile' => '9000000003'],
                'rewards' => [
                    'previous_tier' => ['name' => 'Bronze'],
                    'current_tier' => ['name' => 'Silver'],
                ],
                'detail' => ['ref' => 'tier-silver'],
                'member_id' => 'member-tier',
                'event_occurrence_id' => 'reward-tier-upgraded:member-tier:from:1:to:2:transition:3',
            ],
            'rewardPointsUpdated' => [
                'patient_mobile' => '9000000004',
                'patient' => ['first_name' => 'Dev', 'last_name' => 'Iyer', 'mobile' => '9000000004'],
                'change_type' => 'earned',
                'rewards' => [
                    'previous_balance' => 10,
                    'current_balance' => 15,
                    'points_changed' => 5,
                    'change_type' => 'earned',
                    'source' => 'appointment_payment',
                ],
                'detail' => ['ref' => 'points-5'],
                'member_id' => 'member-points',
                'event_occurrence_id' => 'reward-points-updated:transition:15',
            ],
            'medicineReminderDue' => [
                'patient_mobile' => '9000000005',
                'patient' => ['first_name' => 'Esha', 'last_name' => 'Menon', 'mobile' => '9000000005'],
                'medicine_name' => 'Paracetamol',
                'detail' => ['ref' => 'medicine-paracetamol'],
                'member_id' => 'member-medicine',
                'event_occurrence_id' => 'medicine-reminder-due:schedule:77:slot:1',
            ],
        ];

        foreach ($cases as $trigger => $context) {
            $this->providerSends = [];
            $version = $this->publishDefinition(
                $this->linearGraph($trigger, 'sendSms', [
                    'recipient' => 'patient',
                    'messageTemplate' => $message,
                ]),
                $trigger
            );

            $execution = app(WorkflowExecutor::class)->start($version, $trigger, $context);

            $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status, $trigger);
            $this->assertCount(1, $this->providerSends, $trigger);
            $this->assertSame('sms', $this->providerSends[0]['channel'], $trigger);
            $this->assertSame($context['patient_mobile'], $this->providerSends[0]['recipient'], $trigger);
            $this->assertSame(
                'Hi '.$context['patient']['first_name'].' '.$context['patient']['last_name'].' ref '.$context['detail']['ref'],
                $this->providerSends[0]['message'],
                $trigger
            );
        }
    }

    public function test_completed_sms_is_not_sent_again_for_the_same_reward_points_occurrence(): void
    {
        $version = $this->publishDefinition(
            $this->linearGraph('rewardPointsUpdated', 'sendSms', [
                'recipient' => 'patient',
                'messageTemplate' => 'Balance {{rewards.current_balance}}',
            ]),
            'rewardPointsUpdated'
        );
        $workflow = $version->workflow;
        $workflow->forceFill([
            'hospital_id' => null,
            'organization_id' => null,
            'trigger_type' => 'rewardPointsUpdated',
        ])->save();

        $payload = [
            'patient_mobile' => '9000000004',
            'patient' => ['first_name' => 'Dev', 'last_name' => 'Iyer', 'mobile' => '9000000004'],
            'member_id' => 'member-points',
            'change_type' => 'earned',
            'rewards' => ['current_balance' => 15, 'change_type' => 'earned'],
            'hospital_id' => null,
            'organization_id' => null,
            'event_occurrence_id' => 'reward-points-updated:transition:15',
        ];

        app(AutomationEngine::class)->handle('rewardPointsUpdated', $payload);
        app(AutomationEngine::class)->handle('rewardPointsUpdated', $payload);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame(WorkflowExecutionStatus::Completed->value, WorkflowExecution::query()->where('workflow_id', $workflow->id)->value('status'));
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('sms', $this->providerSends[0]['channel']);
        $this->assertSame('Balance 15', $this->providerSends[0]['message']);
        $this->assertSame('9000000004', $this->providerSends[0]['recipient']);
    }

    public function test_unresolved_reward_placeholder_is_not_sent_on_any_channel(): void
    {
        $context = [
            'patient_mobile' => '9000000099',
            'patient_email' => 'ada@example.com',
            'patient' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'mobile' => '9000000099', 'email' => 'ada@example.com'],
            'member_id' => 'member-missing',
            'rewards' => ['current_balance' => 5],
        ];

        foreach ([
            'sendSms' => ['messageTemplate' => 'Missing {{rewards.not_a_field}}', 'recipient' => 'patient'],
            'sendWhatsApp' => ['messageTemplate' => 'Missing {{rewards.not_a_field}}', 'recipient' => 'patient'],
            'sendEmail' => ['subject' => 'Points', 'body' => 'Missing {{rewards.not_a_field}}', 'recipient' => 'patient'],
            'sendPush' => ['title' => 'Points', 'body' => 'Missing {{rewards.not_a_field}}', 'recipient' => 'patient'],
        ] as $node => $data) {
            $this->providerSends = [];
            $version = $this->publishDefinition(
                $this->linearGraph('rewardPointsUpdated', $node, $data),
                'rewardPointsUpdated'
            );

            $execution = app(WorkflowExecutor::class)->start($version, 'rewardPointsUpdated', $context);

            $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status, $node);
            $this->assertSame([], $this->providerSends, $node);
            $this->assertStringContainsString(
                'unresolved',
                strtolower((string) $execution->fresh()->failure_reason)
            );
        }
    }
}
