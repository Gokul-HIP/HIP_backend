<?php

namespace App\Filament\Resources\MedicineMasters;

use App\Filament\Resources\MedicineMasters\Pages\CreateMedicineMaster;
use App\Filament\Resources\MedicineMasters\Pages\EditMedicineMaster;
use App\Filament\Resources\MedicineMasters\Pages\ListMedicineMasters;
use App\Filament\Resources\MedicineMasters\Schemas\MedicineMasterForm;
use App\Filament\Resources\MedicineMasters\Tables\MedicineMastersTable;
use App\Models\MedicineMaster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MedicineMasterResource extends Resource
{
    protected static ?string $model = MedicineMaster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'MedicineMaster';

    public static function form(Schema $schema): Schema
    {
        return MedicineMasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicineMastersTable::configure($table);
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
            'index' => ListMedicineMasters::route('/'),
            'create' => CreateMedicineMaster::route('/create'),
            'edit' => EditMedicineMaster::route('/{record}/edit'),
        ];
    }
}
