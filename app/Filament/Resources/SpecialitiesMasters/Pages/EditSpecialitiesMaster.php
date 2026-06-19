<?php

namespace App\Filament\Resources\SpecialitiesMasters\Pages;

use App\Filament\Resources\SpecialitiesMasters\Concerns\NormalizesSpecialityDiseasePayload;
use App\Filament\Resources\SpecialitiesMasters\SpecialitiesMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpecialitiesMaster extends EditRecord
{
    use NormalizesSpecialityDiseasePayload;

    protected static string $resource = SpecialitiesMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
