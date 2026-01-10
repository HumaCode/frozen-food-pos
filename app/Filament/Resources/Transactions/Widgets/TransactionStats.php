<?php

namespace App\Filament\Resources\Transactions\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransactionStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Transaction::whereDate('created_at', today());
        $yesterday = Transaction::whereDate('created_at', today()->subDay());

        $todayTotal = $today->sum('total');
        $todayCount = $today->count();
        $yesterdayTotal = $yesterday->sum('total');
        $yesterdayCount = $yesterday->count();

        // Calculate percentage change
        $totalChange = $yesterdayTotal > 0 
            ? round((($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100, 1) 
            : ($todayTotal > 0 ? 100 : 0);
        
        $countChange = $yesterdayCount > 0 
            ? round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 1) 
            : ($todayCount > 0 ? 100 : 0);

        // Average transaction
        $avgTransaction = $todayCount > 0 ? $todayTotal / $todayCount : 0;

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($todayTotal, 0, ',', '.'))
                ->description($totalChange >= 0 ? "+{$totalChange}% dari kemarin" : "{$totalChange}% dari kemarin")
                ->descriptionIcon($totalChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totalChange >= 0 ? 'success' : 'danger')
                ->chart($this->getChartData()),

            Stat::make('Jumlah Transaksi', $todayCount . ' transaksi')
                ->description($countChange >= 0 ? "+{$countChange}% dari kemarin" : "{$countChange}% dari kemarin")
                ->descriptionIcon($countChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($countChange >= 0 ? 'success' : 'danger'),

            Stat::make('Rata-rata Transaksi', 'Rp ' . number_format($avgTransaction, 0, ',', '.'))
                ->description('Per transaksi hari ini')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }

    protected function getChartData(): array
    {
        return Transaction::query()
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->toArray();
    }
}
