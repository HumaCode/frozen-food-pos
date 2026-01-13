<?php

namespace App\Filament\Widgets;

use App\Models\CashFlow;
use Filament\Widgets\ChartWidget;

class CashflowChartWidget extends ChartWidget
{
    protected ?string $heading = 'Cashflow (Pemasukan vs Pengeluaran)';
    protected static ?int $sort = 5;

    public ?string $filter = '7';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 Hari',
            '14' => '14 Hari',
            '30' => '30 Hari',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $cashIn = [];
        $cashOut = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $in = CashFlow::where('type', 'in')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $out = Cashflow::where('type', 'out')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $cashIn[] = $in;
            $cashOut[] = $out;
            $labels[] = $date->format('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan (Rp)',
                    'data' => $cashIn,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Pengeluaran (Rp)',
                    'data' => $cashOut,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
        ];
    }
}
