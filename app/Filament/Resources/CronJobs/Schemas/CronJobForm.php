<?php

namespace App\Filament\Resources\CronJobs\Schemas;

use App\Services\Cron\CronCommandRegistry;
use App\Services\Cron\CronSchedule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CronJobForm
{
    public static function configure(Schema $schema): Schema
    {
        $registry = app(CronCommandRegistry::class);
        $schedule = app(CronSchedule::class);

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                Select::make('command')
                    ->label('Command')
                    ->options($registry->options())
                    ->required()
                    ->searchable()
                    ->helperText('Only approved Artisan commands can be scheduled. Shell commands are not allowed.'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('schedule_type')
                    ->label('Schedule Type')
                    ->options($schedule->presets())
                    ->required()
                    ->live()
                    ->default('hourly'),

                TextInput::make('schedule')
                    ->label('Cron Expression')
                    ->required(fn (Get $get): bool => $get('schedule_type') === 'custom')
                    ->visible(fn (Get $get): bool => $get('schedule_type') === 'custom')
                    ->placeholder('* * * * *')
                    ->helperText('Standard 5-field cron expression.')
                    ->rule(function () use ($schedule) {
                        return function (string $attribute, mixed $value, \Closure $fail) use ($schedule): void {
                            if (! is_string($value) || ! $schedule->isValid($value)) {
                                $fail('Enter a valid 5-field cron expression.');
                            }
                        };
                    }),

                Select::make('timezone')
                    ->label('Timezone')
                    ->options([
                        'Asia/Kolkata' => 'Asia/Kolkata',
                        'UTC' => 'UTC',
                    ])
                    ->default(config('cron.timezone', 'Asia/Kolkata'))
                    ->required()
                    ->searchable(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive jobs stay in the catalog but are not executed by the scheduler.'),
            ]);
    }
}
