<?php

use App\Http\Controllers\Api\v1\AuthApiController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\WholesalePriceController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\CashFlowController;
use App\Http\Controllers\Api\v1\CategoryApiController;
use App\Http\Controllers\Api\v1\DiscountApiController;
use App\Http\Controllers\Api\v1\ProductApiController;
use App\Http\Controllers\Api\v1\ShiftApiController;
use App\Http\Controllers\Api\v1\StoreApiController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\v1\WholesaleApiPriceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/v1
| Middleware: api.key (validasi Accept header & X-API-Key)
|
| Headers yang diperlukan:
| - Accept: application/json
| - X-API-Key: HumaCode2025
|
*/

Route::prefix('v1')->middleware(['api.key'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Auth Routes (Public)
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthApiController::class, 'login']);
        Route::post('/register', [AuthApiController::class, 'register']);
    });

    /*
    |----------------------------------------------------------------------
    | Protected Routes (Butuh Token)
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        /*
        |------------------------------------------------------------------
        | Auth
        |------------------------------------------------------------------
        */
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthApiController::class, 'logout']);
            Route::get('/me', [AuthApiController::class, 'me']);
            Route::post('/profile', [AuthApiController::class, 'updateProfile']);
            Route::post('/password', [AuthApiController::class, 'updatePassword']);
        });

        /*
        |------------------------------------------------------------------
        | Store (Toko)
        |------------------------------------------------------------------
        */
        Route::get('/store', [StoreApiController::class, 'index']);

        /*
        |------------------------------------------------------------------
        | Categories
        |------------------------------------------------------------------
        */
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryApiController::class, 'index']);
            Route::get('/{category}', [CategoryApiController::class, 'show']);
            Route::get('/{category}/products', [CategoryApiController::class, 'products']);
        });

        /*
        |------------------------------------------------------------------
        | Products
        |------------------------------------------------------------------
        */
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductApiController::class, 'index']);
            Route::get('/search', [ProductApiController::class, 'search']);
            Route::get('/low-stock', [ProductApiController::class, 'lowStock']);
            Route::get('/expired', [ProductApiController::class, 'expired']);
            Route::get('/{product}', [ProductApiController::class, 'show']);
            Route::post('/barcode', [ProductApiController::class, 'findByBarcode']);
        });

        /*
        |------------------------------------------------------------------
        | Discounts
        |------------------------------------------------------------------
        */
        Route::prefix('discounts')->group(function () {
            Route::post('/check', [DiscountApiController::class, 'check']);
            Route::get('/', [DiscountApiController::class, 'index']);
            Route::get('/active', [DiscountApiController::class, 'active']);
            Route::get('/{discount}', [DiscountApiController::class, 'show']);
        });

        /*
        |------------------------------------------------------------------
        | Wholesale Prices (Harga Grosir)
        |------------------------------------------------------------------
        */
        Route::prefix('wholesale-prices')->group(function () {
            Route::get('/', [WholesaleApiPriceController::class, 'index']);
            Route::get('/product/{product}', [WholesaleApiPriceController::class, 'byProduct']);
            Route::post('/calculate', [WholesaleApiPriceController::class, 'calculate']);
        });

        /*
        |------------------------------------------------------------------
        | Shifts
        |------------------------------------------------------------------
        */
        Route::prefix('shifts')->group(function () {
            Route::get('/', [ShiftApiController::class, 'index']);
            Route::get('/current', [ShiftApiController::class, 'current']);
            Route::get('/{shift}', [ShiftApiController::class, 'show']);
        });

        /*
        |------------------------------------------------------------------
        | Transactions
        |------------------------------------------------------------------
        */
        // Route::prefix('transactions')->group(function () {
        //     Route::get('/', [TransactionController::class, 'index']);
        //     Route::post('/', [TransactionController::class, 'store']);
        //     Route::get('/today', [TransactionController::class, 'today']);
        //     Route::get('/summary', [TransactionController::class, 'summary']);
        //     Route::post('/sync', [TransactionController::class, 'sync']);
        //     Route::get('/{transaction}', [TransactionController::class, 'show']);
        // });

        /*
        |------------------------------------------------------------------
        | Cash Flow (Kas Masuk/Keluar)
        |------------------------------------------------------------------
        */
        // Route::prefix('cash-flows')->group(function () {
        //     Route::get('/', [CashFlowController::class, 'index']);
        //     Route::post('/', [CashFlowController::class, 'store']);
        //     Route::get('/today', [CashFlowController::class, 'today']);
        //     Route::get('/summary', [CashFlowController::class, 'summary']);
        //     Route::get('/{cashFlow}', [CashFlowController::class, 'show']);
        //     Route::put('/{cashFlow}', [CashFlowController::class, 'update']);
        //     Route::delete('/{cashFlow}', [CashFlowController::class, 'destroy']);
        // });

        /*
        |------------------------------------------------------------------
        | Users (untuk Owner/Admin)
        |------------------------------------------------------------------
        */
        // Route::prefix('users')->group(function () {
        //     Route::get('/', [UserController::class, 'index']);
        //     Route::get('/{user}', [UserController::class, 'show']);
        // });

    });

});