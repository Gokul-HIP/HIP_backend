<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\SettingsServiceProvider::class,
    App\Providers\Filament\MasterPanelProvider::class,
    App\Modules\Workflow\WorkflowServiceProvider::class,
    App\Modules\Automation\AutomationServiceProvider::class,
    App\Modules\MedicineReminder\MedicineReminderServiceProvider::class,
];
