<?php

namespace App\Filament\Resources\ProceduresMasters\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use App\Models\SpecialitiesMaster;

class ProceduresMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Procedure Name')->required()->maxLength(255),
                Select::make('speciality_master_id')->label('Speciality')->relationship('specialityMaster', 'name')->searchable()->required()->options(SpecialitiesMaster::all()->pluck('name', 'id')),
                TextInput::make('duration')->label('Duration(mins)')->required()->numeric()->minValue(0),
                TextInput::make('cost')->label('Cost')->required()->numeric()->prefix('₹'),
                Textarea::make('description')->label('Description')->columnSpanFull(),
                Toggle::make('status')->label('Status')->default(true),
            ]);
    }
}
