<?php

namespace App\Filament\Resources\ProceduresMasters\Pages;

use App\Filament\Resources\ProceduresMasters\ProceduresMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProceduresMasters extends ListRecords
{
    protected static string $resource = ProceduresMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
