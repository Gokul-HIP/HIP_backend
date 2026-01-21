<?php

namespace App\Filament\Resources\ProceduresMasters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\IconColumn;

class ProceduresMastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Procedure Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('specialityMaster.name')
                    ->label('Speciality')
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->suffix(' mins'),

                TextColumn::make('cost')
                    ->label('Cost')
                    ->money('INR'),

                IconColumn::make('status')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('speciality_master_id')
                    ->label('Speciality')
                    ->relationship('specialityMaster', 'name'),

                TernaryFilter::make('status'),
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
