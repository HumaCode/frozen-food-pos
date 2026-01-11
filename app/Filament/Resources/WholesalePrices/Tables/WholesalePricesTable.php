<?php

namespace App\Filament\Resources\WholesalePrices\Tables;

use App\Models\WholesalePrice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class WholesalePricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('product.image')
                    ->label('Gambar')
                    ->alignCenter()
                    ->disk('public')
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->product?->name ?? 'P') . '&background=6366f1&color=fff'),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->formatStateUsing(fn(string $state) => ucwords(strtolower($state)))
                    ->description(fn(WholesalePrice $record) => ucwords(strtolower($record->product?->category?->name ?? ''))),

                TextColumn::make('product.sell_price')
                    ->label('Harga Normal')
                    ->sortable()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                TextColumn::make('min_qty')
                    ->label('Min. Qty')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (WholesalePrice $record) => '≥ ' . $record->min_qty . ' ' . ($record->product?->unit ?? 'pcs')),

                TextColumn::make('price')
                    ->label('Harga Grosir')
                    ->sortable()
                    ->color('success')
                    ->weight('semibold')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                TextColumn::make('savings')
                    ->label('Hemat')
                    ->state(function (WholesalePrice $record) {
                        $normalPrice = $record->product?->sell_price ?? 0;
                        $savings = $normalPrice - $record->price;
                        $percent = $normalPrice > 0 ? round(($savings / $normalPrice) * 100, 0) : 0;
                        return $percent > 0 ? "{$percent}%" : '-';
                    })
                    ->badge()
                    ->color(function (WholesalePrice $record) {
                        $normalPrice = $record->product?->sell_price ?? 0;
                        $savings = $normalPrice - $record->price;
                        $percent = $normalPrice > 0 ? round(($savings / $normalPrice) * 100, 0) : 0;
                        
                        if ($percent >= 20) return 'success';
                        if ($percent >= 10) return 'warning';
                        if ($percent > 0) return 'gray';
                        return 'danger';
                    })
                    ->icon('heroicon-o-arrow-trending-down')
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter()
                    ->afterStateUpdated(function (WholesalePrice $record, bool $state) {
                        $status = $state ? 'aktifkan' : 'nonaktifkan';
                        Notification::make()
                            ->title('Harga grosir "' . $record->name . '" berhasil ' . $status)
                            ->body('Perubahan telah disimpan.')
                            ->success()
                            ->send();
                    }),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('product.name', 'asc')
            ->groups([
                Group::make('product.category.name')
                    ->label('Kategori')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('product.category_id')
                    ->label('Kategori')
                    ->relationship('product.category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua')
                    ->searchable(),

                Filter::make('high_discount')
                    ->label('Diskon Besar (≥20%)')
                    ->query(function ($query) {
                        return $query->whereHas('product', function ($q) {
                            $q->whereRaw('(products.sell_price - wholesale_prices.price) / products.sell_price >= 0.2');
                        });
                    })
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon(Heroicon::OutlinedPencilSquare),

                    Action::make('view_product')
                        ->label('Lihat Produk')
                        ->icon(Heroicon::OutlinedEye)
                        ->color('info')
                        ->url(fn (WholesalePrice $record) =>
                            route(
                                'filament.admin.resources.products.edit',
                                $record->product_id
                            )
                        )
                        ->openUrlInNewTab(),

                    DeleteAction::make()
                        ->icon(Heroicon::OutlinedTrash),
                ])
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->tooltip('Aksi'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->action(fn ($records) =>
                            $records->each->update(['is_active' => true])
                        )
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('warning')
                        ->action(fn ($records) =>
                            $records->each->update(['is_active' => false])
                        )
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada harga grosir')
            ->emptyStateDescription('Atur harga khusus untuk pembelian dalam jumlah banyak.')
            ->emptyStateIcon(Heroicon::OutlinedShoppingBag)
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Harga Grosir')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
