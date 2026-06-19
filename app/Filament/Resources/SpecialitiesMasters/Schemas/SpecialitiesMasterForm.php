<?php

namespace App\Filament\Resources\SpecialitiesMasters\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SpecialitiesMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Speciality Details')
                    ->description('Basic speciality information shown across the platform.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Speciality Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('code')
                                ->label('Code')
                                ->maxLength(100)
                                ->placeholder('e.g. CARD, ORTH'),

                            TextInput::make('description')
                                ->label('Description')
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'active'   => 'Active',
                                    'inactive' => 'Inactive',
                                ])
                                ->default('active')
                                ->required()
                                ->native(false),
                        ]),

                        FileUpload::make('display_image')
                            ->label('Speciality Image')
                            ->image()
                            ->imagePreviewHeight(150)
                            ->directory('speciality')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string =>
                                    Str::uuid() . '_' . hash('sha256', now()) . '.' . $file->getClientOriginalExtension()
                            )
                            ->deleteUploadedFileUsing(function ($file, $record) {
                                if ($record?->display_image) {
                                    Storage::disk('public')->delete($record->display_image);
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Department Grouping')
                    ->description('Groups diseases in the mobile app under a department heading.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('department_name')
                                ->label('Department Name')
                                ->maxLength(255)
                                ->placeholder('e.g. Heart & Vascular Care')
                                ->helperText('Optional. Used when listing diseases in the app.'),

                            FileUpload::make('department_image')
                                ->label('Department Image')
                                ->image()
                                ->imagePreviewHeight(120)
                                ->directory('disease-departments')
                                ->disk('public')
                                ->visibility('public')
                                ->maxSize(2048)
                                ->getUploadedFileNameForStorageUsing(
                                    fn (TemporaryUploadedFile $file): string =>
                                        Str::uuid() . '_' . hash('sha256', now()) . '.' . $file->getClientOriginalExtension()
                                )
                                ->deleteUploadedFileUsing(function ($file, $record) {
                                    if ($record?->department_image) {
                                        Storage::disk('public')->delete($record->department_image);
                                    }
                                }),
                        ]),
                    ]),

                Section::make('Diseases')
                    ->description('Add multiple diseases under this speciality. Each disease can have its own about text, symptoms, and recommended tests.')
                    ->schema([
                        Repeater::make('diseases')
                            ->label('')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Disease Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Textarea::make('about')
                                    ->label('About')
                                    ->rows(3)
                                    ->maxLength(2000)
                                    ->columnSpanFull(),

                                TagsInput::make('symptoms')
                                    ->label('Symptoms')
                                    ->placeholder('Type a symptom and press Enter')
                                    ->splitKeys(['Tab', ','])
                                    ->columnSpanFull(),

                                TagsInput::make('recommended_tests')
                                    ->label('Recommended Tests')
                                    ->placeholder('Type a test name and press Enter')
                                    ->splitKeys(['Tab', ','])
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->addActionLabel('Add disease')
                            ->reorderable()
                            ->cloneable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null)
                                ? (string) $state['name']
                                : 'New disease')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
