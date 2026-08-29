<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ItemCatalogController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('warehouse')->group(function () {
    Route::get('/dashboard', [WarehouseController::class, 'dashboard']);
    Route::get('/products', [WarehouseController::class, 'products']);
    Route::get('/transactions', [WarehouseController::class, 'transactions']);
    Route::post('/transactions/in', [WarehouseController::class, 'storeIn']);
    Route::post('/transactions/out', [WarehouseController::class, 'storeOut']);
    Route::get('/transactions/export', [WarehouseController::class, 'export']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    Route::get('/item-catalogs', [ItemCatalogController::class, 'index']);
    Route::post('/item-catalogs', [ItemCatalogController::class, 'store']);
    Route::put('/item-catalogs/{itemCatalog}', [ItemCatalogController::class, 'update']);
    Route::delete('/item-catalogs/{itemCatalog}', [ItemCatalogController::class, 'destroy']);
});
