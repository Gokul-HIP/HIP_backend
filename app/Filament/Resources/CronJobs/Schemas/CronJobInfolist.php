<?php

namespace App\Filament\Resources\CronJobs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CronJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cron Job')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive')
                            ->color(fn ($state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('command')->label('Command')->copyable(),
                        TextEntry::make('schedule_label')
                            ->label('Schedule')
                            ->state(fn ($record) => $record->scheduleLabel().' ('.$record->schedule.')'),
                        TextEntry::make('timezone')->label('Timezone'),
                        TextEntry::make('description')->label('Description')->columnSpanFull(),
                        TextEntry::make('last_run_at')->label('Last Run')->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('last_status')
                            ->label('Last Status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'success' => 'success',
                                'failed' => 'danger',
                                'skipped' => 'warning',
                                'running' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('last_duration')
                            ->label('Last Duration')
                            ->state(fn ($record) => $record->formattedLastDuration() ?? '—'),
                        TextEntry::make('next_run_at')->label('Next Run')->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('last_error')
                            ->label('Last Error')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
