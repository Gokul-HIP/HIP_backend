<?php

namespace App\Filament\Resources\MasterWellnessCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;

class MasterWellnessCategoriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('parent_category')
                    ->label('Parent Category')
                    ->required()
                    ->maxLength(255),

                Repeater::make('submenus')
                    ->label('Sub Categories')
                    ->schema([
                        TextInput::make('value')
                            ->label('Submenu')
                            ->required(),
                    ])
                    ->columnSpanFull()
                    ->addActionLabel('Add Submenu')
                    ->reorderable()
                    ->collapsible(),
            ]);
    }
}
