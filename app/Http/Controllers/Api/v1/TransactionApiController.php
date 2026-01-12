<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionApiController extends Controller
{
    /**
     * Get all transactions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query()
            ->with(['user', 'shift'])
            ->withCount('items')
            ->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by shift
        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date . ' 23:59:59']);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        $transactions->getCollection()->transform(function ($transaction) {
            return $this->formatTransaction($transaction);
        });

        return ApiResponse::paginate($transactions, 'Data transaksi berhasil diambil');
    }

    /**
     * Store new transaction
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shift_id'        => 'nullable|exists:shifts,id',
            'payment_method'  => 'required|in:cash,transfer,qris,debit,credit',
            'paid_amount'     => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:500',
            'items'           => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.qty'               => 'required|integer|min:1',
            'items.*.price'             => 'required|numeric|min:0',
            'items.*.discount_per_item' => 'nullable|numeric|min:0',
            'items.*.is_wholesale'      => 'nullable|boolean',
        ], [
            'payment_method.required' => 'Metode pembayaran wajib diisi',
            'payment_method.in'       => 'Metode pembayaran tidak valid',
            'paid_amount.required'    => 'Jumlah bayar wajib diisi',
            'paid_amount.min'         => 'Jumlah bayar tidak valid',
            'items.required'          => 'Item transaksi wajib diisi',
            'items.min'               => 'Minimal 1 item transaksi',
            'items.*.product_id.required' => 'Product ID wajib diisi',
            'items.*.product_id.exists'   => 'Produk tidak ditemukan',
            'items.*.qty.required'        => 'Qty wajib diisi',
            'items.*.qty.min'             => 'Qty minimal 1',
            'items.*.price.required'      => 'Harga wajib diisi',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        DB::beginTransaction();

        try {
            // Hitung subtotal
            $subtotal = collect($request->items)->sum(function ($item) {
                return $item['qty'] * $item['price'];
            });

            $discountAmount = $request->discount_amount ?? 0;
            $total = $subtotal - $discountAmount;
            $paidAmount = $request->paid_amount;
            $changeAmount = $paidAmount - $total;

            // Validasi pembayaran
            if ($paidAmount < $total) {
                return ApiResponse::validationError([
                    'paid_amount' => ['Jumlah bayar kurang dari total'],
                ]);
            }

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Buat transaksi
            $transaction = Transaction::create([
                'invoice_number'  => $invoiceNumber,
                'user_id'         => auth()->id(),
                'shift_id'        => $request->shift_id,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'total'           => $total,
                'paid_amount'     => $paidAmount,
                'change_amount'   => $changeAmount,
                'payment_method'  => $request->payment_method,
                'notes'           => $request->notes,
                'synced_at'       => now(),
            ]);

            // Simpan items & update stock
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                // Simpan item
                TransactionItem::create([
                    'transaction_id'    => $transaction->id,
                    'product_id'        => $item['product_id'],
                    'product_name'      => $product->name,
                    'qty'               => $item['qty'],
                    'price'             => $item['price'],
                    'discount_per_item' => $item['discount_per_item'] ?? 0,
                    'subtotal'          => ($item['qty'] * $item['price']) - (($item['discount_per_item'] ?? 0) * $item['qty']),
                    'is_wholesale'      => $item['is_wholesale'] ?? false,
                ]);

                // Kurangi stock
                $product->decrement('stock', $item['qty']);
            }

            DB::commit();

            // Load relations
            $transaction->load(['user', 'shift', 'items']);

            return ApiResponse::created($this->formatTransaction($transaction, true), 'Transaksi berhasil dibuat');

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal membuat transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Get today's transactions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $query = Transaction::query()
            ->with(['user', 'shift'])
            ->withCount('items')
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc');

        // Filter by current user only
        if ($request->has('own') && $request->own == true) {
            $query->where('user_id', auth()->id());
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        $transactions->getCollection()->transform(function ($transaction) {
            return $this->formatTransaction($transaction);
        });

        return ApiResponse::paginate($transactions, 'Transaksi hari ini berhasil diambil');
    }

    /**
     * Get transaction summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $userId = $request->get('user_id');
        $shiftId = $request->get('shift_id');

        $query = Transaction::query()->whereDate('created_at', $date);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        // Summary data
        $totalTransactions = $query->count();
        $totalSales = $query->sum('total');
        $totalDiscount = $query->sum('discount_amount');
        $totalItems = TransactionItem::whereIn('transaction_id', $query->pluck('id'))->sum('qty');

        // By payment method
        $byPaymentMethod = Transaction::query()
            ->whereDate('created_at', $date)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($shiftId, fn($q) => $q->where('shift_id', $shiftId))
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'payment_method' => $item->payment_method,
                    'label'          => $this->getPaymentMethodLabel($item->payment_method),
                    'count'          => $item->count,
                    'total'          => $item->total,
                ];
            });

        // Hourly breakdown
        $hourlyBreakdown = Transaction::query()
            ->whereDate('created_at', $date)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($shiftId, fn($q) => $q->where('shift_id', $shiftId))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, SUM(total) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour'  => sprintf('%02d:00', $item->hour),
                    'count' => $item->count,
                    'total' => $item->total,
                ];
            });

        return ApiResponse::success([
            'date'               => $date,
            'total_transactions' => $totalTransactions,
            'total_sales'        => $totalSales,
            'total_discount'     => $totalDiscount,
            'total_items'        => $totalItems,
            'average_transaction' => $totalTransactions > 0 ? round($totalSales / $totalTransactions, 2) : 0,
            'by_payment_method'  => $byPaymentMethod,
            'hourly_breakdown'   => $hourlyBreakdown,
        ], 'Ringkasan transaksi berhasil diambil');
    }

    /**
     * Sync offline transactions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transactions' => 'required|array|min:1',
            'transactions.*.local_id'        => 'required|string',
            'transactions.*.shift_id'        => 'nullable|exists:shifts,id',
            'transactions.*.payment_method'  => 'required|in:cash,transfer,qris,debit,credit',
            'transactions.*.paid_amount'     => 'required|numeric|min:0',
            'transactions.*.discount_amount' => 'nullable|numeric|min:0',
            'transactions.*.notes'           => 'nullable|string|max:500',
            'transactions.*.created_at'      => 'required|date',
            'transactions.*.items'           => 'required|array|min:1',
            'transactions.*.items.*.product_id'        => 'required|exists:products,id',
            'transactions.*.items.*.qty'               => 'required|integer|min:1',
            'transactions.*.items.*.price'             => 'required|numeric|min:0',
            'transactions.*.items.*.discount_per_item' => 'nullable|numeric|min:0',
            'transactions.*.items.*.is_wholesale'      => 'nullable|boolean',
        ], [
            'transactions.required'     => 'Data transaksi wajib diisi',
            'transactions.min'          => 'Minimal 1 transaksi',
            'transactions.*.local_id.required' => 'Local ID wajib diisi',
            'transactions.*.payment_method.required' => 'Metode pembayaran wajib diisi',
            'transactions.*.items.required' => 'Item transaksi wajib diisi',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $results = [
            'success' => [],
            'failed'  => [],
        ];

        foreach ($request->transactions as $transactionData) {
            DB::beginTransaction();

            try {
                // Hitung subtotal
                $subtotal = collect($transactionData['items'])->sum(function ($item) {
                    return $item['qty'] * $item['price'];
                });

                $discountAmount = $transactionData['discount_amount'] ?? 0;
                $total = $subtotal - $discountAmount;
                $paidAmount = $transactionData['paid_amount'];
                $changeAmount = $paidAmount - $total;

                // Generate invoice number
                $invoiceNumber = $this->generateInvoiceNumber();

                // Buat transaksi
                $transaction = Transaction::create([
                    'invoice_number'  => $invoiceNumber,
                    'user_id'         => auth()->id(),
                    'shift_id'        => $transactionData['shift_id'] ?? null,
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total'           => $total,
                    'paid_amount'     => $paidAmount,
                    'change_amount'   => $changeAmount,
                    'payment_method'  => $transactionData['payment_method'],
                    'notes'           => $transactionData['notes'] ?? null,
                    'created_at'      => $transactionData['created_at'],
                    'synced_at'       => now(),
                ]);

                // Simpan items & update stock
                foreach ($transactionData['items'] as $item) {
                    $product = Product::find($item['product_id']);

                    TransactionItem::create([
                        'transaction_id'    => $transaction->id,
                        'product_id'        => $item['product_id'],
                        'product_name'      => $product->name,
                        'qty'               => $item['qty'],
                        'price'             => $item['price'],
                        'discount_per_item' => $item['discount_per_item'] ?? 0,
                        'subtotal'          => ($item['qty'] * $item['price']) - (($item['discount_per_item'] ?? 0) * $item['qty']),
                        'is_wholesale'      => $item['is_wholesale'] ?? false,
                    ]);

                    $product->decrement('stock', $item['qty']);
                }

                DB::commit();

                $results['success'][] = [
                    'local_id'       => $transactionData['local_id'],
                    'transaction_id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                ];

            } catch (\Exception $e) {
                DB::rollBack();

                $results['failed'][] = [
                    'local_id' => $transactionData['local_id'],
                    'error'    => $e->getMessage(),
                ];
            }
        }

        $totalSuccess = count($results['success']);
        $totalFailed = count($results['failed']);

        return ApiResponse::success([
            'total_received' => count($request->transactions),
            'total_success'  => $totalSuccess,
            'total_failed'   => $totalFailed,
            'results'        => $results,
        ], "Sync selesai: {$totalSuccess} berhasil, {$totalFailed} gagal");
    }

    /**
     * Get transaction detail
     *
     * @param Transaction $transaction
     * @return JsonResponse
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['user', 'shift', 'items.product']);

        return ApiResponse::success($this->formatTransaction($transaction, true), 'Detail transaksi berhasil diambil');
    }

    /**
     * Generate invoice number
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        
        $lastTransaction = Transaction::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastTransaction) {
            $lastSequence = (int) substr($lastTransaction->invoice_number, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get payment method label
     *
     * @param string $method
     * @return string
     */
    private function getPaymentMethodLabel(string $method): string
    {
        return match($method) {
            'cash'     => 'Tunai',
            'transfer' => 'Transfer',
            'qris'     => 'QRIS',
            'debit'    => 'Debit',
            'credit'   => 'Kredit',
            default    => $method,
        };
    }

    /**
     * Format transaction data
     *
     * @param Transaction $transaction
     * @param bool $withItems
     * @return array
     */
    private function formatTransaction(Transaction $transaction, bool $withItems = false): array
    {
        $data = [
            'id'              => $transaction->id,
            'invoice_number'  => $transaction->invoice_number,
            'user_id'         => $transaction->user_id,
            'user'            => $transaction->user ? [
                'id'   => $transaction->user->id,
                'name' => $transaction->user->name,
            ] : null,
            'shift_id'        => $transaction->shift_id,
            'shift'           => $transaction->shift ? [
                'id'   => $transaction->shift->id,
                'name' => $transaction->shift->name,
            ] : null,
            'subtotal'        => $transaction->subtotal,
            'discount_amount' => $transaction->discount_amount,
            'total'           => $transaction->total,
            'paid_amount'     => $transaction->paid_amount,
            'change_amount'   => $transaction->change_amount,
            'payment_method'  => $transaction->payment_method,
            'payment_label'   => $this->getPaymentMethodLabel($transaction->payment_method),
            'notes'           => $transaction->notes,
            'items_count'     => $transaction->items_count ?? $transaction->items->count(),
            'synced_at'       => $transaction->synced_at,
            'created_at'      => $transaction->created_at,
            'updated_at'      => $transaction->updated_at,
        ];

        if ($withItems) {
            $data['items'] = $transaction->items->map(function ($item) {
                return [
                    'id'               => $item->id,
                    'product_id'       => $item->product_id,
                    'product_name'     => $item->product_name,
                    'qty'              => $item->qty,
                    'price'            => $item->price,
                    'discount_per_item' => $item->discount_per_item,
                    'subtotal'         => $item->subtotal,
                    'is_wholesale'     => $item->is_wholesale,
                ];
            });
        }

        return $data;
    }
}
