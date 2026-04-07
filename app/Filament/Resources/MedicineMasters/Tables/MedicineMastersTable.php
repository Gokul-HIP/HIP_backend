<?php

namespace App\Filament\Resources\MedicineMasters\Tables;

use App\Filament\Support\PublicDiskImagePath;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MedicineMastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Medicine Name')
                    ->searchable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),

                TextColumn::make('brand_name')
                    ->label('Brand')
                    ->searchable(),

                TextColumn::make('dosage_form')
                    ->label('Dosage Form')
                    ->searchable(),

                TextColumn::make('strength')
                    ->label('Strength')
                    ->searchable(),

                TextColumn::make('pack_size')
                    ->label('Pack Size')
                    ->searchable(),

                TextColumn::make('mrp')
                    ->label('MRP')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('discount')
                    ->label('Discount (%)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Expiry')
                    ->date('d M Y')
                    ->sortable(),

                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->getStateUsing(fn ($record) => PublicDiskImagePath::resolve($record->image, 'pharmacy/products'))
                    ->size(60)
                    ->square()
                    ->defaultImageUrl(asset('assets/favicon.png'))
                    ->extraImgAttributes([
                        'loading' => 'lazy',
                    ]),

                IconColumn::make('prescription_required')
                    ->label('Prescription Required')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                IconColumn::make('status')
                    ->label('Status')
                    ->state(fn ($record) => $record->status === 'active')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->alignCenter(),

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
