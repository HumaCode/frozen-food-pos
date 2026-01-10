<?php

namespace App\Filament\Resources\CashFlows\Widgets;

use App\Models\CashFlow;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashFlowStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = today();

        $todayIn = CashFlow::whereDate('created_at', $today)->where('type', 'in')->sum('amount');
        $todayOut = CashFlow::whereDate('created_at', $today)->where('type', 'out')->sum('amount');
        $todayBalance = $todayIn - $todayOut;

        $monthIn = CashFlow::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('type', 'in')
            ->sum('amount');

        $monthOut = CashFlow::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('type', 'out')
            ->sum('amount');

        $monthBalance = $monthIn - $monthOut;

        return [
            Stat::make('Kas Masuk Hari Ini', 'Rp ' . number_format($todayIn, 0, ',', '.'))
                ->description('Total kas masuk')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('success'),

            Stat::make('Kas Keluar Hari Ini', 'Rp ' . number_format($todayOut, 0, ',', '.'))
                ->description('Total kas keluar')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('danger'),

            Stat::make('Saldo Hari Ini', 'Rp ' . number_format($todayBalance, 0, ',', '.'))
                ->description('Kas masuk - Kas keluar')
                ->descriptionIcon($todayBalance >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($todayBalance >= 0 ? 'success' : 'danger'),

            Stat::make('Saldo Bulan Ini', 'Rp ' . number_format($monthBalance, 0, ',', '.'))
                ->description('In: ' . number_format($monthIn, 0, ',', '.') . ' | Out: ' . number_format($monthOut, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color($monthBalance >= 0 ? 'info' : 'warning'),
        ];
    }
}
