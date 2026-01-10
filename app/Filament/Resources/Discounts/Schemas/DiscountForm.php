<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Informasi Diskon')
                            ->icon('heroicon-o-information-circle')
                            ->components([
                                TextInput::make('name')
                                    ->label('Nama Diskon')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Promo Weekend, Diskon Lebaran')
                                    ->columnSpanFull(),

                                ToggleButtons::make('type')
                                    ->label('Jenis Diskon')
                                    ->options([
                                        'product' => 'Per Produk',
                                        'total' => 'Total Belanja',
                                    ])
                                    ->icons([
                                        'product' => 'heroicon-o-cube',
                                        'total' => 'heroicon-o-shopping-cart',
                                    ])
                                    ->colors([
                                        'product' => 'info',
                                        'total' => 'warning',
                                    ])
                                    ->required()
                                    ->inline()
                                    ->default('product')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('product_id', null);
                                        $set('min_purchase', null);
                                    })
                                    ->columnSpanFull(),

                                Select::make('product_id')
                                    ->label('Pilih Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Cari produk...')
                                    ->visible(fn(Get $get) => $get('type') === 'product')
                                    ->required(fn(Get $get) => $get('type') === 'product')
                                    ->columnSpanFull(),

                                TextInput::make('min_purchase')
                                    ->label('Minimal Belanja')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->helperText('Diskon berlaku jika total belanja mencapai nilai ini')
                                    ->visible(fn(Get $get) => $get('type') === 'total')
                                    ->required(fn(Get $get) => $get('type') === 'total')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Nilai Diskon')
                            ->icon('heroicon-o-calculator')
                            ->components([
                                ToggleButtons::make('discount_type')
                                    ->label('Tipe Nilai')
                                    ->options([
                                        'percentage' => 'Persentase (%)',
                                        'nominal' => 'Nominal (Rp)',
                                    ])
                                    ->icons([
                                        'percentage' => 'heroicon-o-receipt-percent',
                                        'nominal' => 'heroicon-o-banknotes',
                                    ])
                                    ->colors([
                                        'percentage' => 'success',
                                        'nominal' => 'primary',
                                    ])
                                    ->required()
                                    ->inline()
                                    ->default('percentage')
                                    ->live()
                                    ->columnSpanFull(),

                                TextInput::make('value')
                                    ->label(fn(Get $get) => $get('discount_type') === 'percentage' ? 'Nilai Persentase' : 'Nilai Nominal')
                                    ->required()
                                    ->numeric()
                                    ->prefix(fn(Get $get) => $get('discount_type') === 'nominal' ? 'Rp' : null)
                                    ->suffix(fn(Get $get) => $get('discount_type') === 'percentage' ? '%' : null)
                                    ->maxValue(fn(Get $get) => $get('discount_type') === 'percentage' ? 100 : null)
                                    ->minValue(0)
                                    ->placeholder('0')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Group::make()
                    ->components([
                        Section::make('Periode')
                            ->icon('heroicon-o-calendar-days')
                            ->components([
                                DatePicker::make('start_date')
                                    ->label('Tanggal Mulai')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->placeholder('Pilih tanggal')
                                    ->helperText('Kosongkan jika langsung berlaku'),

                                DatePicker::make('end_date')
                                    ->label('Tanggal Berakhir')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->placeholder('Pilih tanggal')
                                    ->afterOrEqual('start_date')
                                    ->helperText('Kosongkan jika tidak ada batas'),
                            ]),

                        Section::make('Status')
                            ->components([
                                Toggle::make('is_active')
                                    ->label('Aktifkan Diskon')
                                    ->helperText('Diskon akan tampil di aplikasi kasir jika diaktifkan')
                                    ->default(false)
                                    ->inline(false),

                                Placeholder::make('status_info')
                                    ->label('')
                                    ->content(function (Get $get) {
                                        $isActive = $get('is_active');
                                        $startDate = $get('start_date');
                                        $endDate = $get('end_date');

                                        if (!$isActive) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
                                                    <span class="font-medium">⏸️ Diskon tidak aktif</span>
                                                </div>'
                                            );
                                        }

                                        $now = now();
                                        $start = $startDate ? \Carbon\Carbon::parse($startDate) : null;
                                        $end = $endDate ? \Carbon\Carbon::parse($endDate) : null;

                                        if ($start && $start->isFuture()) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-sm text-blue-600 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                                                    <span class="font-medium">📅 Akan berlaku mulai ' . $start->format('d/m/Y') . '</span>
                                                </div>'
                                            );
                                        }

                                        if ($end && $end->isPast()) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-sm text-red-600 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                                                    <span class="font-medium">⛔ Diskon sudah berakhir</span>
                                                </div>'
                                            );
                                        }

                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm text-green-600 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                                                <span class="font-medium">✅ Diskon sedang berlaku</span>
                                            </div>'
                                        );
                                    }),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }
}
