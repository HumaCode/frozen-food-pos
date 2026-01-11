<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Category;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->width(45)
                    ->alignCenter()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=6366f1&color=fff'),

                TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter()
                    ->afterStateUpdated(function (Category $record, bool $state) {
                        $status = $state ? 'aktifkan' : 'nonaktifkan';
                        Notification::make()
                            ->title('Kategori "' . $record->name . '" berhasil ' . $status)
                            ->body('Perubahan telah disimpan.')
                            ->success()
                            ->send();
                    }),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->searchable()
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Kategori')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Tambah Kategori')
                    ->modalWidth('md')
                    ->form(fn($form) => CategoryForm::configure($form)->getComponents())
                    ->successNotification(
                        Notification::make()
                            ->title('Kategori berhasil dibuat')
                            ->body('Kategori baru telah ditambahkan.')
                            ->success(),
                    )
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->modalHeading('Edit Kategori')
                        ->modalWidth('md')
                        ->form(fn($form) => CategoryForm::configure($form)->getComponents())
                        ->successNotification(
                            Notification::make()
                                ->title('Kategori berhasil diperbarui')
                                ->body('Perubahan telah disimpan.')
                                ->success()
                        ),
                    DeleteAction::make()
                        ->icon(Heroicon::OutlinedTrash)
                        ->modalHeading('Hapus Kategori')
                        ->modalDescription(fn(Category $record) => 'Apakah Anda yakin ingin menghapus kategori "' . $record->name . '"?')
                        ->successNotification(null)
                        ->after(function (Category $record) {
                            Notification::make()
                                ->title('Kategori "' . $record->name . '" berhasil dihapus')
                                ->body('Perubahan telah disimpan.')
                                ->success()
                                ->send();
                        }),
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
                        ->requiresConfirmation()
                        ->successNotification(
                            Notification::make()
                                ->title('Kategori berhasil aktifkan')
                                ->body('Perubahan telah disimpan.')
                                ->success()
                        ),
                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->successNotification(
                            Notification::make()
                                ->title('Kategori berhasil nonaktifkan')
                                ->body('Perubahan telah disimpan.')
                                ->success()
                        ),
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->title('Kategori berhasil dihapus')
                                ->body('Perubahan telah disimpan.')
                                ->success()
                        )
                ]),
            ])
            ->emptyStateHeading('Belum ada kategori')
            ->emptyStateDescription('Buat kategori untuk mengelompokkan produk.')
            ->emptyStateIcon(Heroicon::OutlinedTag)
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Kategori')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Tambah Kategori')
                    ->modalWidth('md')
                    ->form(fn($form) => CategoryForm::configure($form)->getComponents()),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
