<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->components([
                        Section::make('Informasi Pengguna')
                            ->icon('heroicon-o-user')
                            ->description('Data utama pengguna')
                            ->components([
                                TextInput::make('name')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan nama lengkap')
                                    ->prefixIcon('heroicon-o-user'),

                                TextInput::make('username')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-user'),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('contoh@email.com')
                                    ->prefixIcon('heroicon-o-envelope'),

                                TextInput::make('phone')
                                    ->label('Nomor Telepon')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('08xxxxxxxxxx')
                                    ->prefixIcon('heroicon-o-phone'),

                                 // Tambahkan Select Role
                                Select::make('roles')
                                    ->label('Role')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Pilih role'),
                            ])->columns(2),

                        Section::make('Keamanan')
                            ->icon('heroicon-o-lock-closed')
                            ->description('Password untuk login')
                            ->components([
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->required(fn(string $context): bool => $context === 'create')
                                    ->minLength(6)
                                    ->placeholder(fn(string $context): string => $context === 'create' ? 'Minimal 6 karakter' : 'Kosongkan jika tidak ingin mengubah')
                                    ->prefixIcon('heroicon-o-key')
                                    ->helperText(fn(string $context): string => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : ''),

                                TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn(string $context): bool => $context === 'create')
                                    ->same('password')
                                    ->placeholder('Ulangi password')
                                    ->prefixIcon('heroicon-o-key')
                                    ->dehydrated(false),
                            ])->columns(2),

                        Section::make('Avatar')
                            ->icon('heroicon-o-user')
                            ->description('Upload avatar (optional)')
                            ->components([
                                FileUpload::make('avatar')
                                    ->label('Avatar')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('users')
                                    ->disk('public')
                                    ->maxSize(2048)
                                    ->hint('Maksimal ukuran file 2MB')
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp'
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->components([


                        Section::make('Status')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->components([
                                Toggle::make('is_active')
                                    ->label('Pengguna Aktif')
                                    ->helperText('Pengguna nonaktif tidak dapat login')
                                    ->default(true)
                                    ->inline(false),

                                Placeholder::make('created_info')
                                    ->label('')
                                    ->content(function ($record) {
                                        if (!$record) return '';

                                        $created = $record->created_at?->format('d/m/Y H:i');
                                        $updated = $record->updated_at?->diffForHumans();

                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                                <p>Dibuat: ' . $created . '</p>
                                                <p>Diperbarui: ' . $updated . '</p>
                                            </div>'
                                        );
                                    })
                                    ->visibleOn('edit'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(2);
    }
}
