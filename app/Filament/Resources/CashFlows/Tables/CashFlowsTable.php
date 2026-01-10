<?php

namespace App\Filament\Resources\CashFlows\Tables;

use App\Models\CashFlow;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashFlowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (CashFlow $record) => $record->created_at->diffForHumans()),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'in' => 'Kas Masuk',
                        'out' => 'Kas Keluar',
                        default => $state,
                    })
                    ->icon(fn (string $state) => match($state) {
                        'in' => 'heroicon-o-arrow-down-circle',
                        'out' => 'heroicon-o-arrow-up-circle',
                        default => null,
                    })
                    ->color(fn (string $state) => match($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(function (CashFlow $record) {
                        $prefix = $record->type === 'in' ? '+' : '-';
                        return $prefix . 'Rp ' . number_format($record->amount, 0, ',', '.');
                    })
                    ->color(fn (CashFlow $record) => $record->type === 'in' ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (CashFlow $record) => $record->description),

                TextColumn::make('user.name')
                    ->label('Oleh')
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'in' => 'Kas Masuk',
                        'out' => 'Kas Keluar',
                    ]),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'name')
                    ->preload(),

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
                ActionGroup::make([
                    EditAction::make()
                        ->icon(Heroicon::OutlinedPencilSquare),
                    DeleteAction::make()
                        ->icon(Heroicon::OutlinedTrash),
                ])
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->tooltip('Aksi'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada kas masuk/keluar')
            ->emptyStateDescription('Catat kas masuk atau keluar untuk melacak arus kas.')
            ->emptyStateIcon(Heroicon::OutlinedBanknotes)
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Kas')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
