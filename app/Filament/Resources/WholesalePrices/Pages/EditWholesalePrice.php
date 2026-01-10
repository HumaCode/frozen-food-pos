<?php

namespace App\Filament\Resources\WholesalePrices\Pages;

use App\Filament\Resources\WholesalePrices\WholesalePriceResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditWholesalePrice extends EditRecord
{
    protected static string $resource = WholesalePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Harga grosir berhasil diubah.')
            ->body('Anda telah berhasil mengubah harga grosir.')
            ->success();
    }
}
