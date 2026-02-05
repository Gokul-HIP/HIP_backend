<?php

namespace App\Filament\Resources\MasterLabtestCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MasterLabtestCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('category_name')
                    ->required(),
            ]);
    }
}
