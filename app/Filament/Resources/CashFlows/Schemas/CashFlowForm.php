<?php

namespace App\Filament\Resources\CashFlows\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CashFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kas')
                    ->icon('heroicon-o-banknotes')
                    ->description('Catat kas masuk atau kas keluar')
                    ->components([
                        ToggleButtons::make('type')
                            ->label('Jenis')
                            ->options([
                                'in' => 'Kas Masuk',
                                'out' => 'Kas Keluar',
                            ])
                            ->icons([
                                'in' => 'heroicon-o-arrow-down-circle',
                                'out' => 'heroicon-o-arrow-up-circle',
                            ])
                            ->colors([
                                'in' => 'success',
                                'out' => 'danger',
                            ])
                            ->required()
                            ->inline()
                            ->default('in')
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->minValue(1)
                            ->columnSpanFull(),

                        Select::make('shift_id')
                            ->label('Shift')
                            ->relationship('shift', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Pilih shift (opsional)'),

                        Hidden::make('user_id')
                            ->default(fn () => auth()->id()),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->required()
                            ->rows(3)
                            ->placeholder(fn (Get $get) => $get('type') === 'in' 
                                ? 'Contoh: Modal awal, Setoran tambahan, dll' 
                                : 'Contoh: Pembelian plastik, Biaya listrik, dll'
                            )
                            ->columnSpanFull(),

                        Placeholder::make('info')
                            ->label('')
                            ->content(function (Get $get) {
                                $type = $get('type');
                                $amount = (float) $get('amount');

                                if (!$amount) return '';

                                $formattedAmount = 'Rp ' . number_format($amount, 0, ',', '.');

                                if ($type === 'in') {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-sm text-green-600 bg-green-50 dark:bg-green-900/20 p-4 rounded-lg flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>Kas akan <strong>bertambah</strong> sebesar <strong>' . $formattedAmount . '</strong></span>
                                        </div>'
                                    );
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm text-red-600 bg-red-50 dark:bg-red-900/20 p-4 rounded-lg flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                        </svg>
                                        <span>Kas akan <strong>berkurang</strong> sebesar <strong>' . $formattedAmount . '</strong></span>
                                    </div>'
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])->columns(1);
    }
}
