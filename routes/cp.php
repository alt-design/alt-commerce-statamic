<?php

use AltDesign\AltCommerceStatamic\Http\Controllers\BasketLookupController;
use AltDesign\AltCommerceStatamic\Http\Controllers\CustomerController;
use AltDesign\AltCommerceStatamic\Http\Controllers\CustomerLookupController;
use AltDesign\AltCommerceStatamic\Http\Controllers\Exports\OrderItemExportController;
use AltDesign\AltCommerceStatamic\Http\Controllers\OrderController;
use AltDesign\AltCommerceStatamic\Http\Controllers\ProductController;
use AltDesign\AltCommerceStatamic\Http\Controllers\ProductLookupController;
use AltDesign\AltCommerceStatamic\Http\Controllers\ReportController;
use AltDesign\AltCommerceStatamic\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated']], function() {

    Route::get('alt-commerce/orders', [OrderController::class, 'index'])->name('alt-commerce.order.index');
    Route::post('alt-commerce/orders', [OrderController::class, 'store'])->name('alt-commerce::order.store');
    Route::get('alt-commerce/order/{orderId}', [OrderController::class, 'show'])->name('alt-commerce.order.show');

    Route::get('alt-commerce/products', [ProductController::class, 'index'])->name('alt-commerce.product.index');
    Route::get('alt-commerce/product/{orderId}', [ProductController::class, 'show'])->name('alt-commerce.product.show');

    Route::get('alt-commerce/customers', [CustomerController::class, 'index'])->name('alt-commerce.customer.index');
    Route::get('alt-commerce/customer/{customerId}', [CustomerController::class, 'show'])->name('alt-commerce.customer.show');


    Route::get('alt-commerce/settings', [SettingsController::class, 'index'])->name('alt-commerce.settings.index');
    Route::post('alt-commerce/settings', [SettingsController::class, 'update'])->name('alt-commerce.settings.update');

    Route::get('/collections/orders/entries/create/default', [OrderController::class, 'create'])->name('alt-commerce.order.create');
    Route::get('/collections/orders/entries/{id}', [OrderController::class, 'show'])->name('alt-commerce.order.show');

    Route::get('/alt-commerce/customer-lookup', CustomerLookupController::class)->name('alt-commerce::customer.lookup');
    Route::get('/alt-commerce/product-lookup', ProductLookupController::class)->name('alt-commerce::product.lookup');
    Route::get('/alt-commerce/basket-lookup', BasketLookupController::class)->name('alt-commerce::basket.lookup');

    Route::get('/alt-commerce/reports', ReportController::class)->name('alt-commerce::reports.index');

    Route::post('/alt-commerce/exports/order-item', OrderItemExportController::class)->name('alt-commerce::exports.order-item');
});
