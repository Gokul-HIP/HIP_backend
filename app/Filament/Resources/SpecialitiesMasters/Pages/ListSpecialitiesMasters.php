<?php

namespace App\Filament\Resources\SpecialitiesMasters\Pages;

use App\Filament\Resources\SpecialitiesMasters\SpecialitiesMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSpecialitiesMasters extends ListRecords
{
    protected static string $resource = SpecialitiesMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
