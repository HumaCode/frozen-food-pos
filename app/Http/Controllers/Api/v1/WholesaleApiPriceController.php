<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WholesalePrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WholesaleApiPriceController extends Controller
{
    /**
     * Get all wholesale prices
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = WholesalePrice::query()
            ->with('product.category')
            ->where('is_active', true)
            ->orderBy('product_id', 'asc')
            ->orderBy('min_qty', 'asc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $wholesalePrices = $query->paginate($perPage);

        $wholesalePrices->getCollection()->transform(function ($wholesale) {
            return $this->formatWholesalePrice($wholesale);
        });

        return ApiResponse::paginate($wholesalePrices, 'Data harga grosir berhasil diambil');
    }

    /**
     * Get wholesale prices by product
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function byProduct(Product $product): JsonResponse
    {
        if (!$product->is_active) {
            return ApiResponse::notFound('Produk tidak ditemukan');
        }

        $wholesalePrices = WholesalePrice::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('min_qty', 'asc')
            ->get()
            ->map(function ($wholesale) {
                return [
                    'id'              => $wholesale->id,
                    'min_qty'         => $wholesale->min_qty,
                    'price'           => $wholesale->price,
                    'savings'         => $wholesale->product->sell_price - $wholesale->price,
                    'discount'        => round((($wholesale->product->sell_price - $wholesale->price) / $wholesale->product->sell_price) * 100, 1),
                    'is_active'       => $wholesale->is_active,
                ];
            });

        return ApiResponse::success([
            'product' => [
                'id'         => $product->id,
                'name'       => $product->name,
                'barcode'    => $product->barcode,
                'sell_price' => $product->sell_price,
                'stock'      => $product->stock,
                'unit'       => $product->unit,
            ],
            'wholesale_prices' => $wholesalePrices,
        ], 'Harga grosir produk berhasil diambil');
    }

    /**
     * Calculate wholesale price for given qty
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|integer|min:1',
        ], [
            'product_id.required' => 'Product ID wajib diisi',
            'product_id.exists'   => 'Produk tidak ditemukan',
            'qty.required'        => 'Qty wajib diisi',
            'qty.min'             => 'Qty minimal 1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $product = Product::with(['wholesalePrices' => function ($query) {
            $query->where('is_active', true)->orderBy('min_qty', 'desc');
        }])->find($request->product_id);

        if (!$product->is_active) {
            return ApiResponse::notFound('Produk tidak ditemukan');
        }

        $qty = $request->qty;
        $normalPrice = $product->sell_price;
        $normalTotal = $normalPrice * $qty;

        // Cari harga grosir yang applicable
        $applicableWholesale = $product->wholesalePrices
            ->where('min_qty', '<=', $qty)
            ->first();

        $finalPrice = $normalPrice;
        $finalTotal = $normalTotal;
        $isWholesale = false;
        $wholesaleInfo = null;

        if ($applicableWholesale) {
            $finalPrice = $applicableWholesale->price;
            $finalTotal = $finalPrice * $qty;
            $isWholesale = true;
            $wholesaleInfo = [
                'id'       => $applicableWholesale->id,
                'min_qty'  => $applicableWholesale->min_qty,
                'price'    => $applicableWholesale->price,
            ];
        }

        $savings = $normalTotal - $finalTotal;
        $savingsPercent = $normalTotal > 0
            ? round(($savings / $normalTotal) * 100, 1)
            : 0;

        return ApiResponse::success([
            'product' => [
                'id'         => $product->id,
                'name'       => $product->name,
                'barcode'    => $product->barcode,
                'sell_price' => $product->sell_price,
            ],
            'qty'              => $qty,
            'normal_price'     => $normalPrice,
            'normal_total'     => $normalTotal,
            'final_price'      => $finalPrice,
            'final_total'      => $finalTotal,
            'is_wholesale'     => $isWholesale,
            'wholesale_info'   => $wholesaleInfo,
            'savings'          => $savings,
            'savings_percent'  => $savingsPercent,
        ], 'Perhitungan harga grosir berhasil');
    }

    /**
     * Format wholesale price data
     *
     * @param WholesalePrice $wholesale
     * @return array
     */
    private function formatWholesalePrice(WholesalePrice $wholesale): array
    {
        $product = $wholesale->product;
        $savings = $product ? $product->sell_price - $wholesale->price : 0;
        $discount = $product && $product->sell_price > 0
            ? round(($savings / $product->sell_price) * 100, 1)
            : 0;

        return [
            'id'         => $wholesale->id,
            'product_id' => $wholesale->product_id,
            'product'    => $product ? [
                'id'         => $product->id,
                'name'       => $product->name,
                'barcode'    => $product->barcode,
                'sell_price' => $product->sell_price,
                'category'   => $product->category ? [
                    'id'   => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
            ] : null,
            'min_qty'    => $wholesale->min_qty,
            'price'      => $wholesale->price,
            'savings'    => $savings,
            'discount'   => $discount,
            'is_active'  => $wholesale->is_active,
            'created_at' => $wholesale->created_at,
            'updated_at' => $wholesale->updated_at,
        ];
    }
}
