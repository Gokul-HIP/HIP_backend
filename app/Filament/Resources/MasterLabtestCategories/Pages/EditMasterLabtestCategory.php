<?php

namespace App\Filament\Resources\MasterLabtestCategories\Pages;

use App\Filament\Resources\MasterLabtestCategories\MasterLabtestCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterLabtestCategory extends EditRecord
{
    protected static string $resource = MasterLabtestCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
