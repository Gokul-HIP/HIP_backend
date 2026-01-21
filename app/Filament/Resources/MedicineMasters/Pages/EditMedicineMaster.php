<?php

namespace App\Filament\Resources\MedicineMasters\Pages;

use App\Filament\Resources\MedicineMasters\MedicineMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMedicineMaster extends EditRecord
{
    protected static string $resource = MedicineMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
