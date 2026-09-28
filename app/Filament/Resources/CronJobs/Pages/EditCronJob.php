<?php

namespace App\Filament\Resources\CronJobs\Pages;

use App\Filament\Resources\CronJobs\CronJobResource;
use App\Services\AdminActionLogger;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCronJob extends EditRecord
{
    protected static string $resource = CronJobResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth('filament')->id();

        return $data;
    }

    protected function afterSave(): void
    {
        app(AdminActionLogger::class)->record('cron.edited', [
            'cron_job_id' => $this->record->id,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(function (): void {
                    app(AdminActionLogger::class)->record('cron.deleted', [
                        'cron_job_id' => $this->record->id,
                    ]);
                }),
        ];
    }
}
