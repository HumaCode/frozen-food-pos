<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTransactionsWidget extends TableWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->with(['user', 'shift'])
                    ->latest()
                    ->limit(15)
            )
            ->heading('🛒 Transaksi Terbaru')
            ->description('15 transaksi terakhir (Auto refresh setiap 15 detik)')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Invoice disalin!')
                    ->icon('heroicon-o-document-text'),

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-user'),

                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color('warning')
                    ->default('-')
                    ->icon('heroicon-o-clock'),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->money('IDR')
                    ->default('Rp 0')
                    ->color('danger')
                    ->formatStateUsing(fn($state) => $state > 0 ? '- ' . number_format($state, 0, ',', '.') : 'Rp 0'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->size('lg'),

                TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'debit' => 'info',
                        'credit' => 'warning',
                        'qris' => 'danger',
                        'transfer' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->icon(fn(string $state): string => match ($state) {
                        'cash' => 'heroicon-o-banknotes',
                        'debit', 'credit' => 'heroicon-o-credit-card',
                        'qris' => 'heroicon-o-qr-code',
                        'transfer' => 'heroicon-o-building-library',
                        default => 'heroicon-o-currency-dollar',
                    }),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->description(fn($record) => $record->created_at->format('H:i:s'))
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn(Transaction $record) => 'Detail Transaksi: ' . $record->invoice_number)
                    ->modalContent(fn(Transaction $record) => view('filament.widgets.transaction-detail', ['transaction' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->emptyStateHeading('Belum Ada Transaksi')
            ->emptyStateDescription('Transaksi akan muncul di sini setelah ada penjualan.')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->poll('15s'); // Auto refresh setiap 15 detik
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }
}
