<?php

use App\Http\Controllers\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('posts')
    ->name('posts.')
    ->middleware('api.token')
    ->controller(PostController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{post}', 'show')->name('show');
        Route::put('/{post}', 'update')->name('update');
        Route::patch('/{post}', 'update')->name('patch');
        Route::delete('/{post}', 'destroy')->name('destroy');
    });
