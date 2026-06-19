<?php

namespace App\Filament\Resources\SpecialitiesMasters\Pages;

use App\Filament\Resources\SpecialitiesMasters\Concerns\NormalizesSpecialityDiseasePayload;
use App\Filament\Resources\SpecialitiesMasters\SpecialitiesMasterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpecialitiesMaster extends CreateRecord
{
    use NormalizesSpecialityDiseasePayload;

    protected static string $resource = SpecialitiesMasterResource::class;
}
