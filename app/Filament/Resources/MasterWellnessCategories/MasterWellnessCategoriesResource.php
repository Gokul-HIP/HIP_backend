<?php

namespace App\Filament\Resources\MasterWellnessCategories;

use App\Filament\Resources\MasterWellnessCategories\Pages\CreateMasterWellnessCategories;
use App\Filament\Resources\MasterWellnessCategories\Pages\EditMasterWellnessCategories;
use App\Filament\Resources\MasterWellnessCategories\Pages\ListMasterWellnessCategories;
use App\Filament\Resources\MasterWellnessCategories\Schemas\MasterWellnessCategoriesForm;
use App\Filament\Resources\MasterWellnessCategories\Tables\MasterWellnessCategoriesTable;
use App\Models\MasterWellnessCategories;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MasterWellnessCategoriesResource extends Resource
{
    protected static ?string $model = MasterWellnessCategories::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'MasterWellnessCategories';

    public static function form(Schema $schema): Schema
    {
        return MasterWellnessCategoriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterWellnessCategoriesTable::configure($table);
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
            'index' => ListMasterWellnessCategories::route('/'),
            'create' => CreateMasterWellnessCategories::route('/create'),
            'edit' => EditMasterWellnessCategories::route('/{record}/edit'),
        ];
    }
}
