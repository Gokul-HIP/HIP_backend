<?php

namespace App\Filament\Resources\MasterQualifications\Pages;

use App\Filament\Resources\MasterQualifications\MasterQualificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterQualification extends EditRecord
{
    protected static string $resource = MasterQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
