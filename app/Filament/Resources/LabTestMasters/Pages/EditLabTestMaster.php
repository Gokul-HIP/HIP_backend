<?php

namespace App\Filament\Resources\LabTestMasters\Pages;

use App\Filament\Resources\LabTestMasters\LabTestMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLabTestMaster extends EditRecord
{
    protected static string $resource = LabTestMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
