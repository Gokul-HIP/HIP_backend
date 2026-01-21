<?php

namespace App\Filament\Resources\LabTestMasters\Pages;

use App\Filament\Resources\LabTestMasters\LabTestMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLabTestMasters extends ListRecords
{
    protected static string $resource = LabTestMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
