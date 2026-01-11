<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Gambar')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp'
                            ])
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama kategori'),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Semakin kecil, semakin atas'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2)
            ])->columns(1);
    }
}
