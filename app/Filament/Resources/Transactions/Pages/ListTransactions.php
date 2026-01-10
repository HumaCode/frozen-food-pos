<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Resources\Transactions\Widgets\TransactionStats;
use App\Models\Transaction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransactionStats::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'today' => Tab::make('Hari Ini')
                ->badge(Transaction::whereDate('created_at', today())->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),

            'this_week' => Tab::make('Minggu Ini')
                ->badge(Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),

            'this_month' => Tab::make('Bulan Ini')
                ->badge(Transaction::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)),

            'all' => Tab::make('Semua')
                ->badge(Transaction::count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'today';
    }
}
