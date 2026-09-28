<?php

namespace App\Filament\Resources\CronJobs\Pages;

use App\Filament\Resources\CronJobs\CronJobResource;
use App\Services\AdminActionLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateCronJob extends CreateRecord
{
    protected static string $resource = CronJobResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth('filament')->id();
        $data['updated_by'] = auth('filament')->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        app(AdminActionLogger::class)->record('cron.created', [
            'cron_job_id' => $this->record->id,
        ]);
    }
}
