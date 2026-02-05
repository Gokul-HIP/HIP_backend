<?php

namespace App\Filament\Resources\MasterLabtestCategories;

use App\Filament\Resources\MasterLabtestCategories\Pages\CreateMasterLabtestCategory;
use App\Filament\Resources\MasterLabtestCategories\Pages\EditMasterLabtestCategory;
use App\Filament\Resources\MasterLabtestCategories\Pages\ListMasterLabtestCategories;
use App\Filament\Resources\MasterLabtestCategories\Schemas\MasterLabtestCategoryForm;
use App\Filament\Resources\MasterLabtestCategories\Tables\MasterLabtestCategoriesTable;
use App\Models\MasterLabtestCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MasterLabtestCategoryResource extends Resource
{
    protected static ?string $model = MasterLabtestCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'MasterLabtestCategory';

    public static function form(Schema $schema): Schema
    {
        return MasterLabtestCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterLabtestCategoriesTable::configure($table);
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
            'index' => ListMasterLabtestCategories::route('/'),
            'create' => CreateMasterLabtestCategory::route('/create'),
            'edit' => EditMasterLabtestCategory::route('/{record}/edit'),
        ];
    }
}
