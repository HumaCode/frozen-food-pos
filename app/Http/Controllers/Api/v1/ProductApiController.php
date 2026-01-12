<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductApiController extends Controller
{
    /**
     * Get all products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name', 'asc');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return ApiResponse::paginate($products, 'Data produk berhasil diambil');
    }

    /**
     * Search products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1',
        ], [
            'q.required' => 'Kata kunci pencarian wajib diisi',
            'q.min' => 'Kata kunci minimal 1 karakter',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $keyword = $request->q;

        $query = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%");
            })
            ->orderBy('name', 'asc');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return ApiResponse::paginate($products, 'Hasil pencarian produk');
    }

    /**
     * Get low stock products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lowStock(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return ApiResponse::paginate($products, 'Data produk stok menipis');
    }

    /**
     * Get expired products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function expired(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->whereNotNull('expired_date')
            ->where('expired_date', '<=', now())
            ->orderBy('expired_date', 'asc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return ApiResponse::paginate($products, 'Data produk expired');
    }

    /**
     * Get product detail
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function show(Product $product): JsonResponse
    {
        if (!$product->is_active) {
            return ApiResponse::notFound('Produk tidak ditemukan');
        }

        $product->load(['category', 'wholesalePrices' => function ($query) {
            $query->where('is_active', true)->orderBy('min_qty', 'asc');
        }]);

        $data = $this->formatProduct($product);

        // Tambahkan harga grosir
        $data['wholesale_prices'] = $product->wholesalePrices->map(function ($wholesale) {
            return [
                'id'       => $wholesale->id,
                'min_qty'  => $wholesale->min_qty,
                'price'    => $wholesale->price,
                'savings'  => $wholesale->product->sell_price - $wholesale->price,
                'discount' => round((($wholesale->product->sell_price - $wholesale->price) / $wholesale->product->sell_price) * 100, 1),
            ];
        });

        return ApiResponse::success($data, 'Detail produk berhasil diambil');
    }

    /**
     * Find product by barcode
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function findByBarcode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string',
        ], [
            'barcode.required' => 'Barcode wajib diisi',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $product = Product::query()
            ->with(['category', 'wholesalePrices' => function ($query) {
                $query->where('is_active', true)->orderBy('min_qty', 'asc');
            }])
            ->where('barcode', $request->barcode)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return ApiResponse::notFound('Produk dengan barcode tersebut tidak ditemukan');
        }

        $data = $this->formatProduct($product);

        // Tambahkan harga grosir
        $data['wholesale_prices'] = $product->wholesalePrices->map(function ($wholesale) {
            return [
                'id'       => $wholesale->id,
                'min_qty'  => $wholesale->min_qty,
                'price'    => $wholesale->price,
                'savings'  => $wholesale->product->sell_price - $wholesale->price,
                'discount' => round((($wholesale->product->sell_price - $wholesale->price) / $wholesale->product->sell_price) * 100, 1),
            ];
        });

        return ApiResponse::success($data, 'Produk ditemukan');
    }

    /**
     * Format product data
     *
     * @param Product $product
     * @return array
     */
    private function formatProduct(Product $product): array
    {
        return [
            'id'            => $product->id,
            'category_id'   => $product->category_id,
            'category'      => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'barcode'       => $product->barcode,
            'name'          => $product->name,
            'image'         => $product->image,
            'image_url'     => $product->image ? asset('storage/' . $product->image) : null,
            'buy_price'     => $product->buy_price,
            'sell_price'    => $product->sell_price,
            'profit'        => $product->sell_price - $product->buy_price,
            'profit_margin' => $product->buy_price > 0
                ? round((($product->sell_price - $product->buy_price) / $product->buy_price) * 100, 1)
                : 0,
            'stock'         => $product->stock,
            'unit'          => $product->unit,
            'min_stock'     => $product->min_stock,
            'is_low_stock'  => $product->stock <= $product->min_stock,
            'expired_date'  => $product->expired_date?->format('Y-m-d'),
            'is_expired'    => $product->expired_date ? $product->expired_date->isPast() : false,
            'days_to_expire' => $product->expired_date && !$product->expired_date->isPast()
                ? $product->expired_date->diffInDays(now())
                : null,
            'is_active'     => $product->is_active,
            'created_at'    => $product->created_at,
            'updated_at'    => $product->updated_at,
        ];
    }
}
