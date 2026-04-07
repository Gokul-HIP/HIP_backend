<?php

namespace App\Filament\Resources\LabTestMasters\Tables;

use App\Filament\Support\PublicDiskImagePath;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use App\Models\MasterLabtestCategory;

class LabTestMastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('test_name')
                    ->searchable(),

                TextColumn::make('test_category')
                    ->label('Category')
                    ->formatStateUsing(fn ($state) => MasterLabtestCategory::find($state)?->category_name ?? '')
                    ->searchable(),
                    
                TextColumn::make('test_code')
                    ->searchable(),

                TextColumn::make('test_price')
                    ->money()
                    ->sortable(),

                TextColumn::make('test_discount')
                    ->numeric()
                    ->sortable(),

                ImageColumn::make('test_image')
                    ->label('Image')
                    ->disk('public')
                    ->getStateUsing(fn ($record) => PublicDiskImagePath::resolve($record->test_image, 'diagnostic-lab-test'))
                    ->size(60)
                    ->square()
                    ->defaultImageUrl(asset('assets/favicon.png'))
                    ->extraImgAttributes([
                        'loading' => 'lazy',
                    ]),

                IconColumn::make('test_status')
                    ->label('Status')
                    ->state(fn ($record) => $record->test_status === 'active')
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
            ->filters([
                // SelectFilter::make('test_status')
                //     ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                //     ->default('inactive'),
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
