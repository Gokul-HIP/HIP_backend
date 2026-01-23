<?php

namespace App\Filament\Resources\ProceduresMasters\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use App\Models\SpecialitiesMaster;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;

class ProceduresMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Procedure Name')->required()->maxLength(255),
                Select::make('speciality_master_id')->label('Speciality')->relationship('specialityMaster', 'name')->searchable()->required()->options(SpecialitiesMaster::all()->pluck('name', 'id')),
                TextInput::make('duration')->label('Duration')->required()->numeric()->minValue(0),
                FileUpload::make('image')
                ->label('Procedure Image')
                ->image()
                ->imagePreviewHeight(150)
                ->directory('procedures')
                ->disk('public')
                ->visibility('public')
                ->imageEditor()
                ->maxSize(2048)
                ->getUploadedFileNameForStorageUsing(
                    fn (TemporaryUploadedFile $file): string =>
                        Str::uuid() . '_' . hash('sha256', now()) . '.' . $file->getClientOriginalExtension()
                )
                ->deleteUploadedFileUsing(function ($file, $record) {
                    if ($record && $record->image) {
                        Storage::disk('public')->delete($record->image);
                    }
                })
                ->columnSpanFull(),

                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextInput::make('recovery_from')
                            ->label('From')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('recovery_to')
                            ->label('To')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->rule(fn (Get $get) => 'gte:' . $get('recovery_from')),

                        Select::make('recovery_unit')
                            ->label('Unit')
                            ->options([
                                'days'   => 'Days',
                                'weeks'  => 'Weeks',
                                'months' => 'Months',
                            ])
                            ->required(),
                    ])
                    ->columnSpanFull(),


                    Hidden::make('recovery_time')
                    ->dehydrated()
                    ->mutateDehydratedStateUsing(function (Get $get) {
                        if (
                            $get('recovery_from') &&
                            $get('recovery_to') &&
                            $get('recovery_unit')
                        ) {
                            return "{$get('recovery_from')} to {$get('recovery_to')} {$get('recovery_unit')}";
                        }
                
                        return null;
                    })
                    ->afterStateHydrated(function ($state, Set $set) {
                        if (! $state) {
                            return;
                        }
                
                        if (preg_match('/(\d+)\s+to\s+(\d+)\s+(days|weeks|months)/', $state, $m)) {
                            $set('recovery_from', $m[1]);
                            $set('recovery_to', $m[2]);
                            $set('recovery_unit', $m[3]);
                        }
                    }),
                
                                                 
                TextInput::make('success_rate')->label('Success Rate')->required()->numeric()->suffix('%'),
                TextInput::make('hospitalization_days')->label('Hospitalization Days')->required()->numeric()->suffix('days'),
                TextInput::make('cost')->label('Cost')->required()->numeric()->prefix('₹'),
                Textarea::make('description')->label('Description')->columnSpanFull(),
                Toggle::make('status')->label('Status')->default(true),
            ]);
    }
}
