<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('shift_id')
                    ->relationship('shift', 'name'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('discount_id')
                    ->relationship('discount', 'name'),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('paid_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('change_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('payment_method')
                    ->required()
                    ->default('cash'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('synced_at'),
            ]);
    }
}
