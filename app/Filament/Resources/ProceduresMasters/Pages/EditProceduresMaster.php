<?php

namespace App\Filament\Resources\ProceduresMasters\Pages;

use App\Filament\Resources\ProceduresMasters\ProceduresMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProceduresMaster extends EditRecord
{
    protected static string $resource = ProceduresMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
