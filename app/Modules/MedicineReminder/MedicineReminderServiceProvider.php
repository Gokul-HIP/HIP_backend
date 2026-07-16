<?php

namespace App\Modules\MedicineReminder;

use App\Modules\MedicineReminder\Console\DispatchMedicineRemindersCommand;
use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Listeners\CreateMedicineReminderSchedules;
use App\Modules\MedicineReminder\Repositories\MedicineReminderRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MedicineReminderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MedicineReminderInterface::class, MedicineReminderRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->registerEvents();
        $this->registerCommands();
    }

    protected function loadRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/Routes/medicineReminder.php');
    }

    protected function registerEvents(): void
    {
        Event::listen(
            PrescriptionCreated::class,
            CreateMedicineReminderSchedules::class
        );
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DispatchMedicineRemindersCommand::class,
            ]);
        }
    }
}
