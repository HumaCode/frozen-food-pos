<?php

namespace App\Filament\Resources\Stores\Tables;

use App\Models\Store;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Gambar')
                    ->circular()
                    ->alignCenter()
                    ->disk('public')
                    ->imageSize(100)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? 'Store') . '&background=6366f1&color=fff&size=60'),

                TextColumn::make('name')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn (Store $record) => $record->address)
                    ->icon('heroicon-o-map-pin')
                    ->iconColor('gray'),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->icon('heroicon-o-phone')
                    ->iconColor('gray')
                    ->copyable()
                    ->copyMessage('Nomor disalin!'),

                TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->iconColor('gray')
                    ->copyable()
                    ->copyMessage('Email disalin!')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('printer_size')
                    ->label('Printer')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? $state . 'mm' : '-')
                    ->color('info')
                    ->icon('heroicon-o-printer'),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare),
            ])
            ->emptyStateHeading('Belum ada data toko')
            ->emptyStateDescription('Tambahkan informasi toko Anda untuk ditampilkan di struk.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingStorefront)
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Toko')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->paginated(false);
    }
}
