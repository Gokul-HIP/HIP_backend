<?php

namespace App\Filament\Pages;

use App\Models\DatabaseQueueJob;
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
use UnitEnum;

class PendingQueueJobs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationParentItem = 'Queue Management';

    protected static ?string $navigationLabel = 'Pending Jobs';

    protected static ?string $title = 'Pending Jobs';

    protected static ?string $slug = 'queue-pending-jobs';

    protected static ?int $navigationSort = 91;

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
            ->query(DatabaseQueueJob::query()->whereNull('reserved_at'))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('queue')->searchable(),
                TextColumn::make('job')
                    ->label('Job')
                    ->state(fn (DatabaseQueueJob $record) => app(QueueJobManager::class)->safePayload($record)['display_name']
                        ?? app(QueueJobManager::class)->safePayload($record)['command_name']
                        ?? 'Unknown'),
                TextColumn::make('attempts')->numeric(),
                TextColumn::make('available_at')
                    ->label('Available At')
                    ->state(fn (DatabaseQueueJob $record) => $record->availableAt()?->toDateTimeString()),
                TextColumn::make('reserved_at')
                    ->label('Reserved At')
                    ->state(fn (DatabaseQueueJob $record) => $record->reservedAt()?->toDateTimeString() ?? '—'),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->state(fn (DatabaseQueueJob $record) => $record->queuedAt()?->toDateTimeString()),
            ])
            ->recordActions([
                Action::make('viewPayload')
                    ->label('View Payload')
                    ->modalHeading('Safe job metadata')
                    ->modalSubmitAction(false)
                    ->fillForm(fn (DatabaseQueueJob $record): array => [
                        'meta' => json_encode(app(QueueJobManager::class)->safePayload($record), JSON_PRETTY_PRINT),
                    ])
                    ->schema([
                        Textarea::make('meta')->label('Metadata')->disabled()->rows(16),
                    ]),
                Action::make('delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->authorize(fn (): bool => AdminAccess::canManageQueues(auth('filament')->user()))
                    ->action(function (DatabaseQueueJob $record): void {
                        app(QueueJobManager::class)->deletePending($record);
                        Notification::make()->title('Pending job deleted')->success()->send();
                    }),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc');
    }
}
