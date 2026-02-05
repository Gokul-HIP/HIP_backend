<?php

namespace App\Filament\Resources\MasterLabtestCategories\Pages;

use App\Filament\Resources\MasterLabtestCategories\MasterLabtestCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterLabtestCategories extends ListRecords
{
    protected static string $resource = MasterLabtestCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
