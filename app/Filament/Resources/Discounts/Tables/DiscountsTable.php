<?php

namespace App\Filament\Resources\Discounts\Tables;

use App\Models\Discount;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Diskon')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(
                        fn(Discount $record) => $record->type === 'product'
                            ? $record->product?->name
                            : 'Min. belanja Rp ' . number_format($record->min_purchase, 0, ',', '.')
                    ),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'product' => 'Per Produk',
                        'total' => 'Total Belanja',
                        default => $state,
                    })
                    ->icon(fn(string $state) => match ($state) {
                        'product' => 'heroicon-o-cube',
                        'total' => 'heroicon-o-shopping-cart',
                        default => null,
                    })
                    ->color(fn(string $state) => match ($state) {
                        'product' => 'info',
                        'total' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('value')
                    ->label('Nilai Diskon')
                    ->formatStateUsing(
                        fn(Discount $record) =>
                        $record->discount_type === 'percentage'
                            ? $record->value . '%'
                            : 'Rp ' . number_format($record->value, 0, ',', '.')
                    )
                    ->badge()
                    ->color('success')
                    ->icon(
                        fn(Discount $record) => $record->discount_type === 'percentage'
                            ? 'heroicon-o-receipt-percent'
                            : 'heroicon-o-banknotes'
                    ),

                TextColumn::make('period')
                    ->label('Periode')
                    ->state(function (Discount $record) {
                        if (!$record->start_date && !$record->end_date) {
                            return 'Tanpa Batas';
                        }

                        $start = $record->start_date?->format('d/m/Y') ?? '-';
                        $end = $record->end_date?->format('d/m/Y') ?? '∞';

                        return "{$start} s/d {$end}";
                    })
                    ->color(function (Discount $record) {
                        if (!$record->is_active) return 'gray';
                        if ($record->end_date?->isPast()) return 'danger';
                        if ($record->start_date?->isFuture()) return 'info';
                        return 'success';
                    })
                    ->icon(function (Discount $record) {
                        if ($record->end_date?->isPast()) return 'heroicon-o-x-circle';
                        if ($record->start_date?->isFuture()) return 'heroicon-o-clock';
                        return 'heroicon-o-calendar';
                    }),

                TextColumn::make('status_display')
                    ->label('Status')
                    ->state(function (Discount $record) {
                        if (!$record->is_active) return 'Nonaktif';
                        if ($record->end_date?->isPast()) return 'Berakhir';
                        if ($record->start_date?->isFuture()) return 'Terjadwal';
                        return 'Berlaku';
                    })
                    ->badge()
                    ->color(function (Discount $record) {
                        if (!$record->is_active) return 'gray';
                        if ($record->end_date?->isPast()) return 'danger';
                        if ($record->start_date?->isFuture()) return 'info';
                        return 'success';
                    }),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter(),

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
                        'product' => 'Per Produk',
                        'total' => 'Total Belanja',
                    ]),

                SelectFilter::make('discount_type')
                    ->label('Tipe Nilai')
                    ->options([
                        'percentage' => 'Persentase',
                        'nominal' => 'Nominal',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),

                Filter::make('currently_valid')
                    ->label('Sedang Berlaku')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('is_active', true)
                            ->where(function ($q) {
                                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                            })
                            ->where(function ($q) {
                                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                            })
                    )
                    ->toggle(),

                Filter::make('expired')
                    ->label('Sudah Berakhir')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereNotNull('end_date')->where('end_date', '<', now())
                    )
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('duplicate')
                        ->label('Duplikat')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('info')
                        ->action(function (Discount $record) {
                            $newDiscount = $record->replicate();
                            $newDiscount->name = $record->name . ' (Copy)';
                            $newDiscount->is_active = false;
                            $newDiscount->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Diskon berhasil diduplikat')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplikat Diskon')
                        ->modalDescription('Diskon baru akan dibuat dengan status nonaktif.'),
                    DeleteAction::make()
                        ->icon(Heroicon::OutlinedTrash),
                ])
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->tooltip('Aksi'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada diskon')
            ->emptyStateDescription('Buat diskon untuk menarik pelanggan.')
            ->emptyStateIcon(Heroicon::OutlinedReceiptPercent)
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Diskon')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
