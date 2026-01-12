<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryApiController extends Controller
{
    /**
     * Get all categories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order', 'asc');

        // Optional: dengan pagination
        if ($request->has('per_page')) {
            $categories = $query->paginate($request->per_page);
            return ApiResponse::paginate($categories, 'Data kategori berhasil diambil');
        }

        // Default: tanpa pagination
        $categories = $query->get()->map(function ($category) {
            return [
                'id'             => $category->id,
                'name'           => $category->name,
                'image'          => $category->image,
                'image_url'      => $category->image ? asset('storage/' . $category->image) : null,
                'sort_order'     => $category->sort_order,
                'is_active'      => $category->is_active,
                'products_count' => $category->products_count,
                'created_at'     => $category->created_at,
                'updated_at'     => $category->updated_at,
            ];
        });

        return ApiResponse::success($categories, 'Data kategori berhasil diambil');
    }

    /**
     * Get category detail
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function show(Category $category): JsonResponse
    {
        if (!$category->is_active) {
            return ApiResponse::notFound('Kategori tidak ditemukan');
        }

        $category->loadCount('products');

        return ApiResponse::success([
            'id'             => $category->id,
            'name'           => $category->name,
            'image'          => $category->image,
            'image_url'      => $category->image ? asset('storage/' . $category->image) : null,
            'sort_order'     => $category->sort_order,
            'is_active'      => $category->is_active,
            'products_count' => $category->products_count,
            'created_at'     => $category->created_at,
            'updated_at'     => $category->updated_at,
        ], 'Detail kategori berhasil diambil');
    }

    /**
     * Get products by category
     *
     * @param Request $request
     * @param Category $category
     * @return JsonResponse
     */
    public function products(Request $request, Category $category): JsonResponse
    {
        if (!$category->is_active) {
            return ApiResponse::notFound('Kategori tidak ditemukan');
        }

        $query = $category->products()
            ->where('is_active', true)
            ->orderBy('name', 'asc');

        // Optional: dengan pagination
        if ($request->has('per_page')) {
            $products = $query->paginate($request->per_page);

            $products->getCollection()->transform(function ($product) use ($category) {
                return $this->formatProduct($product, $category);
            });

            return ApiResponse::paginate($products, 'Produk dalam kategori berhasil diambil');
        }

        // Default: tanpa pagination
        $products = $query->get()->map(function ($product) use ($category) {
            return $this->formatProduct($product, $category);
        });

        return ApiResponse::success([
            'category' => [
                'id'        => $category->id,
                'name'      => $category->name,
                'image'     => $category->image,
                'image_url' => $category->image ? asset('storage/' . $category->image) : null,
            ],
            'products' => $products,
        ], 'Produk dalam kategori berhasil diambil');
    }

    /**
     * Format product data
     *
     * @param $product
     * @param Category $category
     * @return array
     */
    private function formatProduct($product, Category $category): array
    {
        return [
            'id'           => $product->id,
            'category_id'  => $product->category_id,
            'category'     => $category->name,
            'barcode'      => $product->barcode,
            'name'         => $product->name,
            'image'        => $product->image,
            'image_url'    => $product->image ? asset('storage/' . $product->image) : null,
            'buy_price'    => $product->buy_price,
            'sell_price'   => $product->sell_price,
            'stock'        => $product->stock,
            'unit'         => $product->unit,
            'min_stock'    => $product->min_stock,
            'is_low_stock' => $product->stock <= $product->min_stock,
            'expired_date' => $product->expired_date,
            'is_expired'   => $product->expired_date ? $product->expired_date->isPast() : false,
            'is_active'    => $product->is_active,
            'created_at'   => $product->created_at,
            'updated_at'   => $product->updated_at,
        ];
    }
}
