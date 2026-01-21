<?php

namespace App\Filament\Resources\SpecialitiesMasters;

use App\Filament\Resources\SpecialitiesMasters\Pages\CreateSpecialitiesMaster;
use App\Filament\Resources\SpecialitiesMasters\Pages\EditSpecialitiesMaster;
use App\Filament\Resources\SpecialitiesMasters\Pages\ListSpecialitiesMasters;
use App\Filament\Resources\SpecialitiesMasters\Pages\ViewSpecialitiesMaster;
use App\Filament\Resources\SpecialitiesMasters\Schemas\SpecialitiesMasterForm;
use App\Filament\Resources\SpecialitiesMasters\Schemas\SpecialitiesMasterInfolist;
use App\Filament\Resources\SpecialitiesMasters\Tables\SpecialitiesMastersTable;
use App\Models\SpecialitiesMaster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SpecialitiesMasterResource extends Resource
{
    protected static ?string $model = SpecialitiesMaster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SpecialitiesMasterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SpecialitiesMasterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpecialitiesMastersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecialitiesMasters::route('/'),
            'create' => CreateSpecialitiesMaster::route('/create'),
            'edit' => EditSpecialitiesMaster::route('/{record}/edit'),
        ];
    }
}
