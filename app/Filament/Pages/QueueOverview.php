<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\QueueOverviewStats;
use App\Services\Queue\QueueMetrics;
use App\Support\AdminAccess;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class QueueOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Queue Management';

    protected static ?string $title = 'Queue Management';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'queue-management';

    public static function canAccess(): bool
    {
        return AdminAccess::canManageQueues(auth('filament')->user());
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            QueueOverviewStats::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        $metrics = app(QueueMetrics::class)->overview();
        $worker = $metrics['worker'];

        return $schema
            ->components([
                Section::make('Queue details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('connection')
                            ->label('Queue connection')
                            ->state($metrics['connection']),
                        TextEntry::make('queue')
                            ->label('Queue name')
                            ->state($metrics['queue']),
                        TextEntry::make('dispatch')
                            ->label('Application dispatching')
                            ->state($metrics['dispatch_enabled'] ? 'Enabled' : 'Disabled')
                            ->badge()
                            ->color($metrics['dispatch_enabled'] ? 'success' : 'danger'),
                        TextEntry::make('oldest')
                            ->label('Oldest pending')
                            ->state($metrics['oldest_pending_age'] ?? 'None'),
                        TextEntry::make('latest_failure')
                            ->label('Latest failure')
                            ->state(
                                $metrics['latest_failure_job']
                                    ? $metrics['latest_failure_job'].(
                                        $metrics['latest_failure_at']
                                            ? ' — '.$metrics['latest_failure_at']->format('H:i')
                                            : ''
                                    )
                                    : 'None'
                            ),
                        TextEntry::make('worker_heartbeat')
                            ->label('Last worker heartbeat')
                            ->state($worker['last_heartbeat']?->toDateTimeString() ?? 'None'),
                        TextEntry::make('note')
                            ->label('Successful jobs')
                            ->state($metrics['successful_jobs_note'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
