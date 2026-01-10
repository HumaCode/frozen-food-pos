<?php

namespace App\Filament\Resources\CashFlows\Pages;

use App\Filament\Resources\CashFlows\CashFlowResource;
use App\Filament\Resources\CashFlows\Widgets\CashFlowStats;
use App\Models\CashFlow;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListCashFlows extends ListRecords
{
    protected static string $resource = CashFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Kas')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CashFlowStats::class,
        ];
    }

    public function getTabs(): array
    {
        $today = today();

        return [
            'all' => Tab::make('Semua')
                ->badge(CashFlow::whereDate('created_at', $today)->count()),

            'in' => Tab::make('Kas Masuk')
                ->badge(CashFlow::whereDate('created_at', $today)->where('type', 'in')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-arrow-down-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'in')),

            'out' => Tab::make('Kas Keluar')
                ->badge(CashFlow::whereDate('created_at', $today)->where('type', 'out')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-arrow-up-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'out')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }
}
