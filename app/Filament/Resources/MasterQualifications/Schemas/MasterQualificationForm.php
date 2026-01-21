<?php

namespace App\Filament\Resources\MasterQualifications\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MasterQualificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull()
                    ->default(null)
                    ->label('Qualification Name')
                    ->maxLength(255)
                    ->placeholder('Enter qualification name'),

                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull()
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('Enter qualification description'),
            ]);
    }
}
