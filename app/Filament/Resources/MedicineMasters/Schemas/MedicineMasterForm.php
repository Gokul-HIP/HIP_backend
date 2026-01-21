<?php

namespace App\Filament\Resources\MedicineMasters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;

class MedicineMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                TextInput::make('name')
                    ->label('Medicine Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->label('Medicine Code')
                    ->required()
                    ->maxLength(100),

                TextInput::make('category')
                    ->label('Category')
                    ->maxLength(255),

                TextInput::make('brand_name')
                    ->label('Brand Name')
                    ->maxLength(255),

                TextInput::make('dosage_form')
                    ->label('Dosage Form')
                    ->maxLength(100),

                TextInput::make('strength')
                    ->label('Strength')
                    ->maxLength(100),

                TextInput::make('pack_size')
                    ->label('Pack Size')
                    ->maxLength(100),

                TextInput::make('mrp')
                    ->label('MRP')
                    ->numeric()
                    ->prefix('₹')
                    ->rule('min:0'),

                TextInput::make('selling_price')
                    ->label('Selling Price')
                    ->numeric()
                    ->prefix('₹')
                    ->rule('min:0'),

                TextInput::make('discount')
                    ->label('Discount (%)')
                    ->numeric()
                    ->suffix('%')
                    ->default(0)
                    ->rule('min:0')
                    ->rule('max:100'),

                TextInput::make('stock_quantity')
                    ->label('Stock Quantity')
                    ->numeric()
                    ->default(0)
                    ->rule('min:0'),

                DatePicker::make('expiry_date')
                    ->label('Expiry Date'),

                TextInput::make('batch_number')
                    ->label('Batch Number')
                    ->maxLength(100),

                Select::make('prescription_required')
                    ->label('Prescription Required')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ])
                    ->required(),

                FileUpload::make('image')
                    ->label('Medicine Image')
                    ->image()
                    ->imagePreviewHeight(150)
                    ->directory('pharmacy/products')
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

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('status')
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
