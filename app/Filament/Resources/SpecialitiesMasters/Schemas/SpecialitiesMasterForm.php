<?php

namespace App\Filament\Resources\SpecialitiesMasters\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SpecialitiesMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                TextInput::make('name')
                    ->label('Speciality Name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('code')
                    ->label('Code')
                    ->maxLength(100)
                    ->columnSpan(1),

                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->columnSpanFull(),

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

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required()
                    ->columnSpan(1),
            ]);
    }
}
