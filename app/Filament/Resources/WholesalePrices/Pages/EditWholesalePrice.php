<?php

namespace App\Filament\Resources\WholesalePrices\Pages;

use App\Filament\Resources\WholesalePrices\WholesalePriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWholesalePrice extends EditRecord
{
    protected static string $resource = WholesalePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
