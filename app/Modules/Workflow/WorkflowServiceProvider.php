<?php

namespace App\Modules\Workflow;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Executors\Actions\CreateRecordExecutor;
use App\Modules\Workflow\Executors\Actions\SendEmailExecutor;
use App\Modules\Workflow\Executors\Actions\SendPushExecutor;
use App\Modules\Workflow\Executors\Actions\SendSMSExecutor;
use App\Modules\Workflow\Executors\Actions\SendWhatsAppExecutor;
use App\Modules\Workflow\Executors\Actions\UpdateRecordExecutor;
use App\Modules\Workflow\Executors\Actions\WebhookExecutor;
use App\Modules\Workflow\Executors\Flow\ConditionExecutor;
use App\Modules\Workflow\Executors\Flow\DelayExecutor;
use App\Modules\Workflow\Executors\Flow\EndExecutor;
use App\Modules\Workflow\Executors\Triggers\AppointmentBookedTriggerExecutor;
use App\Modules\Workflow\Executors\Triggers\BirthdayTriggerExecutor;
use App\Modules\Workflow\Executors\Triggers\MedicineReminderDueTriggerExecutor;
use App\Modules\Workflow\Executors\Triggers\MedicineReminderNodeExecutor;
use App\Modules\Workflow\Executors\Triggers\PrescriptionAddedTriggerExecutor;
use App\Modules\Workflow\Executors\Triggers\ScheduledEventTriggerExecutor;
use App\Modules\Workflow\Services\Compiler\WorkflowCompiler;
use App\Modules\Workflow\Services\Runtime\NodeExecutorRegistry;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkflowCompilerInterface::class, WorkflowCompiler::class);
        $this->app->singleton(NodeExecutorRegistry::class, function ($app) {
            $registry = new NodeExecutorRegistry;

            $executors = [
                PrescriptionAddedTriggerExecutor::class,
                MedicineReminderDueTriggerExecutor::class,
                MedicineReminderNodeExecutor::class,
                AppointmentBookedTriggerExecutor::class,
                BirthdayTriggerExecutor::class,
                ScheduledEventTriggerExecutor::class,
                SendWhatsAppExecutor::class,
                SendEmailExecutor::class,
                SendSMSExecutor::class,
                SendPushExecutor::class,
                WebhookExecutor::class,
                UpdateRecordExecutor::class,
                CreateRecordExecutor::class,
                ConditionExecutor::class,
                DelayExecutor::class,
                EndExecutor::class,
            ];

            foreach ($executors as $executorClass) {
                $registry->register($app->make($executorClass));
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        \Illuminate\Support\Facades\Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/Routes/workflow.php');
    }
}
