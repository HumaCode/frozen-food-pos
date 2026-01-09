<?php

namespace App\Filament\Resources\CashFlows\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CashFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('shift_id')
                    ->relationship('shift', 'name'),
                TextInput::make('type')
                    ->required()
                    ->default('in'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('description')
                    ->required(),
            ]);
    }
}
