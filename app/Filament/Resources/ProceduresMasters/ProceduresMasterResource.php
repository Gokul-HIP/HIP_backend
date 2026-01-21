<?php

namespace App\Filament\Resources\ProceduresMasters;

use App\Filament\Resources\ProceduresMasters\Pages\CreateProceduresMaster;
use App\Filament\Resources\ProceduresMasters\Pages\EditProceduresMaster;
use App\Filament\Resources\ProceduresMasters\Pages\ListProceduresMasters;
use App\Filament\Resources\ProceduresMasters\Schemas\ProceduresMasterForm;
use App\Filament\Resources\ProceduresMasters\Tables\ProceduresMastersTable;
use App\Models\ProcedureMaster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProceduresMasterResource extends Resource
{
    protected static ?string $model = ProcedureMaster::class;

    protected static string|BackedEnum|null $navigationIcon =  Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProceduresMasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProceduresMastersTable::configure($table);
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
            'index' => ListProceduresMasters::route('/'),
            'create' => CreateProceduresMaster::route('/create'),
            'edit' => EditProceduresMaster::route('/{record}/edit'),
        ];
    }
}
