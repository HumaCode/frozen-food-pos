<?php

namespace App\Filament\Resources\Shifts\Tables;

use App\Models\Shift;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Shift')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-clock')
                    ->iconColor('primary'),

                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-play'),

                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-o-stop'),

                TextColumn::make('duration')
                    ->label('Durasi')
                    ->state(function (Shift $record) {
                        try {
                            $start = Carbon::parse($record->start_time);
                            $end = Carbon::parse($record->end_time);

                            // Handle overnight shift
                            if ($end->lessThanOrEqualTo($start)) {
                                $end->addDay();
                            }

                            $hours = $start->diffInHours($end);
                            $minutes = $start->diffInMinutes($end) % 60;

                            $text = $hours . 'j';
                            if ($minutes > 0) {
                                $text .= ' ' . $minutes . 'm';
                            }

                            return $text;
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('is_overnight')
                    ->label('Tipe')
                    ->state(function (Shift $record) {
                        try {
                            $start = Carbon::parse($record->start_time);
                            $end = Carbon::parse($record->end_time);

                            return $end->lessThanOrEqualTo($start) ? 'Malam' : 'Normal';
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
                    ->badge()
                    ->color(fn(string $state) => $state === 'Malam' ? 'warning' : 'info')
                    ->icon(fn(string $state) => $state === 'Malam' ? 'heroicon-o-moon' : 'heroicon-o-sun'),

                TextColumn::make('transactions_count')
                    ->label('Transaksi')
                    ->counts('transactions')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_time', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('duplicate')
                        ->label('Duplikat')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('info')
                        ->action(function (Shift $record) {
                            $newShift = $record->replicate();
                            $newShift->name = $record->name . ' (Copy)';
                            $newShift->is_active = false;
                            $newShift->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Shift berhasil diduplikat')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplikat Shift')
                        ->modalDescription('Shift baru akan dibuat dengan status nonaktif.'),
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
            ->emptyStateHeading('Belum ada shift')
            ->emptyStateDescription('Buat shift untuk mengatur jadwal kerja kasir.')
            ->emptyStateIcon(Heroicon::OutlinedClock)
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Shift')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
