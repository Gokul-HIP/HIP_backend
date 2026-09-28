<?php

namespace App\Filament\Widgets;

use App\Services\Queue\QueueMetrics;
use App\Support\AdminAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QueueOverviewStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return AdminAccess::canManageQueues(auth('filament')->user());
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = app(QueueMetrics::class)->overview();
        $worker = $metrics['worker'];

        return [
            Stat::make('Pending Jobs', (string) $metrics['pending'])
                ->description($metrics['oldest_pending_age'] ? 'Oldest: '.$metrics['oldest_pending_age'] : 'No pending jobs')
                ->color('warning'),
            Stat::make('Processing', (string) $metrics['processing'])
                ->description('Reserved / currently claimed')
                ->color('info'),
            Stat::make('Failed Jobs', (string) $metrics['failed'])
                ->description($metrics['latest_failure_job'] ? 'Latest: '.$metrics['latest_failure_job'] : 'No failures')
                ->color('danger'),
            Stat::make('Worker', (string) $worker['label'])
                ->description($worker['detail'])
                ->color(match ($worker['status']) {
                    'active' => 'success',
                    'stale' => 'warning',
                    default => 'gray',
                }),
        ];
    }
}
