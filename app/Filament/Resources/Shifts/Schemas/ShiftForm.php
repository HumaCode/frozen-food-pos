<?php

namespace App\Filament\Resources\Shifts\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Shift')
                    ->icon('heroicon-o-clock')
                    ->description('Atur jadwal shift kerja kasir')
                    ->components([
                        TextInput::make('name')
                            ->label('Nama Shift')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Pagi, Siang, Malam')
                            ->columnSpanFull(),

                        TimePicker::make('start_time')
                            ->label('Waktu Mulai')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->live()
                            ->prefixIcon('heroicon-o-play'),

                        TimePicker::make('end_time')
                            ->label('Waktu Selesai')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->live()
                            ->prefixIcon('heroicon-o-stop'),

                        Placeholder::make('duration_info')
                            ->label('')
                            ->content(function (Get $get) {
                                $start = $get('start_time');
                                $end = $get('end_time');

                                if (!$start || !$end) {
                                    return '';
                                }

                                try {
                                    $startTime = \Carbon\Carbon::parse($start);
                                    $endTime = \Carbon\Carbon::parse($end);

                                    if ($endTime->lessThanOrEqualTo($startTime)) {
                                        $endTime->addDay();
                                    }

                                    $diffInHours = $startTime->diffInHours($endTime);
                                    $diffInMinutes = $startTime->diffInMinutes($endTime) % 60;

                                    $durationText = $diffInHours . ' jam';
                                    if ($diffInMinutes > 0) {
                                        $durationText .= ' ' . $diffInMinutes . ' menit';
                                    }

                                    $isOvernight = $endTime->greaterThan(\Carbon\Carbon::parse($end));

                                    $clockIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';

                                    $moonIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>';

                                    $overnightBadge = $isOvernight
                                        ? '<div class="inline-flex items-center gap-1.5 text-xs text-yellow-700 dark:text-yellow-500 bg-yellow-50 dark:bg-yellow-900/30 px-2.5 py-1.5 rounded-md">' . $moonIcon . '<span>Shift Malam</span></div>'
                                        : '';

                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center gap-3">
                    <div class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 rounded-md">
                        ' . $clockIcon . '
                        <span>Durasi: <strong>' . $durationText . '</strong></span>
                    </div>
                    ' . $overnightBadge . '
                </div>'
                                    );
                                } catch (\Exception $e) {
                                    return '';
                                }
                            }),

                        Toggle::make('is_active')
                            ->label('Shift Aktif')
                            ->helperText('Shift yang tidak aktif tidak akan muncul di aplikasi kasir')
                            ->default(true)
                            ->inline(false)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
