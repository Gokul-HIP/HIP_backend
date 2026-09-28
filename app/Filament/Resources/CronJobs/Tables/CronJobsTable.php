<?php

namespace App\Filament\Resources\CronJobs\Tables;

use App\Models\CronJob;
use App\Services\AdminActionLogger;
use App\Services\Cron\CronJobRunner;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CronJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('command')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('schedule_display')
                    ->label('Schedule')
                    ->state(fn (CronJob $record) => $record->scheduleLabel()),
                TextColumn::make('timezone')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('last_run_at')
                    ->label('Last Run')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('next_run_at')
                    ->label('Next Run')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('last_status')
                    ->label('Last Result')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        'running' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                SelectFilter::make('last_status')
                    ->label('Last Result')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'skipped' => 'Skipped',
                        'running' => 'Running',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('View Logs'),
                EditAction::make(),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (CronJob $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (CronJob $record): void {
                        $record->update(['is_active' => true, 'updated_by' => auth('filament')->id()]);
                        app(AdminActionLogger::class)->record('cron.activated', ['cron_job_id' => $record->id]);
                        Notification::make()->title('Cron job activated')->success()->send();
                    }),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (CronJob $record): bool => (bool) $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (CronJob $record): void {
                        $record->update(['is_active' => false, 'updated_by' => auth('filament')->id()]);
                        app(AdminActionLogger::class)->record('cron.deactivated', ['cron_job_id' => $record->id]);
                        Notification::make()->title('Cron job deactivated')->success()->send();
                    }),
                Action::make('runNow')
                    ->label('Run Now')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->authorize('runNow')
                    ->requiresConfirmation()
                    ->action(function (CronJob $record): void {
                        $run = app(CronJobRunner::class)->runNow($record, 'manual');
                        Notification::make()
                            ->title('Run '.($run->status === 'success' ? 'completed' : $run->status))
                            ->body($run->error)
                            ->status($run->status === 'success' ? 'success' : ($run->status === 'skipped' ? 'warning' : 'danger'))
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-play')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(function (CronJob $record): void {
                                $record->update(['is_active' => true, 'updated_by' => auth('filament')->id()]);
                                app(AdminActionLogger::class)->record('cron.activated', ['cron_job_id' => $record->id]);
                            });
                        }),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(function (CronJob $record): void {
                                $record->update(['is_active' => false, 'updated_by' => auth('filament')->id()]);
                                app(AdminActionLogger::class)->record('cron.deactivated', ['cron_job_id' => $record->id]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
