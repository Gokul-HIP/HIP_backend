<?php

namespace App\Filament\Resources\MedicineMasters\Pages;

use App\Filament\Resources\MedicineMasters\MedicineMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMedicineMasters extends ListRecords
{
    protected static string $resource = MedicineMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
