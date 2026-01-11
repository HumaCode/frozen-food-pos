<?php

namespace App\Filament\Resources\Shifts\Pages;

use App\Filament\Resources\Shifts\ShiftResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Shift berhasil dibuat.')
            ->body('Anda telah berhasil membuat shift baru.')
            ->success();
    }
}
