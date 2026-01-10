<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak Struk')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn (Transaction $record) => route('transactions.print', $record->id))
                ->openUrlInNewTab()
                ->visible(fn () => \Illuminate\Support\Facades\Route::has('transactions.print')),
        ];
    }
}
