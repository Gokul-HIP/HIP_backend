<?php

namespace Tests\Feature;

use App\Models\RewardTier;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Events\RewardTierUpgraded;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsJetClient;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
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
    }
}
