<?php

namespace App\Modules\HospitalAutomation;

use App\Modules\HospitalAutomation\Events\AnniversaryReached;
use App\Modules\HospitalAutomation\Events\AppointmentBooked;
use App\Modules\HospitalAutomation\Events\AppointmentCancelled;
use App\Modules\HospitalAutomation\Events\AppointmentCompleted;
use App\Modules\HospitalAutomation\Events\AppointmentMissed;
use App\Modules\HospitalAutomation\Events\AppointmentRescheduled;
use App\Modules\HospitalAutomation\Events\BirthdayReached;
use App\Modules\HospitalAutomation\Events\CampaignTriggered;
use App\Modules\HospitalAutomation\Events\InvoiceGenerated;
use App\Modules\HospitalAutomation\Events\LabCompleted;
use App\Modules\HospitalAutomation\Events\LabReportReady;
use App\Modules\HospitalAutomation\Events\LabTestOrdered;
use App\Modules\HospitalAutomation\Events\MedicineRefillDue;
use App\Modules\HospitalAutomation\Events\MembershipExpiry;
use App\Modules\HospitalAutomation\Events\MembershipRenewed;
use App\Modules\HospitalAutomation\Events\MessageReceived;
use App\Modules\HospitalAutomation\Events\PatientRegistered;
use App\Modules\HospitalAutomation\Events\PaymentPending;
use App\Modules\HospitalAutomation\Events\PaymentReceived;
use App\Modules\HospitalAutomation\Events\ProcedureCompleted;
use App\Modules\HospitalAutomation\Events\RewardPointsUpdated;
use App\Modules\HospitalAutomation\Events\RewardTierUpgraded;
use App\Modules\HospitalAutomation\Executors\AiPromptExecutor;
use App\Modules\HospitalAutomation\Executors\HospitalDomainTriggerExecutor;
use App\Modules\HospitalAutomation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\HospitalAutomation\Support\TriggerCatalog;
use App\Modules\Workflow\Services\Runtime\NodeExecutorRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HospitalAutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerExecutors();
        $this->registerEvents();
        $this->loadRoutes();
    }

    protected function registerExecutors(): void
    {
        $registry = $this->app->make(NodeExecutorRegistry::class);
        $existingTypes = array_map(fn ($e) => $e->type(), $registry->all());

        foreach (TriggerCatalog::types() as $triggerType) {
            if (in_array($triggerType, $existingTypes, true)) {
                continue;
            }

            $registry->register(new HospitalDomainTriggerExecutor(
                $triggerType,
                $this->app->make(\App\Modules\Workflow\Services\Runtime\ActionDispatcher::class),
                $this->app->make(Services\AutomationContextBuilder::class),
            ));
        }

        if (! in_array('aiPrompt', $existingTypes, true)) {
            $registry->register($this->app->make(AiPromptExecutor::class));
        }
    }

    protected function registerEvents(): void
    {
        $listener = DispatchHospitalAutomationWorkflow::class;

        $events = [
            AppointmentBooked::class,
            AppointmentCancelled::class,
            AppointmentMissed::class,
            AppointmentRescheduled::class,
            AppointmentCompleted::class,
            LabTestOrdered::class,
            LabReportReady::class,
            LabCompleted::class,
            MedicineRefillDue::class,
            InvoiceGenerated::class,
            PaymentReceived::class,
            PaymentPending::class,
            MembershipExpiry::class,
            MembershipRenewed::class,
            RewardPointsUpdated::class,
            RewardTierUpgraded::class,
            BirthdayReached::class,
            AnniversaryReached::class,
            PatientRegistered::class,
            ProcedureCompleted::class,
            MessageReceived::class,
            CampaignTriggered::class,
        ];

        foreach ($events as $event) {
            Event::listen($event, $listener);
        }
    }

    protected function loadRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/Routes/hospitalAutomation.php');
    }
}
