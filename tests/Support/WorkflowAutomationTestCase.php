<?php

namespace Tests\Support;

use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * Shared harness for Hospital Automation graph tests.
 * Uses in-memory SQLite + mocked notification providers. No queue workers.
 */
abstract class WorkflowAutomationTestCase extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @var list<array{channel: string, message: string, subject?: string|null, recipient?: string|null}> */
    protected array $providerSends = [];

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

        if (! Schema::hasColumn('workflows', 'hospital_id')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->unsignedBigInteger('hospital_id')->nullable();
            });
        }

        if (! Schema::hasTable('communication_logs')) {
            Schema::create('communication_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workflow_id')->nullable();
                $table->unsignedBigInteger('workflow_execution_id')->nullable();
                $table->string('node_id')->nullable();
                $table->string('channel');
                $table->string('status')->default('pending');
                $table->string('recipient')->nullable();
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->text('provider_response')->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();
            });
        }

        $this->providerSends = [];
        $this->bindNotificationMocks(success: true);
    }

    protected function bindNotificationMocks(bool $success = true, ?string $failChannel = null): void
    {
        $ok = ['success' => true, 'response' => 'ok'];
        $fail = ['success' => false, 'response' => 'provider down'];

        $whatsApp = Mockery::mock(WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('send')->andReturnUsing(function (?string $mobile, string $message) use ($success, $failChannel, $ok, $fail) {
            $this->providerSends[] = [
                'channel' => 'whatsapp',
                'message' => $message,
                'recipient' => $mobile,
            ];

            return (! $success && $failChannel === 'whatsapp') ? $fail : $ok;
        });

        $sms = Mockery::mock(SMSNotificationService::class);
        $sms->shouldReceive('send')->andReturnUsing(function (?string $mobile, string $message) use ($success, $failChannel, $ok, $fail) {
            $this->providerSends[] = [
                'channel' => 'sms',
                'message' => $message,
                'recipient' => $mobile,
            ];

            return (! $success && $failChannel === 'sms') ? $fail : $ok;
        });

        $email = Mockery::mock(EmailNotificationService::class);
        $email->shouldReceive('send')->andReturnUsing(function (?string $to, string $subject, string $message) use ($success, $failChannel, $ok, $fail) {
            $this->providerSends[] = [
                'channel' => 'email',
                'message' => $message,
                'subject' => $subject,
                'recipient' => $to,
            ];

            return (! $success && $failChannel === 'email') ? $fail : $ok;
        });

        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('send')->andReturnUsing(function ($memberId, string $title, string $message) use ($success, $failChannel, $ok, $fail) {
            $this->providerSends[] = [
                'channel' => 'push',
                'message' => $message,
                'subject' => $title,
                'recipient' => is_scalar($memberId) ? (string) $memberId : null,
            ];

            return (! $success && $failChannel === 'push') ? $fail : $ok;
        });

        $this->app->instance(WhatsAppNotificationService::class, $whatsApp);
        $this->app->instance(SMSNotificationService::class, $sms);
        $this->app->instance(EmailNotificationService::class, $email);
        $this->app->instance(PushNotificationService::class, $push);

        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);

        // AutomationServiceProvider registers AiPromptNodeProcessor only during boot.
        // Rebuilding the registry singleton requires re-applying that registration.
        $registry = $this->app->make(NodeProcessorRegistry::class);
        if (! $registry->has('aiPrompt')) {
            $registry->register($this->app->make(
                \App\Modules\Workflow\NodeProcessors\AiPromptNodeProcessor::class
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function node(string $id, string $nodeType, array $extra = []): array
    {
        return [
            'id' => $id,
            'type' => 'workflow',
            'position' => ['x' => 0, 'y' => 0],
            'data' => array_merge(['nodeType' => $nodeType], $extra),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function edge(string $id, string $source, string $target, ?string $sourceHandle = null): array
    {
        $edge = ['id' => $id, 'source' => $source, 'target' => $target];
        if ($sourceHandle !== null) {
            $edge['sourceHandle'] = $sourceHandle;
        }

        return $edge;
    }

    /**
     * Linear graph: trigger → action → end (FE node types as stored by Flow Builder).
     *
     * @param  array<string, mixed>  $actionData
     * @return array<string, mixed>
     */
    protected function linearGraph(
        string $triggerType,
        string $actionType,
        array $actionData = [],
        string $triggerId = 't1',
        string $actionId = 'a1',
        string $endId = 'e1',
    ): array {
        return [
            'builderVersion' => '1',
            'nodes' => [
                $this->node($triggerId, $triggerType),
                $this->node($actionId, $actionType, $actionData),
                $this->node($endId, 'end'),
            ],
            'edges' => [
                $this->edge('e1', $triggerId, $actionId),
                $this->edge('e2', $actionId, $endId),
            ],
        ];
    }

    /**
     * Trigger → end only.
     *
     * @return array<string, mixed>
     */
    protected function triggerEndGraph(string $triggerType): array
    {
        return [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', $triggerType),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'e1'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function publishDefinition(
        array $definition,
        string $triggerType = 'appointmentBooked',
        ?int $hospitalId = null,
        ?int $organizationId = null,
    ): WorkflowVersion {
        $workflow = Workflow::query()->create([
            'name' => 'Automation Suite '.uniqid(),
            'status' => WorkflowStatus::Active->value,
            'trigger_type' => $triggerType,
            'hospital_id' => $hospitalId,
            'organization_id' => $organizationId,
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

    /**
     * @return array<string, mixed>
     */
    protected function sampleAppointmentContext(array $overrides = []): array
    {
        return array_merge([
            'patient' => [
                'id' => 'patient-1',
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'mobile' => '+919999999999',
                'email' => 'ada@example.com',
                'age' => 35,
                'gender' => 'female',
            ],
            'doctor' => ['id' => 'doctor-1', 'name' => 'Dr Green'],
            'hospital' => ['id' => 10, 'name' => 'Sunrise Hospital', 'organization_id' => 1],
            'appointment_id' => 42,
            'appointment_date' => '01 Sep 2026',
            'appointment_time' => '10:30 AM',
            'patient_id' => 'patient-1',
            'doctor_id' => 'doctor-1',
            'hospital_id' => 10,
            'organization_id' => 1,
            'patient_mobile' => '+919999999999',
            'patient_email' => 'ada@example.com',
            'member_id' => 'member-1',
        ], $overrides);
    }
}
