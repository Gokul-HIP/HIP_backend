<?php

namespace App\Filament\Resources\LabTestMasters;

use App\Filament\Resources\LabTestMasters\Pages\CreateLabTestMaster;
use App\Filament\Resources\LabTestMasters\Pages\EditLabTestMaster;
use App\Filament\Resources\LabTestMasters\Pages\ListLabTestMasters;
use App\Filament\Resources\LabTestMasters\Schemas\LabTestMasterForm;
use App\Filament\Resources\LabTestMasters\Tables\LabTestMastersTable;
use App\Models\LabTestMaster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LabTestMasterResource extends Resource
{
    protected static ?string $model = LabTestMaster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'LabTestMaster';

    public static function form(Schema $schema): Schema
    {
        return LabTestMasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LabTestMastersTable::configure($table);
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
            'index' => ListLabTestMasters::route('/'),
            'create' => CreateLabTestMaster::route('/create'),
            'edit' => EditLabTestMaster::route('/{record}/edit'),
        ];
    }
}
