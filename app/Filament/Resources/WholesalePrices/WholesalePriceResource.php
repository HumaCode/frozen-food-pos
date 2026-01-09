<?php

namespace App\Filament\Resources\WholesalePrices;

use App\Filament\Resources\WholesalePrices\Pages\CreateWholesalePrice;
use App\Filament\Resources\WholesalePrices\Pages\EditWholesalePrice;
use App\Filament\Resources\WholesalePrices\Pages\ListWholesalePrices;
use App\Filament\Resources\WholesalePrices\Schemas\WholesalePriceForm;
use App\Filament\Resources\WholesalePrices\Tables\WholesalePricesTable;
use App\Models\WholesalePrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WholesalePriceResource extends Resource
{
    protected static ?string $model = WholesalePrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Promosi';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return WholesalePriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WholesalePricesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWholesalePrices::route('/'),
            'create' => CreateWholesalePrice::route('/create'),
            'edit' => EditWholesalePrice::route('/{record}/edit'),
        ];
    }
}
