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
use App\Http\Controllers\Api\v1\StoreApiController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\UserController;

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
        // Route::prefix('categories')->group(function () {
        //     Route::get('/', [CategoryController::class, 'index']);
        //     Route::get('/{category}', [CategoryController::class, 'show']);
        //     Route::get('/{category}/products', [CategoryController::class, 'products']);
        // });

        /*
        |------------------------------------------------------------------
        | Products
        |------------------------------------------------------------------
        */
        // Route::prefix('products')->group(function () {
        //     Route::get('/', [ProductController::class, 'index']);
        //     Route::get('/search', [ProductController::class, 'search']);
        //     Route::get('/low-stock', [ProductController::class, 'lowStock']);
        //     Route::get('/expired', [ProductController::class, 'expired']);
        //     Route::get('/{product}', [ProductController::class, 'show']);
        //     Route::post('/barcode', [ProductController::class, 'findByBarcode']);
        // });

        /*
        |------------------------------------------------------------------
        | Discounts
        |------------------------------------------------------------------
        */
        // Route::prefix('discounts')->group(function () {
        //     Route::get('/', [DiscountController::class, 'index']);
        //     Route::get('/active', [DiscountController::class, 'active']);
        //     Route::get('/{discount}', [DiscountController::class, 'show']);
        //     Route::post('/check', [DiscountController::class, 'check']);
        // });

        /*
        |------------------------------------------------------------------
        | Wholesale Prices (Harga Grosir)
        |------------------------------------------------------------------
        */
        // Route::prefix('wholesale-prices')->group(function () {
        //     Route::get('/', [WholesalePriceController::class, 'index']);
        //     Route::get('/product/{product}', [WholesalePriceController::class, 'byProduct']);
        //     Route::post('/calculate', [WholesalePriceController::class, 'calculate']);
        // });

        /*
        |------------------------------------------------------------------
        | Shifts
        |------------------------------------------------------------------
        */
        // Route::prefix('shifts')->group(function () {
        //     Route::get('/', [ShiftController::class, 'index']);
        //     Route::get('/current', [ShiftController::class, 'current']);
        //     Route::get('/{shift}', [ShiftController::class, 'show']);
        // });

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