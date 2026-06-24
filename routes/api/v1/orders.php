<?php

use App\Http\Controllers\V1\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')
    ->name('orders.')
    ->middleware('api.token')
    ->controller(OrderController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/create', 'create')->name('create');
        Route::get('/report', 'report')->name('report');
        Route::patch('/{orderId}/cancel', 'cancel')->name('cancel');
    });
