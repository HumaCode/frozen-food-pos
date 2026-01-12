<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountApiController extends Controller
{
    /**
     * Get all discounts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Discount::query()
            ->with('product')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $discounts = $query->paginate($perPage);

        $discounts->getCollection()->transform(function ($discount) {
            return $this->formatDiscount($discount);
        });

        return ApiResponse::paginate($discounts, 'Data diskon berhasil diambil');
    }

    /**
     * Get active discounts (currently valid)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function active(Request $request): JsonResponse
    {
        $now = now();

        $query = Discount::query()
            ->with('product')
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $discounts = $query->paginate($perPage);

        $discounts->getCollection()->transform(function ($discount) {
            return $this->formatDiscount($discount);
        });

        return ApiResponse::paginate($discounts, 'Data diskon aktif berhasil diambil');
    }

    /**
     * Get discount detail
     *
     * @param Discount $discount
     * @return JsonResponse
     */
    public function show(Discount $discount): JsonResponse
    {
        if (!$discount->is_active) {
            return ApiResponse::notFound('Diskon tidak ditemukan');
        }

        $discount->load('product');

        return ApiResponse::success($this->formatDiscount($discount), 'Detail diskon berhasil diambil');
    }

    /**
     * Check applicable discounts for cart
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ], [
            'items.required' => 'Items wajib diisi',
            'items.array' => 'Items harus berupa array',
            'items.min' => 'Minimal 1 item',
            'items.*.product_id.required' => 'Product ID wajib diisi',
            'items.*.product_id.exists' => 'Product tidak ditemukan',
            'items.*.qty.required' => 'Qty wajib diisi',
            'items.*.qty.min' => 'Qty minimal 1',
            'items.*.price.required' => 'Price wajib diisi',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $items = collect($request->items);
        $now = now();

        // Hitung subtotal
        $subtotal = $items->sum(function ($item) {
            return $item['qty'] * $item['price'];
        });

        // Get active discounts
        $activeDiscounts = Discount::query()
            ->with('product')
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->get();

        $appliedDiscounts = [];
        $totalDiscount = 0;

        foreach ($activeDiscounts as $discount) {
            // Diskon per produk
            if ($discount->type === 'product' && $discount->product_id) {
                $item = $items->firstWhere('product_id', $discount->product_id);

                if ($item) {
                    $itemSubtotal = $item['qty'] * $item['price'];
                    $discountAmount = $this->calculateDiscountAmount($discount, $itemSubtotal);

                    if ($discountAmount > 0) {
                        $appliedDiscounts[] = [
                            'discount_id'     => $discount->id,
                            'name'            => $discount->name,
                            'type'            => $discount->type,
                            'product_id'      => $discount->product_id,
                            'product_name'    => $discount->product?->name,
                            'discount_type'   => $discount->discount_type,
                            'discount_value'  => $discount->value,
                            'discount_amount' => $discountAmount,
                        ];
                        $totalDiscount += $discountAmount;
                    }
                }
            }

            // Diskon total belanja
            if ($discount->type === 'total' && $subtotal >= ($discount->min_purchase ?? 0)) {
                $discountAmount = $this->calculateDiscountAmount($discount, $subtotal);

                if ($discountAmount > 0) {
                    $appliedDiscounts[] = [
                        'discount_id'     => $discount->id,
                        'name'            => $discount->name,
                        'type'            => $discount->type,
                        'min_purchase'    => $discount->min_purchase,
                        'discount_type'   => $discount->discount_type,
                        'discount_value'  => $discount->value,
                        'discount_amount' => $discountAmount,
                    ];
                    $totalDiscount += $discountAmount;
                }
            }
        }

        return ApiResponse::success([
            'subtotal'          => $subtotal,
            'total_discount'    => $totalDiscount,
            'grand_total'       => $subtotal - $totalDiscount,
            'applied_discounts' => $appliedDiscounts,
        ], 'Perhitungan diskon berhasil');
    }

    /**
     * Calculate discount amount
     *
     * @param Discount $discount
     * @param float $amount
     * @return float
     */
    private function calculateDiscountAmount(Discount $discount, float $amount): float
    {
        if ($discount->discount_type === 'percentage') {
            return round(($discount->value / 100) * $amount, 2);
        }

        // Nominal discount - tidak boleh lebih dari amount
        return min($discount->value, $amount);
    }

    /**
     * Format discount data
     *
     * @param Discount $discount
     * @return array
     */
    private function formatDiscount(Discount $discount): array
    {
        $now = now();
        $isExpired = $discount->end_date && $discount->end_date->isPast();
        $isScheduled = $discount->start_date && $discount->start_date->isFuture();
        $isValid = !$isExpired && !$isScheduled;

        return [
            'id'             => $discount->id,
            'name'           => $discount->name,
            'type'           => $discount->type,
            'type_label'     => $discount->type === 'product' ? 'Per Produk' : 'Total Belanja',
            'product_id'     => $discount->product_id,
            'product'        => $discount->product ? [
                'id'   => $discount->product->id,
                'name' => $discount->product->name,
            ] : null,
            'min_purchase'   => $discount->min_purchase,
            'discount_type'  => $discount->discount_type,
            'discount_label' => $discount->discount_type === 'percentage' ? 'Persentase' : 'Nominal',
            'value'          => $discount->value,
            'value_formatted' => $discount->discount_type === 'percentage'
                ? $discount->value . '%'
                : 'Rp ' . number_format($discount->value, 0, ',', '.'),
            'start_date'     => $discount->start_date?->format('Y-m-d'),
            'end_date'       => $discount->end_date?->format('Y-m-d'),
            'is_active'      => $discount->is_active,
            'is_expired'     => $isExpired,
            'is_scheduled'   => $isScheduled,
            'is_valid'       => $isValid,
            'status'         => $isExpired ? 'expired' : ($isScheduled ? 'scheduled' : ($discount->is_active ? 'active' : 'inactive')),
            'status_label'   => $isExpired ? 'Berakhir' : ($isScheduled ? 'Terjadwal' : ($discount->is_active ? 'Aktif' : 'Nonaktif')),
            'created_at'     => $discount->created_at,
            'updated_at'     => $discount->updated_at,
        ];
    }
}
