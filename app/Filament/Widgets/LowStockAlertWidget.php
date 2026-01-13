<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockAlertWidget extends TableWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with('category')
                    ->where('is_active', true)
                    ->whereColumn('stock', '<=', 'min_stock')
                    ->orderBy('stock', 'asc')
            )
            ->heading('⚠️ Alert: Produk Stok Menipis')
            ->description('Produk yang perlu segera di-restock')
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('/images/noimage.jpg')),

                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('danger'),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info'),

                TextColumn::make('stock')
                    ->label('Stok Saat Ini')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn(int $state): string => $state . ' ' . ($state > 0 ? 'pcs' : '(HABIS)')),

                TextColumn::make('min_stock')
                    ->label('Min. Stok')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('stock_diff')
                    ->label('Kekurangan')
                    ->state(function (Product $record): int {
                        return max(0, $record->min_stock - $record->stock);
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn(int $state): string => $state > 0 ? '-' . $state . ' pcs' : 'Aman'),

                TextColumn::make('sell_price')
                    ->label('Harga Jual')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([
                Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->url(fn(Product $record): string => route('filament.admin.resources.products.edit', $record))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Semua Stok Aman! 🎉')
            ->emptyStateDescription('Tidak ada produk yang stoknya menipis.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->poll('30s'); // Auto refresh setiap 30 detik
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }
}
