<?php

namespace App\Filament\Resources\MasterWellnessCategories\Pages;

use App\Filament\Resources\MasterWellnessCategories\MasterWellnessCategoriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterWellnessCategories extends ListRecords
{
    protected static string $resource = MasterWellnessCategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
