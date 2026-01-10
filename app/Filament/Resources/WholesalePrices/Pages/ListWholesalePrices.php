<?php

namespace App\Filament\Resources\WholesalePrices\Pages;

use App\Filament\Resources\WholesalePrices\WholesalePriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListWholesalePrices extends ListRecords
{
    protected static string $resource = WholesalePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Harga Grosir')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
