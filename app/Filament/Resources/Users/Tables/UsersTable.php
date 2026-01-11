<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->alignCenter()
                    ->disk('public')
                    ->imageSize(50)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? 'Store') . '&background=6366f1&color=fff&size=60'),


                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->formatStateUsing(fn(string $state) => ucwords(strtolower($state)))
                    ->description(fn(User $record) => $record->email),
                
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->icon('heroicon-o-phone')
                    ->iconColor('gray')
                    ->placeholder('-')
                    ->alignCenter()
                    ->copyable()
                    ->copyMessage('Nomor disalin!'),

                TextColumn::make('transactions_count')
                    ->label('Transaksi')
                    ->counts('transactions')
                    ->badge()
                    ->alignCenter()
                    ->color('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter()
                    ->afterStateUpdated(function (User $record, bool $state) {
                        $status = $state ? 'aktifkan' : 'nonaktifkan';
                        Notification::make()
                            ->title('User dengan nama "' . $record->name . '" berhasil ' . $status)
                            ->body('Perubahan telah disimpan.')
                            ->success()
                            ->send();
                    }),

                TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->since()
                    ->sortable()
                    ->placeholder('Belum pernah')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->searchable()
                    ->placeholder('Semua'),

            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon(Heroicon::OutlinedKey)
                        ->color('warning')
                        ->form([
                            TextInput::make('new_password')
                                ->label('Password Baru')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(6)
                                ->placeholder('Minimal 6 karakter'),
                            TextInput::make('new_password_confirmation')
                                ->label('Konfirmasi Password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->same('new_password')
                                ->placeholder('Ulangi password'),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'password' => Hash::make($data['new_password']),
                            ]);

                            Notification::make()
                                ->title('Password berhasil direset')
                                ->body('Password untuk ' . $record->name . ' telah diperbarui.')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Reset Password')
                        ->modalWidth('sm')
                        ->requiresConfirmation()
                        ->modalDescription(fn(User $record) => 'Reset password untuk ' . $record->name . '?'),
                    DeleteAction::make()
                        ->icon(Heroicon::OutlinedTrash)
                        ->before(function (User $record, DeleteAction $action) {
                            if ($record->id === auth()->id()) {
                                Notification::make()
                                    ->title('Tidak dapat menghapus')
                                    ->body('Anda tidak dapat menghapus akun sendiri.')
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
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
                        ->requiresConfirmation(),
                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->id !== auth()->id()) {
                                    $record->update(['is_active' => false]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    DeleteBulkAction::make()
                        ->before(function ($records, DeleteBulkAction $action) {
                            if ($records->contains('id', auth()->id())) {
                                Notification::make()
                                    ->title('Tidak dapat menghapus')
                                    ->body('Anda tidak dapat menghapus akun sendiri.')
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Tambahkan pengguna untuk mengakses sistem.')
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Pengguna')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
