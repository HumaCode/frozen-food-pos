<?php

namespace App\Filament\Resources\WholesalePrices\Pages;

use App\Filament\Resources\WholesalePrices\WholesalePriceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateWholesalePrice extends CreateRecord
{
    protected static string $resource = WholesalePriceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Harga grosir berhasil dibuat.')
            ->body('Anda telah berhasil membuat harga grosir baru.')
            ->success();
    }
}
