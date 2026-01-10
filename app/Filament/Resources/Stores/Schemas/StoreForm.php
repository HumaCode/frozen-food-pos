<?php

namespace App\Filament\Resources\Stores\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
       return $schema
            ->components([
                Group::make()
                    ->components([
                        Section::make('Informasi Toko')
                            ->icon('heroicon-o-building-storefront')
                            ->description('Data utama toko yang akan ditampilkan di struk')
                            ->components([
                                TextInput::make('name')
                                    ->label('Nama Toko')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Toko Frozen Food Sejahtera')
                                    ->columnSpanFull(),

                                Textarea::make('address')
                                    ->label('Alamat')
                                    ->rows(3)
                                    ->placeholder('Alamat lengkap toko')
                                    ->columnSpanFull(),

                                TextInput::make('phone')
                                    ->label('Nomor Telepon')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('Contoh: 08123456789')
                                    ->prefixIcon('heroicon-o-phone'),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: toko@email.com')
                                    ->prefixIcon('heroicon-o-envelope'),
                            ])
                            ->columns(2),

                        Section::make('Pengaturan Struk')
                            ->icon('heroicon-o-printer')
                            ->description('Konfigurasi untuk cetak struk')
                            ->components([
                                ToggleButtons::make('printer_size')
                                    ->label('Ukuran Printer')
                                    ->options([
                                        '58' => '58mm (Thermal Kecil)',
                                        '80' => '80mm (Thermal Standar)',
                                    ])
                                    ->icons([
                                        '58' => 'heroicon-o-document',
                                        '80' => 'heroicon-o-document-text',
                                    ])
                                    ->default('58')
                                    ->inline()
                                    ->required()
                                    ->columnSpanFull(),

                                Textarea::make('receipt_footer')
                                    ->label('Footer Struk')
                                    ->rows(3)
                                    ->placeholder('Contoh: Terima kasih atas kunjungan Anda. Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.')
                                    ->helperText('Pesan yang akan ditampilkan di bagian bawah struk')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->components([
                        Section::make('Logo Toko')
                            ->icon('heroicon-o-photo')
                            ->components([
                                FileUpload::make('logo')
                                    ->label('')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('store')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('300')
                                    ->imageResizeTargetHeight('300')
                                    ->helperText('Logo akan ditampilkan di struk (opsional)')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Preview Struk')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Placeholder::make('receipt_preview')
                                    ->label('')
                                    ->content(function ($get) {
                                        $name = $get('name') ?: 'Nama Toko';
                                        $address = $get('address') ?: 'Alamat Toko';
                                        $phone = $get('phone') ?: '-';
                                        $footer = $get('receipt_footer') ?: 'Terima kasih';
                                        $size = $get('printer_size') === '80' ? 'w-80' : 'w-58';

                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="bg-white dark:bg-gray-900 p-4 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 font-mono text-xs text-center">
                                                <p class="font-bold text-sm">' . e($name) . '</p>
                                                <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">' . nl2br(e($address)) . '</p>
                                                <p class="text-gray-600 dark:text-gray-400 text-xs">Telp: ' . e($phone) . '</p>
                                                <p class="border-t border-dashed border-gray-300 dark:border-gray-600 my-3"></p>
                                                <p class="text-gray-500 dark:text-gray-500 text-xs">... isi struk ...</p>
                                                <p class="border-t border-dashed border-gray-300 dark:border-gray-600 my-3"></p>
                                                <p class="text-gray-600 dark:text-gray-400 text-xs italic">' . nl2br(e($footer)) . '</p>
                                            </div>'
                                        );
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
