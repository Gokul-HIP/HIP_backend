<?php

namespace App\Modules\Automation;

use App\Models\DoctorBooking;
use App\Models\Persons;
use App\Modules\Automation\Events\AnniversaryReached;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\AppointmentCancelled;
use App\Modules\Automation\Events\AppointmentCompleted;
use App\Modules\Automation\Events\AppointmentMissed;
use App\Modules\Automation\Events\AppointmentRescheduled;
use App\Modules\Automation\Events\BirthdayReached;
use App\Modules\Automation\Events\CampaignTriggered;
use App\Modules\Automation\Events\InvoiceGenerated;
use App\Modules\Automation\Events\LabCompleted;
use App\Modules\Automation\Events\LabReportReady;
use App\Modules\Automation\Events\LabTestOrdered;
use App\Modules\Automation\Events\MedicineRefillDue;
use App\Modules\Automation\Events\MembershipExpiry;
use App\Modules\Automation\Events\MembershipRenewed;
use App\Modules\Automation\Events\MessageReceived;
use App\Modules\Automation\Events\PatientRegistered;


use App\Modules\Automation\Events\PaymentPending;
use App\Modules\Automation\Events\PaymentReceived;
use App\Modules\Automation\Events\ProcedureCompleted;
use App\Modules\Automation\Events\RewardPointsUpdated;
use App\Modules\Automation\Events\RewardTierUpgraded;
use App\Modules\Automation\Events\ScheduledEvent;
use App\Modules\Workflow\NodeProcessors\AiPromptNodeProcessor;
use App\Modules\Workflow\NodeProcessors\HospitalDomainTriggerNodeProcessor;
use App\Modules\Automation\Listeners\AppointmentBookedListener;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Automation\Observers\DoctorBookingObserver;
use App\Modules\Automation\Observers\PersonsObserver;
use App\Modules\Automation\Support\TriggerCatalog;
use App\Modules\Workflow\NodeProcessorRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\RunAutomationTestCommand::class,
                Console\Commands\DispatchBirthdayReachedCommand::class,
                Console\Commands\DispatchAnniversaryReachedCommand::class,
                Console\Commands\DispatchScheduledEventsCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->registerExecutors();
        $this->registerEvents();
        $this->registerObservers();
        $this->loadRoutes();
    }

    protected function registerExecutors(): void
    {
        $registry = $this->app->make(NodeProcessorRegistry::class);
        $existingTypes = array_map(fn ($e) => $e->type(), $registry->all());

        foreach (TriggerCatalog::types() as $triggerType) {
            if (in_array($triggerType, $existingTypes, true)) {
                continue;
            }

            $registry->register(new HospitalDomainTriggerNodeProcessor(
                $triggerType,
                $this->app->make(\App\Modules\Workflow\Services\Runtime\ActionDispatcher::class),
                $this->app->make(Engine\AutomationContextBuilder::class),
            ));
        }

        if (! in_array('aiPrompt', $existingTypes, true)) {
            $registry->register($this->app->make(AiPromptNodeProcessor::class));
        }
    }

    protected function registerEvents(): void
    {
        Event::listen(AppointmentBooked::class, AppointmentBookedListener::class);

        $listener = DispatchHospitalAutomationWorkflow::class;

        $events = [
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
            ScheduledEvent::class,
        ];

        foreach ($events as $event) {
            Event::listen($event, $listener);
        }
    }

    protected function registerObservers(): void
    {
        DoctorBooking::observe(DoctorBookingObserver::class);
        Persons::observe(PersonsObserver::class);
    }

    protected function loadRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/Http/Routes/hospitalAutomation.php');
    }
}
