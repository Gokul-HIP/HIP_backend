<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\AdminActionLogger;
use App\Support\AdminAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class QueueSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationParentItem = 'Queue Management';

    protected static ?string $navigationLabel = 'Queue Settings';

    protected static ?string $title = 'Queue Settings';

    protected static ?string $slug = 'queue-settings';

    protected static ?int $navigationSort = 93;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return AdminAccess::canManageQueues(auth('filament')->user());
    }

    public function mount(): void
    {
        $this->data = [
            'connection' => config('queue.default'),
            'queue' => config('queue.connections.'.config('queue.default').'.queue', 'default'),
            'dispatch_enabled' => filter_var(
                Setting::get('queue.dispatch_enabled', true),
                FILTER_VALIDATE_BOOLEAN
            ),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Worker vs dispatch')
                    ->description('Disabling dispatch stops the application from queueing new jobs. It does not stop Supervisor workers already running.')
                    ->schema([
                        TextInput::make('connection')
                            ->label('Queue connection')
                            ->disabled()
                            ->helperText('Configured in .env (QUEUE_CONNECTION). Changing workers requires Supervisor, not this form.'),
                        TextInput::make('queue')
                            ->label('Queue name')
                            ->disabled(),
                        Toggle::make('dispatch_enabled')
                            ->label('Enable application dispatching')
                            ->helperText('When off, the app will not push new jobs onto the queue. Existing Supervisor workers are unaffected.'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $enabled = (bool) ($this->data['dispatch_enabled'] ?? true);
        Setting::set('queue.dispatch_enabled', $enabled, 'boolean', 'queue');
        app(AdminActionLogger::class)->record('queue.settings_updated', [
            'dispatch_enabled' => $enabled,
        ]);
        Notification::make()->title('Queue settings saved')->success()->send();
    }
}
