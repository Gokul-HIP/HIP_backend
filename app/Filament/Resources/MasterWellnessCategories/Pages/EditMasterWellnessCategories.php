<?php

namespace App\Filament\Resources\MasterWellnessCategories\Pages;

use App\Filament\Resources\MasterWellnessCategories\MasterWellnessCategoriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterWellnessCategories extends EditRecord
{
    protected static string $resource = MasterWellnessCategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
