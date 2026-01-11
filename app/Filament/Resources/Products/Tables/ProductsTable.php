<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Picqer\Barcode\BarcodeGeneratorHTML;

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
                    ->formatStateUsing(fn(string $state) => ucwords(strtolower($state)))
                    ->description(fn(Product $record) => ucwords(strtolower($record->category?->name ?? ''))),

                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->alignCenter()
                    ->defaultImageUrl(asset('images/noimage.png')),

                TextColumn::make('barcode')
                    ->label('Barcode / SKU')
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (! $state) return '-';

                        $generator = new BarcodeGeneratorHTML();

                        return '
                            <div class="flex flex-col items-center gap-1">
                                ' . $generator->getBarcode($state, $generator::TYPE_CODE_128, 1.4, 35) . '
                                <span class="text-xs font-mono text-gray-500">' . $state . '</span>
                            </div>
                        ';
                    })
                    ->copyable()
                    ->copyMessage('Barcode disalin'),


                // ===============================
                // HARGA
                // ===============================
                TextColumn::make('buy_price')
                    ->label('Harga Beli')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                TextColumn::make('sell_price')
                    ->label('Harga Jual')
                    ->weight('semibold')
                    ->color('success')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),


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
                    ->searchable()
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
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('Lihat')
                    ->iconButton(),

                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip('Edit')
                    ->iconButton(),

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('')
                    ->tooltip('Hapus')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
