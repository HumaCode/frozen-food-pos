@php
    $buy = (float) data_get($getState, 'buy_price', 0);
    $sell = (float) data_get($getState, 'sell_price', 0);

    $profit = $sell - $buy;
    $percentage = $buy > 0 ? round(($profit / $buy) * 100, 1) : 0;

    if ($profit > 0) {
        $color = 'text-green-600';
    } elseif ($profit < 0) {
        $color = 'text-red-600';
    } else {
        $color = 'text-yellow-500';
    }
@endphp

<span class="{{ $color }} font-semibold">
    Rp {{ number_format($profit, 0, ',', '.') }}
    ({{ $percentage }}%)
</span>
