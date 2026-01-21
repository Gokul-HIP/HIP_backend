<?php

namespace App\Filament\Resources\SpecialitiesMasters\Schemas;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
class SpecialitiesMasterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextColumn::make('name'),
                TextColumn::make('code'),
                TextColumn::make('description'),
                TextColumn::make('display_image'),
                TextColumn::make('status'),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),
                TextColumn::make('deleted_at'),
            ]);
    }
}
