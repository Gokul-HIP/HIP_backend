<?php

namespace App\Filament\Resources\LabTestMasters\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\MasterLabtestCategory;

class LabTestMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                TextInput::make('test_name')
                    ->label('Test Name')
                    ->required()
                    ->maxLength(255),

                Select::make('test_category')
                    ->label('Category')
                    ->options(MasterLabtestCategory::all()->pluck('category_name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                // TextInput::make('test_category')
                //     ->label('Category')
                //     ->required()
                //     ->maxLength(255),

                TextInput::make('test_code')
                    ->label('Test Code')
                    ->maxLength(100),

                Textarea::make('test_description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('test_price')
                    ->label('Price')
                    ->numeric()
                    ->required()
                    ->prefix('₹')
                    ->rule('min:0'),

                TextInput::make('test_discount')
                    ->suffix('₹')
                    ->label('Discount Price (₹)')
                    ->numeric()
                    ->rule('min:0'),

                
                FileUpload::make('test_image')
                    ->label('Test Image')
                    ->image()
                    ->imagePreviewHeight(150)
                    ->directory('diagnostic-lab-test')
                    ->disk('public')
                    ->visibility('public')
                    ->imageEditor()
                    ->maxSize(2048)
                    ->getUploadedFileNameForStorageUsing(
                        fn (TemporaryUploadedFile $file): string =>
                            Str::uuid() . '_' . hash('sha256', now()) . '.' . $file->getClientOriginalExtension()
                    )
                    ->deleteUploadedFileUsing(function ($file, $record) {
                        if ($record && $record->test_image) {
                            Storage::disk('public')->delete($record->test_image);
                        }
                    })
                    ->columnSpanFull(),

                Select::make('test_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('inactive')
                    ->required(),
            ]);
    }
}
