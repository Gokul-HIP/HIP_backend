<?php

namespace App\Modules\Workflow;

use App\Modules\Automation\Support\TriggerCatalog;
use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\NodeProcessors\CreateRecordNodeProcessor;
use App\Modules\Workflow\NodeProcessors\DbDeleteNodeProcessor;
use App\Modules\Workflow\NodeProcessors\DbQueryNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendAiChatNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendAiVoiceNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendEmailNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendPushNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendSMSNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendTemplateNodeProcessor;
use App\Modules\Workflow\NodeProcessors\SendWhatsAppNodeProcessor;
use App\Modules\Workflow\NodeProcessors\UpdateRecordNodeProcessor;
use App\Modules\Workflow\NodeProcessors\WebhookNodeProcessor;
use App\Modules\Workflow\NodeProcessors\ConditionNodeProcessor;
use App\Modules\Workflow\NodeProcessors\DelayNodeProcessor;
use App\Modules\Workflow\NodeProcessors\EndNodeProcessor;
use App\Modules\Workflow\NodeProcessors\AppointmentBookedTriggerNodeProcessor;
use App\Modules\Workflow\NodeProcessors\BirthdayTriggerNodeProcessor;
use App\Modules\Workflow\NodeProcessors\MedicineReminderDueTriggerNodeProcessor;
use App\Modules\Workflow\NodeProcessors\MedicineReminderNodeProcessor;
use App\Modules\Workflow\NodeProcessors\TriggerNodeProcessor;
use App\Modules\Workflow\NodeProcessors\PrescriptionAddedTriggerNodeProcessor;
use App\Modules\Workflow\NodeProcessors\ScheduledEventTriggerNodeProcessor;
use App\Modules\Workflow\Models\WorkflowTemplate;
use App\Modules\Workflow\Policies\WorkflowTemplatePolicy;
use App\Modules\Workflow\Services\Compiler\WorkflowCompiler;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\NodeProcessorRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkflowCompilerInterface::class, WorkflowCompiler::class);
        $this->app->singleton(NodeProcessorRegistry::class, function ($app) {
            $registry = new NodeProcessorRegistry;

            $executors = [
                PrescriptionAddedTriggerNodeProcessor::class,
                MedicineReminderDueTriggerNodeProcessor::class,
                MedicineReminderNodeProcessor::class,
                AppointmentBookedTriggerNodeProcessor::class,
                BirthdayTriggerNodeProcessor::class,
                ScheduledEventTriggerNodeProcessor::class,
                SendWhatsAppNodeProcessor::class,
                SendEmailNodeProcessor::class,
                SendSMSNodeProcessor::class,
                SendPushNodeProcessor::class,
                SendTemplateNodeProcessor::class,
                SendAiChatNodeProcessor::class,
                SendAiVoiceNodeProcessor::class,
                WebhookNodeProcessor::class,
                UpdateRecordNodeProcessor::class,
                CreateRecordNodeProcessor::class,
                DbDeleteNodeProcessor::class,
                DbQueryNodeProcessor::class,
                ConditionNodeProcessor::class,
                DelayNodeProcessor::class,
                EndNodeProcessor::class,
            ];

            foreach ($executors as $executorClass) {
                $registry->register($app->make($executorClass));
            }

            // Ensure every canonical TriggerCatalog type can execute (continue to next node).
            // Specialized executors above win; remaining types get a passthrough.
            $dispatcher = $app->make(ActionDispatcher::class);
            foreach (TriggerCatalog::types() as $triggerType) {
                if (! $registry->has($triggerType)) {
                    $registry->register(new TriggerNodeProcessor($triggerType, $dispatcher));
                }
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        Gate::policy(WorkflowTemplate::class, WorkflowTemplatePolicy::class);
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        \Illuminate\Support\Facades\Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/Routes/workflow.php');
    }
}
