<?php

namespace App\Filament\Resources\CronJobs\Pages;

use App\Filament\Resources\CronJobs\CronJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCronJobs extends ListRecords
{
    protected static string $resource = CronJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
