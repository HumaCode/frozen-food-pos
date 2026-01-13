<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Transaction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Detail Item')
                ->icon('heroicon-o-shopping-bag')
                ->components([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            TextEntry::make('product_name')
                                ->label('Produk')
                                ->weight('semibold'),

                            TextEntry::make('qty')
                                ->label('Qty')
                                ->badge()
                                ->color('gray'),

                            TextEntry::make('price')
                                ->label('Harga')
                                ->money('IDR'),

                            TextEntry::make('discount_per_item')
                                ->label('Diskon')
                                ->money('IDR')
                                ->placeholder('-')
                                ->color('danger'),

                            TextEntry::make('is_wholesale')
                                ->label('Grosir')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state ? 'Ya' : '-')
                                ->color(fn ($state) => $state ? 'info' : 'gray'),

                            TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->money('IDR')
                                ->weight('semibold')
                                ->color('success'),
                        ])
                        ->columns(2)
                        ->grid(2)
                        ,
                ])->columnSpan(2),

                Grid::make(2)
                    ->components([
                        Section::make('Catatan')
                            ->icon('heroicon-o-chat-bubble-left')
                            ->components([
                                TextEntry::make('notes')
                                    ->label('')
                                    ->placeholder('Tidak ada catatan')
                                    ->columnSpanFull(),
                            ])
                            ->hidden(fn (Transaction $record) => empty($record->notes)),

                        Section::make('Ringkasan')
                            ->icon('heroicon-o-calculator')
                            ->components([
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR'),

                                TextEntry::make('discount_amount')
                                    ->label('Diskon')
                                    ->money('IDR')
                                    ->color('danger')
                                    ->formatStateUsing(fn ($state) => $state > 0 ? '-Rp ' . number_format($state, 0, ',', '.') : '-'),

                                TextEntry::make('total')
                                    ->label('Total')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->color('success')
                                    ->size('lg'),

                                TextEntry::make('paid_amount')
                                    ->label('Bayar')
                                    ->money('IDR'),

                                TextEntry::make('change_amount')
                                    ->label('Kembali')
                                    ->money('IDR')
                                    ->color('info'),
                            ])
                            ->columnSpan(fn (Transaction $record) => empty($record->notes) ? 3 : 1),
                    ]),


                Grid::make(3)
                    ->schema([
                        Section::make('Informasi Transaksi')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('No. Invoice')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable()
                                    ->copyMessage('Invoice disalin!'),

                                TextEntry::make('created_at')
                                    ->label('Tanggal & Waktu')
                                    ->dateTime('d/m/Y H:i:s'),

                                TextEntry::make('user.name')
                                    ->label('Kasir')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('shift.name')
                                    ->label('Shift')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('-'),
                            ])
                            ->columns(2)
                            ->columnSpan(2),

                        Section::make('Pembayaran')
                            ->icon('heroicon-o-banknotes')
                            ->components([
                                TextEntry::make('payment_method')
                                    ->label('Metode')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state) => match($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        'qris' => 'QRIS',
                                        'debit' => 'Debit',
                                        'credit' => 'Kredit',
                                        default => $state ?? '-',
                                    })
                                    ->color(fn (?string $state) => match($state) {
                                        'cash' => 'success',
                                        'transfer' => 'info',
                                        'qris' => 'warning',
                                        'debit' => 'primary',
                                        'credit' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('synced_at')
                                    ->label('Status Sync')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Synced' : 'Pending')
                                    ->color(fn ($state) => $state ? 'success' : 'warning')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-cloud-arrow-up' : 'heroicon-o-clock'),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                    ])
                    ->columns(2),




            ]);
    }
}
