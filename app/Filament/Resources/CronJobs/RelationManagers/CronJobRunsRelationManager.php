<?php

namespace App\Filament\Resources\CronJobs\RelationManagers;

use App\Models\CronJobRun;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CronJobRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'runs';

    protected static ?string $title = 'Execution History';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('status')->badge(),
                TextEntry::make('triggered_by')->label('Triggered By'),
                TextEntry::make('started_at')->dateTime('Y-m-d H:i:s'),
                TextEntry::make('finished_at')->dateTime('Y-m-d H:i:s'),
                TextEntry::make('duration')
                    ->state(fn (CronJobRun $record) => $record->formattedDuration() ?? '—'),
                TextEntry::make('output')->markdown()->columnSpanFull(),
                TextEntry::make('error')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('started_at')->label('Started')->dateTime('Y-m-d H:i:s')->sortable(),
                TextColumn::make('finished_at')->label('Finished')->dateTime('Y-m-d H:i:s'),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->state(fn (CronJobRun $record) => $record->formattedDuration() ?? '—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        'running' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('triggered_by')->label('Triggered By'),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->label('Details'),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc');
    }
}
