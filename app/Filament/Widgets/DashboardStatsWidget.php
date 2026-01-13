<?php

namespace App\Filament\Widgets;

use App\Models\CashFlow;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        // Statistik Produk
        $totalProducts = Product::where('is_active', true)->count();
        $lowStock = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // Statistik Transaksi Hari Ini
        $todayTransactions = Transaction::whereDate('created_at', $today)->count();
        $todayRevenue = Transaction::whereDate('created_at', $today)->sum('total');
        $yesterdayRevenue = Transaction::whereDate('created_at', $today->copy()->subDay())->sum('total');
        $revenueChange = $yesterdayRevenue > 0
            ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100
            : 0;

        // Statistik Stok Hari Ini
        $stockIn = StockHistory::where('type', 'in')
            ->whereDate('created_at', $today)
            ->sum('qty');
        $stockOut = StockHistory::where('type', 'out')
            ->whereDate('created_at', $today)
            ->sum('qty');

        // Statistik Cashflow Hari Ini
        $cashIn = CashFlow::where('type', 'in')
            ->whereDate('created_at', $today)
            ->sum('amount');
        $cashOut = Cashflow::where('type', 'out')
            ->whereDate('created_at', $today)
            ->sum('amount');
        $netCashflow = $cashIn - $cashOut;

        return [
            // Widget Produk
            Stat::make('Total Produk Aktif', number_format($totalProducts))
                ->description($lowStock > 0 ? "{$lowStock} produk stok menipis" : 'Semua stok aman')
                ->descriptionIcon($lowStock > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->color($lowStock > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-cube')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105 hover:shadow-lg',
                ]),

            // Widget Transaksi
            Stat::make('Transaksi Hari Ini', number_format($todayTransactions))
                ->description('Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([3, 5, 7, 9, 12, 10, 15])
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-shopping-cart')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105 hover:shadow-lg',
                ]),

            // Widget Stok
            Stat::make('Pergerakan Stok', number_format($stockIn + $stockOut))
                ->description("Masuk: {$stockIn} | Keluar: {$stockOut}")
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->chart([5, 10, 8, 15, 12, 18, 20])
                ->color('info')
                ->icon('heroicon-o-archive-box')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105 hover:shadow-lg',
                ]),

            // Widget Cashflow
            Stat::make('Cashflow Hari Ini', 'Rp ' . number_format(abs($netCashflow), 0, ',', '.'))
                ->description($netCashflow >= 0 ? 'Surplus' : 'Defisit')
                ->descriptionIcon($netCashflow >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([20, 15, 25, 18, 30, 22, 28])
                ->color($netCashflow >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-banknotes')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105 hover:shadow-lg',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
