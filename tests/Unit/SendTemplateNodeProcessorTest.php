<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\NodeProcessors\SendTemplateNodeProcessor;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendTemplateNodeProcessorTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{channel: string, message: string, mobile?: string|null}> */
    private array $whatsAppSends = [];

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/2025_12_10_111601_create_organizations_table.php',
                'database/migrations/2026_07_21_100000_create_workflows_table.php',
                'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
                'database/migrations/2026_07_21_100002_create_workflow_executions_table.php',
                'database/migrations/2026_07_21_100003_create_communication_logs_table.php',
                'database/migrations/2026_07_21_100004_create_workflow_templates_table.php',
                'database/migrations/2026_07_21_140000_add_category_and_version_to_workflow_templates_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
                'database/migrations/2026_07_29_100000_rename_workflow_templates_to_workflow_message_templates.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->whatsAppSends = [];
        $this->bindNotificationMocks();
    }

    public function test_registry_resolves_send_template_frontend_node_type(): void
    {
        $registry = app(NodeProcessorRegistry::class);

        $this->assertTrue($registry->has('sendTemplate'));
        $this->assertInstanceOf(SendTemplateNodeProcessor::class, $registry->get('sendTemplate'));
        $this->assertSame('sendTemplate', $registry->get('sendTemplate')->type());
    }

    public function test_valid_send_template_resolves_variables_and_continues_workflow(): void
    {
        $template = $this->createTemplate(
            'Hi {{patient_name}}, see {{doctor_name}} at {{hospital_name}} on {{appointment_date}} {{appointment_time}} (#{{appointment_id}}).'
        );

        $version = $this->publishDefinition($this->graph($template->id, 'whatsapp'));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient' => [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'mobile' => '+919999999999',
            ],
            'doctor' => ['name' => 'Dr Green'],
            'hospital' => ['name' => 'Sunrise Hospital'],
            'appointment_date' => '01 Sep 2026',
            'appointment_time' => '10:30 AM',
            'appointment_id' => '42',
            'patient_mobile' => '+919999999999',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->whatsAppSends);
        $this->assertSame('whatsapp', $this->whatsAppSends[0]['channel']);
        $this->assertSame(
            'Hi Ada Lovelace, see Dr Green at Sunrise Hospital on 01 Sep 2026 10:30 AM (#42).',
            $this->whatsAppSends[0]['message']
        );
    }

    public function test_send_template_uses_configured_channel(): void
    {
        $template = $this->createTemplate('Push body for {{patient_name}}');
        $version = $this->publishDefinition($this->graph($template->id, 'push'));

        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($memberId, $title, $message) {
                $this->whatsAppSends[] = [
                    'channel' => 'push',
                    'message' => $message,
                    'mobile' => null,
                ];

                return ['success' => true, 'response' => 'ok'];
            });
        $this->app->instance(PushNotificationService::class, $push);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient' => ['first_name' => 'Ada', 'last_name' => ''],
            'member_id' => 'member-1',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->whatsAppSends);
        $this->assertSame('push', $this->whatsAppSends[0]['channel']);
        $this->assertSame('Push body for Ada', $this->whatsAppSends[0]['message']);
    }

    public function test_missing_template_id_fails_without_sending(): void
    {
        $version = $this->publishDefinition($this->graph(null, 'whatsapp'));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient_mobile' => '+919999999999',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertCount(0, $this->whatsAppSends);
        $this->assertStringContainsString('templateId', (string) $execution->fresh()->failure_reason);
    }

    public function test_invalid_template_id_fails_without_sending(): void
    {
        $version = $this->publishDefinition($this->graph(999999, 'whatsapp'));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient_mobile' => '+919999999999',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertCount(0, $this->whatsAppSends);
        $this->assertStringContainsString('not found', (string) $execution->fresh()->failure_reason);
    }

    public function test_inactive_template_fails_without_sending(): void
    {
        $template = $this->createTemplate('Hello {{patient_name}}', active: false);
        $version = $this->publishDefinition($this->graph($template->id, 'whatsapp'));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient_mobile' => '+919999999999',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->status);
        $this->assertCount(0, $this->whatsAppSends);
    }

    public function test_missing_channel_fails_without_sending(): void
    {
        $template = $this->createTemplate('Hello {{patient_name}}');
        $version = $this->publishDefinition($this->graph($template->id, ''));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient_mobile' => '+919999999999',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertCount(0, $this->whatsAppSends);
        $this->assertStringContainsString('channel', (string) $execution->fresh()->failure_reason);
    }

    public function test_channel_delivery_failure_fails_execution_without_duplicate_send(): void
    {
        $template = $this->createTemplate('Hello {{patient_name}}');
        $version = $this->publishDefinition($this->graph($template->id, 'whatsapp'));

        $whatsApp = Mockery::mock(WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->andReturn(['success' => false, 'response' => 'provider down']);
        $this->app->instance(WhatsAppNotificationService::class, $whatsApp);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient' => ['first_name' => 'Ada'],
            'patient_mobile' => '+919999999999',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('provider down', (string) $execution->fresh()->failure_reason);
    }

    public function test_empty_template_body_fails_without_sending(): void
    {
        $template = $this->createTemplate('   ');
        $version = $this->publishDefinition($this->graph($template->id, 'whatsapp'));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient_mobile' => '+919999999999',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertCount(0, $this->whatsAppSends);
        $this->assertStringContainsString('empty body', (string) $execution->fresh()->failure_reason);
    }

    private function bindNotificationMocks(): void
    {
        $whatsApp = Mockery::mock(WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('send')->andReturnUsing(function (?string $mobile, string $message) {
            $this->whatsAppSends[] = [
                'channel' => 'whatsapp',
                'message' => $message,
                'mobile' => $mobile,
            ];

            return ['success' => true, 'response' => 'ok'];
        });

        $this->app->instance(WhatsAppNotificationService::class, $whatsApp);
        $this->app->instance(SMSNotificationService::class, Mockery::mock(SMSNotificationService::class));
        $this->app->instance(EmailNotificationService::class, Mockery::mock(EmailNotificationService::class));
        $this->app->instance(PushNotificationService::class, Mockery::mock(PushNotificationService::class));

        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);
    }

    private function createTemplate(string $body, bool $active = true): WorkflowMessageTemplate
    {
        return WorkflowMessageTemplate::query()->create([
            'name' => 'Appointment Template',
            'channel' => 'whatsapp',
            'locale' => 'en',
            'body' => $body,
            'variables' => ['patient_name', 'doctor_name'],
            'is_active' => $active,
            'version_number' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function graph(?int $templateId, string $channel): array
    {
        $data = [
            'nodeType' => 'sendTemplate',
            'label' => 'Send Template',
            'recipient' => 'patient',
            'channel' => $channel,
        ];

        if ($templateId !== null) {
            $data['templateId'] = $templateId;
        } else {
            $data['templateId'] = '';
        }

        return [
            'builderVersion' => '1',
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => ['nodeType' => 'appointmentBooked'],
                ],
                [
                    'id' => 'm1',
                    'type' => 'workflow',
                    'position' => ['x' => 100, 'y' => 0],
                    'data' => $data,
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'm1'],
                ['id' => 'e2', 'source' => 'm1', 'target' => 'e1'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function publishDefinition(array $definition): WorkflowVersion
    {
        $workflow = Workflow::query()->create([
            'name' => 'SendTemplate Test '.uniqid(),
            'status' => WorkflowStatus::Active->value,
            'trigger_type' => 'appointmentBooked',
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

        return $version->fresh();
    }
}
