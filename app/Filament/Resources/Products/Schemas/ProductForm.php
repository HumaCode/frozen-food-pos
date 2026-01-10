<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\FormsComponent;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;

class ProductForm
{
    protected static function calculateProfit(
        float|int|null $buyPrice,
        float|int|null $sellPrice,
        Set $set
    ): void {
        $buy = (float) ($buyPrice ?? 0);
        $sell = (float) ($sellPrice ?? 0);

        $profit = $sell - $buy;

        $set('profit', $profit);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Produk')
                    ->components([

                        Section::make('Informasi Produk')
                            ->icon('heroicon-o-information-circle')
                            ->components([
                                Select::make('category_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Pilih kategori'),
                                TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan nama produk')
                                    ->autocomplete(false),
                                TextInput::make('barcode')
                                    ->label('Barcode / SKU')
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Scan atau input manual')
                                    ->prefixIcon('heroicon-o-qr-code'),
                            ])->columns(1),

                        Section::make('Harga')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                TextInput::make('buy_price')
                                    ->label('Harga Beli')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn($state, Set $set, Get $get) =>
                                        self::calculateProfit($state, $get('sell_price'), $set)
                                    ),

                                TextInput::make('sell_price')
                                    ->label('Harga Jual')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn($state, Set $set, Get $get) =>
                                        self::calculateProfit($get('buy_price'), $state, $set)
                                    ),

                                Placeholder::make('profit_display')
                                    ->label('Keuntungan')
                                    ->content(function (Get $get) {
                                        $buy = (float) $get('buy_price') ?: 0;
                                        $sell = (float) $get('sell_price') ?: 0;
                                        $profit = $sell - $buy;
                                        $percentage = $buy > 0 ? round(($profit / $buy) * 100, 1) : 0;

                                        $color = $profit > 0 ? 'text-green-600' : ($profit < 0 ? 'text-red-600' : 'text-gray-500');

                                        return new \Illuminate\Support\HtmlString(
                                            "<span class='{$color} font-semibold'>Rp " . number_format($profit, 0, ',', '.') . " ({$percentage}%)</span>"
                                        );
                                    }),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Gambar')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('400')
                                    ->imageResizeTargetHeight('400')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Stok')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                TextInput::make('stock')
                                    ->label('Stok Saat Ini')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix(fn(Get $get) => $get('unit') ?: 'pcs'),

                                TextInput::make('min_stock')
                                    ->label('Stok Minimum')
                                    ->required()
                                    ->numeric()
                                    ->default(10)
                                    ->minValue(0)
                                    ->helperText('Alert jika stok di bawah ini'),

                                Select::make('unit')
                                    ->label('Satuan')
                                    ->options([
                                        'pcs' => 'Pcs',
                                        'pack' => 'Pack',
                                        'box' => 'Box',
                                        'kg' => 'Kilogram',
                                        'gram' => 'Gram',
                                    ])
                                    ->default('pcs')
                                    ->native(false),
                            ]),

                        Section::make('Status')
                            ->schema([
                                DatePicker::make('expired_date')
                                    ->label('Tanggal Expired')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->placeholder('Pilih tanggal'),

                                Toggle::make('is_active')
                                    ->label('Produk Aktif')
                                    ->default(true)
                                    ->helperText('Nonaktif = tidak tampil di kasir')
                                    ->inline(false),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }
}
