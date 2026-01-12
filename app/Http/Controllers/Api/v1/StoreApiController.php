<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreApiController extends Controller
{
    /**
     * Get store information
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $store = Store::first();

        if (!$store) {
            return ApiResponse::notFound('Data toko belum diatur');
        }

        return ApiResponse::success([
            'id'             => $store->id,
            'name'           => $store->name,
            'address'        => $store->address,
            'phone'          => $store->phone,
            'email'          => $store->email,
            'logo'           => $store->logo,
            'logo_url'       => $store->logo ? asset('storage/' . $store->logo) : null,
            'printer_size'   => $store->printer_size,
            'receipt_footer' => $store->receipt_footer,
            'created_at'     => $store->created_at,
            'updated_at'     => $store->updated_at,
        ], 'Data toko berhasil diambil');
    }
}
