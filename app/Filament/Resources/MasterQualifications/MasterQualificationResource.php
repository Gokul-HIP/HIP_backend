<?php

namespace App\Filament\Resources\MasterQualifications;

use App\Filament\Resources\MasterQualifications\Pages\CreateMasterQualification;
use App\Filament\Resources\MasterQualifications\Pages\EditMasterQualification;
use App\Filament\Resources\MasterQualifications\Pages\ListMasterQualifications;
use App\Filament\Resources\MasterQualifications\Schemas\MasterQualificationForm;
use App\Filament\Resources\MasterQualifications\Tables\MasterQualificationsTable;
use App\Models\MasterQualification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MasterQualificationResource extends Resource
{
    protected static ?string $model = MasterQualification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'MasterQualification';

    public static function form(Schema $schema): Schema
    {
        return MasterQualificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterQualificationsTable::configure($table);
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
            'index' => ListMasterQualifications::route('/'),
            'create' => CreateMasterQualification::route('/create'),
            'edit' => EditMasterQualification::route('/{record}/edit'),
        ];
    }
}
