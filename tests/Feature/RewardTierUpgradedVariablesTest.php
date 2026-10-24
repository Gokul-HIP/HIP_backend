<?php

namespace Tests\Feature;

use App\Models\RewardTier;
use App\Models\RewardTierTransition;
use App\Models\UserRewardProgress;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Events\RewardTierUpgraded;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsJetClient;
use App\Modules\Workflow\Enums\CommunicationStatus;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\CommunicationLog;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Services\NotificationService;
use App\Services\RewardTierService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

class RewardTierUpgradedVariablesTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.base_url' => 'https://whatsjet.test/api',
            'services.whatsapp.vendor_uid' => 'vendor-uid',
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.default_country_code' => '91',
            'services.whatsapp.timeout' => 10,
        ]);

        $this->bootRewardTables();
        $this->bindRealWhatsApp();
    }

    public function test_upgrade_message_resolves_both_tier_names_before_whatsapp_send(): void
    {
        Http::fake([
            'https://whatsjet.test/api/*' => Http::response([
                'result' => 'success',
                'data' => ['log_uid' => 'log-1', 'wamid' => 'wamid-1', 'status' => 'sent'],
            ], 200),
        ]);

        $previous = $this->tier('Harbor '.substr(uniqid(), -6), 0, 4);
        $current = $this->tier('Summit '.substr(uniqid(), -6), 40, 9);

        $captured = null;
        Event::listen(RewardTierUpgraded::class, function (RewardTierUpgraded $event) use (&$captured): void {
            $captured = $event->payload();
        });

        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-vars');
        $service->addEarnedCoins('user-vars', 40);

        $this->assertIsArray($captured);
        $this->assertSame([
            'previous_tier' => [
                'id' => (int) $previous->id,
                'name' => $previous->name,
                'rank' => (int) $previous->sort_order,
            ],
            'current_tier' => [
                'id' => (int) $current->id,
                'name' => $current->name,
                'rank' => (int) $current->sort_order,
            ],
        ], $captured['rewards']);

        $context = app(AutomationContextBuilder::class)->merge($captured);
        $context['patient_mobile'] = '9876543210';

        $template = "Congratulations! You've upgraded from {{rewards.previous_tier.name}} to {{rewards.current_tier.name}}.";
        $expected = "Congratulations! You've upgraded from {$previous->name} to {$current->name}.";

        $version = $this->publishDefinition(
            $this->linearGraph('rewardTierUpgraded', 'sendWhatsApp', [
                'messageTemplate' => $template,
                'recipient' => 'patient',
            ]),
            'rewardTierUpgraded',
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'rewardTierUpgraded', $context);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame($expected, $this->sentWhatsAppBody());
        $this->assertStringNotContainsString('{{', $this->sentWhatsAppBody());
    }

    public function test_missing_tier_is_not_sent_to_whatsapp_as_a_placeholder(): void
    {
        Http::fake([
            'https://whatsjet.test/api/*' => Http::response(['result' => 'success'], 200),
        ]);

        $previous = $this->tier('Cedar '.substr(uniqid(), -6), 0, 2);

        $context = [
            'patient_mobile' => '9876543210',
            'rewards' => [
                'previous_tier' => [
                    'id' => (int) $previous->id,
                    'name' => $previous->name,
                    'rank' => (int) $previous->sort_order,
                ],
                'current_tier' => null,
            ],
        ];

        $version = $this->publishDefinition(
            $this->linearGraph('rewardTierUpgraded', 'sendWhatsApp', [
                'messageTemplate' => "Congratulations! You've upgraded from {{rewards.previous_tier.name}} to {{rewards.current_tier.name}}.",
                'recipient' => 'patient',
            ]),
            'rewardTierUpgraded',
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'rewardTierUpgraded', $context);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
        $this->assertStringContainsString(
            'unresolved',
            strtolower((string) \App\Modules\Workflow\Models\CommunicationLog::query()->latest('id')->value('provider_response'))
        );
    }

    public function test_upgrade_push_uses_member_id_and_keeps_tier_variables(): void
    {
        $previous = $this->tier('Harbor '.substr(uniqid(), -6), 0, 4);
        $current = $this->tier('Summit '.substr(uniqid(), -6), 40, 9);

        $captured = null;
        Event::listen(RewardTierUpgraded::class, function (RewardTierUpgraded $event) use (&$captured): void {
            $captured = $event->payload();
        });

        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-push');
        $service->addEarnedCoins('user-push', 40);

        $this->assertIsArray($captured);
        $this->assertSame('user-push', $captured['member_id']);
        $this->assertNotSame($captured['event_occurrence_id'], $captured['member_id']);
        $this->assertSame($previous->name, data_get($captured, 'rewards.previous_tier.name'));
        $this->assertSame($current->name, data_get($captured, 'rewards.current_tier.name'));

        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')
            ->once()
            ->withArgs(function ($userId, $title, $body) use ($previous, $current) {
                return $userId === 'user-push'
                    && $title === 'Tier upgraded'
                    && str_contains((string) $body, $previous->name)
                    && str_contains((string) $body, $current->name);
            })
            ->andReturn(true);
        $this->bindPushProvider($notifications);

        $context = app(AutomationContextBuilder::class)->merge($captured);
        $version = $this->publishDefinition(
            $this->linearGraph('rewardTierUpgraded', 'sendPush', [
                'title' => 'Tier upgraded',
                'body' => 'From {{rewards.previous_tier.name}} to {{rewards.current_tier.name}}.',
            ]),
            'rewardTierUpgraded',
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'rewardTierUpgraded', $context);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
    }

    public function test_push_without_a_member_user_id_fails_without_calling_the_provider(): void
    {
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->never();
        $this->bindPushProvider($notifications);

        $version = $this->publishDefinition(
            $this->linearGraph('rewardTierUpgraded', 'sendPush', [
                'title' => 'Tier upgraded',
                'body' => 'From {{rewards.previous_tier.name}} to {{rewards.current_tier.name}}.',
            ]),
            'rewardTierUpgraded',
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'rewardTierUpgraded', [
            'rewards' => [
                'previous_tier' => ['id' => 2, 'name' => 'Silver', 'rank' => 2],
                'current_tier' => ['id' => 3, 'name' => 'Gold', 'rank' => 3],
            ],
            'event_occurrence_id' => RewardTierUpgraded::occurrenceId('not-a-recipient', 2, 3),
        ]);

        $fresh = $execution->fresh();
        $this->assertSame(WorkflowExecutionStatus::Failed->value, $fresh->status);
        $this->assertSame('No member user id for push notification', $fresh->failure_reason);
    }

    public function test_a_completed_reward_tier_occurrence_is_not_sent_twice(): void
    {
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->once()->andReturn(true);
        $this->bindPushProvider($notifications);

        $this->publishRewardPush();
        $payload = $this->rewardPayload('user-once');

        app(AutomationEngine::class)->handle('rewardTierUpgraded', $payload);
        app(AutomationEngine::class)->handle('rewardTierUpgraded', $payload);

        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame(
            WorkflowExecutionStatus::Completed->value,
            WorkflowExecution::query()->first()->status
        );
    }

    public function test_a_failed_reward_tier_execution_that_never_sent_can_be_retried(): void
    {
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->once()->withArgs(function ($userId, $title, $body) {
            return $userId === 'user-retry' && $title === 'Tier upgraded' && is_string($body);
        })->andReturn(true);
        $this->bindPushProvider($notifications);

        $this->publishRewardPush();
        $occurrence = RewardTierUpgraded::occurrenceId('user-retry', 2, 3);

        app(AutomationEngine::class)->handle('rewardTierUpgraded', $this->rewardPayload('user-retry', false));

        $failed = WorkflowExecution::query()->first();
        $this->assertSame(WorkflowExecutionStatus::Failed->value, $failed->status);
        $this->assertSame('No member user id for push notification', $failed->failure_reason);

        app(AutomationEngine::class)->handle('rewardTierUpgraded', $this->rewardPayload('user-retry'));

        $this->assertSame(1, WorkflowExecution::query()->count());
        $retried = $failed->fresh();
        $this->assertSame($failed->id, $retried->id);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $retried->status);
        $this->assertSame('user-retry', $retried->context['member_id']);
        $this->assertSame($occurrence, $retried->context['event_occurrence_id']);
        $this->assertNull($retried->failure_reason);
    }

    public function test_a_running_reward_tier_execution_is_not_duplicated(): void
    {
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->never();
        $this->bindPushProvider($notifications);

        $version = $this->publishRewardPush();
        $payload = $this->rewardPayload('user-running');
        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $version->workflow_id,
            'workflow_version_id' => $version->id,
            'status' => WorkflowExecutionStatus::Running->value,
            'trigger_type' => 'rewardTierUpgraded',
            'current_node_id' => 'a1',
            'context' => $payload,
            'started_at' => now(),
        ]);

        app(AutomationEngine::class)->handle('rewardTierUpgraded', $payload);

        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame(WorkflowExecutionStatus::Running->value, $execution->fresh()->status);
    }

    public function test_a_provider_accepted_reward_notification_is_not_resent(): void
    {
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->never();
        $this->bindPushProvider($notifications);

        $version = $this->publishRewardPush();
        $payload = $this->rewardPayload('user-accepted');
        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $version->workflow_id,
            'workflow_version_id' => $version->id,
            'status' => WorkflowExecutionStatus::Failed->value,
            'trigger_type' => 'rewardTierUpgraded',
            'current_node_id' => 'a1',
            'context' => $payload,
            'failure_reason' => 'later node failed',
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        CommunicationLog::query()->create([
            'workflow_id' => $version->workflow_id,
            'workflow_execution_id' => $execution->id,
            'node_id' => 'a1',
            'channel' => 'push',
            'status' => CommunicationStatus::Sent->value,
            'provider_response' => 'Push notification dispatched',
            'sent_at' => now(),
        ]);

        app(AutomationEngine::class)->handle('rewardTierUpgraded', $payload);

        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame(1, CommunicationLog::query()->count());
    }

    public function test_redelivery_of_the_same_transition_keeps_one_occurrence_and_does_not_send_twice(): void
    {
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->once()->andReturn(true);
        $this->bindPushProvider($notifications);
        $this->publishRewardPush();

        $captured = null;
        Event::listen(RewardTierUpgraded::class, function (RewardTierUpgraded $event) use (&$captured): void {
            $captured = $event->payload();
        });

        $previous = $this->tier('Relay '.substr(uniqid(), -6), 0, 1);
        $current = $this->tier('Relay Next '.substr(uniqid(), -6), 40, 2);
        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-redeliver');
        $service->addEarnedCoins('user-redeliver', 40);

        $transition = RewardTierTransition::query()->firstOrFail();
        $occurrence = RewardTierUpgraded::occurrenceId('user-redeliver', $previous->id, $current->id, $transition->id);
        $this->assertSame($occurrence, $transition->occurrence_id);
        $this->assertSame($occurrence, $captured['event_occurrence_id']);
        $this->assertSame('user-redeliver', $captured['member_id']);

        $event = new RewardTierUpgraded($captured);
        $redelivered = unserialize(serialize($event));
        $this->assertInstanceOf(RewardTierUpgraded::class, $redelivered);
        $this->assertSame($occurrence, $redelivered->payload()['event_occurrence_id']);

        app(DispatchHospitalAutomationWorkflow::class)->handle($redelivered);

        $this->assertSame(1, RewardTierTransition::query()->count());
        $this->assertSame(1, WorkflowExecution::query()->count());
        $execution = WorkflowExecution::query()->first();
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame($occurrence, $execution->context['event_occurrence_id']);
    }

    public function test_a_completed_transition_does_not_suppress_a_later_upgrade_between_the_same_tiers(): void
    {
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->twice()->andReturn(true);
        $this->bindPushProvider($notifications);
        $this->publishRewardPush();

        $bronze = $this->tier('Again '.substr(uniqid(), -6), 0, 1);
        $silver = $this->tier('Again Next '.substr(uniqid(), -6), 40, 2);
        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-second');
        $service->addEarnedCoins('user-second', 40);

        UserRewardProgress::query()->where('hip_user_id', 'user-second')->update([
            'reward_tier_id' => $bronze->id,
            'earned_coins_in_tier' => 0,
        ]);
        $service->addEarnedCoins('user-second', 40);

        $transitions = RewardTierTransition::query()->orderBy('id')->get();
        $this->assertCount(2, $transitions);
        $this->assertNotSame($transitions[0]->occurrence_id, $transitions[1]->occurrence_id);
        $this->assertSame((int) $bronze->id, (int) $transitions[0]->previous_tier_id);
        $this->assertSame((int) $silver->id, (int) $transitions[0]->new_tier_id);
        $this->assertSame((int) $bronze->id, (int) $transitions[1]->previous_tier_id);
        $this->assertSame((int) $silver->id, (int) $transitions[1]->new_tier_id);

        $executions = WorkflowExecution::query()->orderBy('id')->get();
        $this->assertCount(2, $executions);
        $this->assertSame($transitions[0]->occurrence_id, $executions[0]->context['event_occurrence_id']);
        $this->assertSame($transitions[1]->occurrence_id, $executions[1]->context['event_occurrence_id']);
        $this->assertSame('user-second', $executions[0]->context['member_id']);
        $this->assertSame('user-second', $executions[1]->context['member_id']);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $executions[0]->status);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $executions[1]->status);
    }

    private function bindPushProvider(NotificationService $notifications): void
    {
        $this->app->instance(NotificationService::class, $notifications);
        $this->app->instance(PushNotificationService::class, new PushNotificationService($notifications));
        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);
        $this->app->forgetInstance(AutomationEngine::class);
        $this->app->forgetInstance(HospitalAutomationTriggerService::class);
        $this->app->forgetInstance(DispatchHospitalAutomationWorkflow::class);
    }

    private function publishRewardPush(): WorkflowVersion
    {
        return $this->publishDefinition(
            $this->linearGraph('rewardTierUpgraded', 'sendPush', [
                'title' => 'Tier upgraded',
                'body' => 'From {{rewards.previous_tier.name}} to {{rewards.current_tier.name}}.',
            ]),
            'rewardTierUpgraded',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rewardPayload(string $userId, bool $withMember = true): array
    {
        $payload = [
            'hip_user_id' => $userId,
            'previous_tier_id' => 2,
            'new_tier_id' => 3,
            'rewards' => [
                'previous_tier' => ['id' => 2, 'name' => 'Silver', 'rank' => 2],
                'current_tier' => ['id' => 3, 'name' => 'Gold', 'rank' => 3],
            ],
            'hospital_id' => null,
            'event_occurrence_id' => RewardTierUpgraded::occurrenceId($userId, 2, 3),
        ];

        if ($withMember) {
            $payload['member_id'] = $userId;
        }

        return $payload;
    }

    private function sentWhatsAppBody(): string
    {
        $body = null;
        Http::assertSent(function (Request $request) use (&$body) {
            $body = (string) $request['message_body'];

            return $request->url() === 'https://whatsjet.test/api/vendor-uid/contact/send-message';
        });

        return (string) $body;
    }

    private function tier(string $name, int $minCoins, int $sortOrder): RewardTier
    {
        return RewardTier::query()->create([
            'name' => $name,
            'min_coins' => $minCoins,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function bindRealWhatsApp(): void
    {
        $this->app->forgetInstance(WhatsJetClient::class);
        $this->app->forgetInstance(WhatsAppNotificationService::class);
        $this->app->instance(WhatsAppNotificationService::class, $this->app->make(WhatsAppNotificationService::class));
        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);
    }

    private function bootRewardTables(): void
    {
        Schema::create('reward_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('user_reward_progress', function (Blueprint $table) {
            $table->id();
            $table->string('hip_user_id')->unique();
            $table->unsignedBigInteger('reward_tier_id')->nullable();
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();
        });
        Schema::create('reward_tier_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('hip_user_id')->index();
            $table->unsignedBigInteger('previous_tier_id')->nullable();
            $table->unsignedBigInteger('new_tier_id');
            $table->string('occurrence_id')->nullable()->unique();
            $table->timestamps();
        });
    }
}
