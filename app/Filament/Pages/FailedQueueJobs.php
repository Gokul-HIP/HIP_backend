<?php

namespace App\Filament\Pages;

use App\Models\FailedQueueJob;
use App\Services\Queue\QueueJobManager;
use App\Support\AdminAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Throwable;
use UnitEnum;

class FailedQueueJobs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationParentItem = 'Queue Management';

    protected static ?string $navigationLabel = 'Failed Jobs';

    protected static ?string $title = 'Failed Jobs';

    protected static ?string $slug = 'queue-failed-jobs';

    protected static ?int $navigationSort = 92;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageQueues(auth('filament')->user());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(FailedQueueJob::query())
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('uuid')->copyable()->toggleable(),
                TextColumn::make('connection'),
                TextColumn::make('queue'),
                TextColumn::make('job')
                    ->label('Job')
                    ->state(fn (FailedQueueJob $record) => app(QueueJobManager::class)->safePayload($record)['display_name']
                        ?? app(QueueJobManager::class)->safePayload($record)['command_name']
                        ?? 'Unknown'),
                TextColumn::make('failed_at')->dateTime('Y-m-d H:i:s')->sortable(),
            ])
            ->recordActions([
                Action::make('viewException')
                    ->label('View Exception')
                    ->modalSubmitAction(false)
                    ->fillForm(fn (FailedQueueJob $record): array => [
                        'exception' => app(QueueJobManager::class)->safeException($record) ?? '—',
                    ])
                    ->schema([
                        Textarea::make('exception')->disabled()->rows(16),
                    ]),
                Action::make('retry')
                    ->requiresConfirmation()
                    ->authorize(fn (): bool => AdminAccess::canManageQueues(auth('filament')->user()))
                    ->action(function (FailedQueueJob $record): void {
                        try {
                            app(QueueJobManager::class)->retryFailed($record);
                            Notification::make()->title('Failed job queued for retry')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('Retry failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->authorize(fn (): bool => AdminAccess::canManageQueues(auth('filament')->user()))
                    ->action(function (FailedQueueJob $record): void {
                        app(QueueJobManager::class)->deleteFailed($record);
                        Notification::make()->title('Failed job deleted')->success()->send();
                    }),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc');
    }
}
