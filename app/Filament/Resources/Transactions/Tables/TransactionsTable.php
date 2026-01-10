<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Invoice disalin!')
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (Transaction $record) => $record->created_at->diffForHumans()),

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->iconColor('gray'),

                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('gray')
                    ->suffix(' item')
                    ->alignCenter(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state > 0 ? '-Rp ' . number_format($state, 0, ',', '.') : '-'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('paid_amount')
                    ->label('Bayar')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('change_amount')
                    ->label('Kembali')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match($state) {
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'debit' => 'Debit',
                        'credit' => 'Kredit',
                        default => $state ?? '-',
                    })
                    ->icon(fn (?string $state) => match($state) {
                        'cash' => 'heroicon-o-banknotes',
                        'transfer' => 'heroicon-o-building-library',
                        'qris' => 'heroicon-o-qr-code',
                        'debit', 'credit' => 'heroicon-o-credit-card',
                        default => null,
                    })
                    ->color(fn (?string $state) => match($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'qris' => 'warning',
                        'debit' => 'primary',
                        'credit' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('synced_at')
                    ->label('Sync')
                    ->boolean()
                    ->trueIcon('heroicon-o-cloud-arrow-up')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn (Transaction $record) => $record->synced_at !== null)
                    ->tooltip(fn (Transaction $record) => $record->synced_at 
                        ? 'Synced: ' . $record->synced_at->format('d/m/Y H:i') 
                        : 'Belum sync'
                    )
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'name')
                    ->preload(),

                SelectFilter::make('payment_method')
                    ->label('Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'debit' => 'Debit',
                        'credit' => 'Kredit',
                    ]),

                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle()
                    ->default(),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->toggle(),

                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
                    ->toggle(),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->columns(2),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye),
                Action::make('print')
                    ->label('Cetak')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (Transaction $record) => route('transactions.print', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn () => \Illuminate\Support\Facades\Route::has('transactions.print')),
            ])
            ->bulkActions([
                BulkAction::make('export')
                    ->label('Export Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(function ($records) {
                        // TODO: Implement export logic
                        \Filament\Notifications\Notification::make()
                            ->title('Export dalam pengembangan')
                            ->info()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Belum ada transaksi')
            ->emptyStateDescription('Transaksi akan muncul di sini setelah ada penjualan dari aplikasi kasir.')
            ->emptyStateIcon(Heroicon::OutlinedShoppingCart)
            ->striped()
            ->poll('30s')
            ->paginated([10, 25, 50, 100])
            ->deferLoading();
    }
}
