<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('product'),
                TextInput::make('discount_type')
                    ->required()
                    ->default('percentage'),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('product_id')
                    ->relationship('product', 'name'),
                TextInput::make('min_purchase')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
            ]);
    }
}
