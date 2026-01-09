<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Masukkan nama kategori')
                            ->required(),
                        FileUpload::make('image')
                            ->label('Gambar')
                            ->imageEditor()
                            ->directory('categories')
                            ->columnSpanFull()
                            ->image()
                            ->disk('public')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Semakin kecil, semakin atas'),
                    ])->columns(2)
            ])->columns(1);
    }
}
