<?php

namespace App\Filament\Widgets;

use App\Models\TransactionItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopProductsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Top 10 Produk Terlaris';
    protected static ?int $sort = 3;

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
                'product_name',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->with('transaction');

        if ($days !== 'all') {
            $query->whereHas('transaction', function ($q) use ($days) {
                $q->where('created_at', '>=', now()->subDays((int) $days));
            });
        }

        $topProducts = $query
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Terjual',
                    'data' => $topProducts->pluck('total_qty')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(217, 70, 239, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(244, 63, 94, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(163, 230, 53, 0.8)',
                    ],
                ],
            ],
            'labels' => $topProducts->pluck('product_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
