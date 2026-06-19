<?php

namespace App\Filament\Resources\SpecialitiesMasters\Tables;

use App\Filament\Support\PublicDiskImagePath;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;

class SpecialitiesMastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                ImageColumn::make('display_image')
                    ->label('Image')
                    ->disk('public')
                    ->getStateUsing(fn ($record) => PublicDiskImagePath::resolve($record->display_image, 'speciality'))
                    ->size(50)
                    ->square()
                    ->defaultImageUrl(asset('assets/favicon.png'))
                    ->alignCenter(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('department_name')
                    ->label('Department')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('diseases')
                    ->label('Diseases')
                    ->badge()
                    ->state(fn ($record) => count($record->normalizedDiseaseEntries()))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter(),

                TextColumn::make('description')
                    ->limit(40)
                    ->wrap(),

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
