<?php

namespace App\Filament\Resources\Stores\Pages;

use App\Filament\Resources\Stores\StoreResource;
use App\Models\Store;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListStores extends ListRecords
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        // Hanya tampilkan tombol create jika belum ada toko
        if (Store::count() === 0) {
            return [
                CreateAction::make()
                    ->label('Tambah Toko')
                    ->icon(Heroicon::OutlinedPlus),
            ];
        }

        return [];
    }  
}
