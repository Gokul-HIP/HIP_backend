<?php

namespace App\Filament\Resources\CronJobs;

use App\Filament\Resources\CronJobs\Pages\CreateCronJob;
use App\Filament\Resources\CronJobs\Pages\EditCronJob;
use App\Filament\Resources\CronJobs\Pages\ListCronJobs;
use App\Filament\Resources\CronJobs\Pages\ViewCronJob;
use App\Filament\Resources\CronJobs\RelationManagers\CronJobRunsRelationManager;
use App\Filament\Resources\CronJobs\Schemas\CronJobForm;
use App\Filament\Resources\CronJobs\Schemas\CronJobInfolist;
use App\Filament\Resources\CronJobs\Tables\CronJobsTable;
use App\Models\CronJob;
use App\Support\AdminAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CronJobResource extends Resource
{
    protected static ?string $model = CronJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Cron Jobs';

    protected static ?string $modelLabel = 'Cron Job';

    protected static ?string $pluralModelLabel = 'Cron Jobs';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 80;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageCron(auth('filament')->user());
    }

    public static function form(Schema $schema): Schema
    {
        return CronJobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CronJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CronJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CronJobRunsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCronJobs::route('/'),
            'create' => CreateCronJob::route('/create'),
            'view' => ViewCronJob::route('/{record}'),
            'edit' => EditCronJob::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'command'];
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * @param  CronJob  $record
     */
    public static function canRunNow(Model $record): bool
    {
        return static::can('runNow', $record);
    }
}
