<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // ===============================
                // INFORMASI UTAMA
                // ===============================
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn(Product $record) => $record->category?->name),

                ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular()
                    ->size(48)
                    ->defaultImageUrl(asset('images/no-image.png')),

                TextColumn::make('barcode')
                    ->label('Barcode / SKU')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Barcode disalin')
                    ->fontFamily('mono')
                    ->color('gray')
                    ->placeholder('-')
                    ->toggleable(),

                // ===============================
                // HARGA
                // ===============================
                TextColumn::make('buy_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sell_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable()
                    ->color('success')
                    ->weight('semibold'),

                // ===============================
                // STOK
                // ===============================
                TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->formatStateUsing(fn(Product $record) => "{$record->stock} {$record->unit}")
                    ->color(fn(Product $record) => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->min_stock => 'warning',
                        default => 'success',
                    })
                    ->icon(fn(Product $record) => match (true) {
                        $record->stock <= 0 => 'heroicon-o-x-circle',
                        $record->stock <= $record->min_stock => 'heroicon-o-exclamation-triangle',
                        default => null,
                    }),

                TextColumn::make('min_stock')
                    ->label('Min. Stok')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ===============================
                // EXPIRED
                // ===============================
                TextColumn::make('expired_date')
                    ->label('Expired')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn(Product $record) => match (true) {
                        $record->expired_date === null => 'gray',
                        $record->expired_date->isPast() => 'danger',
                        $record->expired_date->isFuture()
                            && $record->expired_date->diffInDays(now()) <= 30 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn(Product $record) => match (true) {
                        $record->expired_date?->isPast() => 'heroicon-o-x-circle',
                        $record->expired_date?->isFuture()
                            && $record->expired_date->diffInDays(now()) <= 30
                        => 'heroicon-o-exclamation-triangle',
                        default => null,
                    })
                    ->toggleable(),

                // ===============================
                // STATUS
                // ===============================
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(
                        fn(Product $record) =>
                        $record->is_active ? 'Produk Aktif' : 'Produk Nonaktif'
                    ),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // ===============================
            // FILTER
            // ===============================
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),

                Filter::make('low_stock')
                    ->label('Stok Menipis')
                    ->query(fn(Builder $query) => $query->lowStock())
                    ->toggle(),

                Filter::make('out_of_stock')
                    ->label('Stok Habis')
                    ->query(fn(Builder $query) => $query->outOfStock())
                    ->toggle(),

                Filter::make('expired')
                    ->label('Sudah Expired')
                    ->query(fn(Builder $query) => $query->expired())
                    ->toggle(),

                Filter::make('expiring_soon')
                    ->label('Segera Expired (≤ 30 hari)')
                    ->query(fn(Builder $query) => $query->expiringSoon(30))
                    ->toggle(),
            ])


            // ===============================
            // ACTIONS
            // ===============================
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
