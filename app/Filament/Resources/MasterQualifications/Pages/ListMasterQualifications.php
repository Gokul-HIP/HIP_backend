<?php

namespace App\Filament\Resources\MasterQualifications\Pages;

use App\Filament\Resources\MasterQualifications\MasterQualificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterQualifications extends ListRecords
{
    protected static string $resource = MasterQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
