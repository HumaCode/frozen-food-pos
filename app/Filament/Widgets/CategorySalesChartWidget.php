<?php

namespace App\Filament\Widgets;

use App\Models\TransactionItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategorySalesChartWidget extends ChartWidget
{
    protected ?string $heading = 'Penjualan per Kategori';
    protected static ?int $sort = 4;

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 Hari',
            '30' => '30 Hari',
            'all' => 'Semua',
        ];
    }

    protected function getData(): array
    {
        $days = $this->filter;

        $query = TransactionItem::select(
                'products.category_id',
                'categories.name as category_name',
                DB::raw('SUM(transaction_items.subtotal) as total_sales')
            )
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id');

        if ($days !== 'all') {
            $query->where('transactions.created_at', '>=', now()->subDays((int) $days));
        }

        $categorySales = $query
            ->groupBy('products.category_id', 'categories.name')
            ->orderByDesc('total_sales')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $categorySales->pluck('total_sales')->toArray(),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(20, 184, 166, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                ],
            ],
            'labels' => $categorySales->pluck('category_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
