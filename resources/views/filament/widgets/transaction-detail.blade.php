<div class="space-y-6">
    {{-- Header Info --}}
    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Invoice</p>
            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $transaction->invoice_number }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Waktu Transaksi</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $transaction->created_at->format('d M Y, H:i:s') }}
            </p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Kasir</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $transaction->user->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Shift</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $transaction->shift->name ?? '-' }}</p>
        </div>
    </div>

    {{-- Items Table --}}
    <div>
        <h3 class="font-bold text-lg mb-3 text-gray-900 dark:text-white">Detail Item</h3>
        <div class="overflow-x-auto border border-gray-200 dark:border-gray-600 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Produk</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Harga</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Qty</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Diskon</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                    @forelse($transaction->items ?? [] as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</div>
                                @if ($item->is_wholesale)
                                    <span
                                        class="inline-block mt-1 text-xs bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-2 py-0.5 rounded">Grosir</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">Rp
                                {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">
                                {{ $item->qty }}</td>
                            <td class="px-4 py-3 text-center text-red-600 dark:text-red-400">
                                {{ $item->discount_per_item > 0 ? '- Rp ' . number_format($item->discount_per_item, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Rp
                                {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada
                                item</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary --}}
    <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
        <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
            <h3 class="font-bold text-gray-900 dark:text-white">Ringkasan Pembayaran</h3>
        </div>

        <div class="p-4 space-y-3 bg-white dark:bg-gray-800">
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                <span class="font-semibold text-gray-900 dark:text-white">Rp
                    {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>

            @if ($transaction->discount_amount > 0)
                <div class="flex justify-between items-center py-2 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-red-600 dark:text-red-400">Diskon</span>
                    <span class="font-semibold text-red-600 dark:text-red-400">- Rp
                        {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="flex justify-between items-center py-3 border-t-2 border-gray-300 dark:border-gray-600">
                <span class="font-bold text-lg text-gray-900 dark:text-white">Total</span>
                <span class="font-bold text-xl text-green-600 dark:text-green-400">Rp
                    {{ number_format($transaction->total, 0, ',', '.') }}</span>
            </div>

            <div class="pt-3 border-t-2 border-gray-300 dark:border-gray-600 space-y-3">
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600 dark:text-gray-400">Metode Pembayaran</span>
                    <span
                        class="font-semibold uppercase px-3 py-1 rounded-full text-xs 
                        {{ $transaction->payment_method === 'cash' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : '' }}
                        {{ $transaction->payment_method === 'qris' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' : '' }}
                        {{ $transaction->payment_method === 'debit' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : '' }}
                        {{ $transaction->payment_method === 'credit' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : '' }}
                        {{ $transaction->payment_method === 'transfer' ? 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200' : '' }}
                    ">{{ $transaction->payment_method }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Dibayar</span>
                    <span class="font-semibold text-gray-900 dark:text-white">Rp
                        {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Kembalian</span>
                    <span class="font-semibold text-blue-600 dark:text-blue-400">Rp
                        {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            @if ($transaction->notes)
                <div class="pt-3 border-t-2 border-gray-300 dark:border-gray-600">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Catatan:</p>
                    <p
                        class="text-sm italic text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                        {{ $transaction->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
