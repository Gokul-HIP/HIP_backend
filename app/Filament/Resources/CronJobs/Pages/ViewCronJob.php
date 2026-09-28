<?php

namespace App\Filament\Resources\CronJobs\Pages;

use App\Filament\Resources\CronJobs\CronJobResource;
use App\Models\CronJob;
use App\Services\AdminActionLogger;
use App\Services\Cron\CronJobRunner;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCronJob extends ViewRecord
{
    protected static string $resource = CronJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('activate')
                ->visible(fn (): bool => ! $this->record->is_active)
                ->color('success')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['is_active' => true, 'updated_by' => auth('filament')->id()]);
                    app(AdminActionLogger::class)->record('cron.activated', ['cron_job_id' => $this->record->id]);
                    Notification::make()->title('Cron job activated')->success()->send();
                }),
            Action::make('deactivate')
                ->visible(fn (): bool => (bool) $this->record->is_active)
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['is_active' => false, 'updated_by' => auth('filament')->id()]);
                    app(AdminActionLogger::class)->record('cron.deactivated', ['cron_job_id' => $this->record->id]);
                    Notification::make()->title('Cron job deactivated')->success()->send();
                }),
            Action::make('runNow')
                ->label('Run Now')
                ->icon('heroicon-o-bolt')
                ->authorize(fn (): bool => CronJobResource::canRunNow($this->record))
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var CronJob $record */
                    $record = $this->record;
                    $run = app(CronJobRunner::class)->runNow($record, 'manual');
                    Notification::make()
                        ->title('Run '.$run->status)
                        ->body($run->error)
                        ->status($run->status === 'success' ? 'success' : ($run->status === 'skipped' ? 'warning' : 'danger'))
                        ->send();
                }),
        ];
    }
}
