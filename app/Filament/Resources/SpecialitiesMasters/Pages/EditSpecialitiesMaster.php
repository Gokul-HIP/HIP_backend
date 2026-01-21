<?php

namespace App\Filament\Resources\SpecialitiesMasters\Pages;

use App\Filament\Resources\SpecialitiesMasters\SpecialitiesMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSpecialitiesMaster extends EditRecord
{
    protected static string $resource = SpecialitiesMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
