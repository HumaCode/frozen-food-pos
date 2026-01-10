<?php

namespace App\Filament\Resources\WholesalePrices\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WholesalePriceForm
{
    public static function configure(Schema $schema): Schema
    {
         return $schema
            ->components([
                Section::make('Informasi Harga Grosir')
                    ->icon('heroicon-o-shopping-bag')
                    ->description('Atur harga khusus untuk pembelian dalam jumlah banyak')
                    ->components([
                        Select::make('product_id')
                            ->label('Pilih Produk')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->placeholder('Cari produk...')
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('current_sell_price', $product->sell_price);
                                    }
                                } else {
                                    $set('current_sell_price', null);
                                }
                            })
                            ->columnSpanFull(),

                        Placeholder::make('product_info')
                            ->label('')
                            ->content(function (Get $get) {
                                $productId = $get('product_id');
                                if (!$productId) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-800 p-4 rounded-lg text-center">
                                            Pilih produk terlebih dahulu
                                        </div>'
                                    );
                                }

                                $product = Product::with('category')->find($productId);
                                if (!$product) return '';

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Kategori</p>
                                                <p class="font-medium">' . ($product->category?->name ?? '-') . '</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Stok</p>
                                                <p class="font-medium">' . number_format($product->stock, 0, ',', '.') . ' ' . $product->unit . '</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Harga Beli</p>
                                                <p class="font-medium">Rp ' . number_format($product->buy_price, 0, ',', '.') . '</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Harga Jual Normal</p>
                                                <p class="font-medium text-primary-600">Rp ' . number_format($product->sell_price, 0, ',', '.') . '</p>
                                            </div>
                                        </div>
                                    </div>'
                                );
                            })
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->components([
                                TextInput::make('min_qty')
                                    ->label('Minimal Qty')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2)
                                    ->default(10)
                                    ->suffix(function (Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId) {
                                            $product = Product::find($productId);
                                            return $product?->unit ?? 'pcs';
                                        }
                                        return 'pcs';
                                    })
                                    ->helperText('Minimal pembelian untuk mendapat harga grosir')
                                    ->live(onBlur: true),

                                TextInput::make('price')
                                    ->label('Harga Grosir')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->live(onBlur: true),
                            ]),

                        Placeholder::make('savings_info')
                            ->label('')
                            ->content(function (Get $get) {
                                $productId = $get('product_id');
                                $wholesalePrice = (float) $get('price');

                                if (!$productId || !$wholesalePrice) {
                                    return '';
                                }

                                $product = Product::find($productId);
                                if (!$product) return '';

                                $normalPrice = $product->sell_price;
                                $savings = $normalPrice - $wholesalePrice;
                                $savingsPercent = $normalPrice > 0 ? round(($savings / $normalPrice) * 100, 1) : 0;

                                if ($savings <= 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-sm text-red-600 bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                                            <span class="font-semibold">⚠️ Harga grosir harus lebih rendah dari harga normal!</span>
                                        </div>'
                                    );
                                }

                                $minQty = (int) $get('min_qty') ?: 1;
                                $totalSavings = $savings * $minQty;

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                                        <div class="grid grid-cols-3 gap-4 text-center">
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Hemat per Item</p>
                                                <p class="font-semibold text-green-600">Rp ' . number_format($savings, 0, ',', '.') . '</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Persentase</p>
                                                <p class="font-semibold text-green-600">' . $savingsPercent . '%</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Total Hemat (min. ' . $minQty . ' pcs)</p>
                                                <p class="font-semibold text-green-600">Rp ' . number_format($totalSavings, 0, ',', '.') . '</p>
                                            </div>
                                        </div>
                                    </div>'
                                );
                            })
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Aktifkan Harga Grosir')
                            ->helperText('Harga grosir akan berlaku di aplikasi kasir jika diaktifkan')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(1),
            ]);
    }
}
